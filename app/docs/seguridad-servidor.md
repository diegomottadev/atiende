# Seguridad del servidor — Atiende producción (DigitalOcean)

**Fecha:** 2026-06-08 · **Servidor:** DigitalOcean droplet · Ubuntu 24.04 LTS · IP `137.184.76.211` · usuario `root`

Este documento registra **todas las decisiones y configuraciones de seguridad** aplicadas al servidor de producción, **dónde vive cada una**, **cómo editarla** y **cómo revertirla**. Es la fuente de verdad para mantener el blindaje del server.

> ⚠️ Esto es **producción** (`atiende.lat` / `admin.atiende.lat`). Antes de tocar SSH o el firewall, asegurate de tener una vía de acceso alternativa abierta (web console de DigitalOcean). Nunca corras `startup.sh` (resetea las DBs).

---

## Resumen de lo aplicado

| # | Control | Estado | Dónde se configura |
|---|---|---|---|
| 1 | Backends Docker (81/82) atados a `127.0.0.1` | ✅ | `app/docker-compose.yml`, `app-tenants/docker-compose.yml` (versionado) |
| 2 | Firewall **ufw** (solo 22/80/443) | ✅ | estado del SO (`ufw`) |
| 3 | **fail2ban** (anti fuerza bruta SSH **+ login del panel**) | ✅ | `/etc/fail2ban/jail.local` + `filter.d/atiende-login.conf` |
| 4 | **SSH** endurecido (solo llave, root prohibit-password) | ✅ | `/etc/ssh/sshd_config` |
| 5 | Acceso por llave + fallback web console | ✅ | `/root/.ssh/authorized_keys` + password de root |
| 6 | **unattended-upgrades** + parches de seguridad | ✅ | paquete del SO |
| 7 | Agente **seguridad-servidor** | ✅ | `app/.claude/agents/seguridad-servidor.md` (versionado) |
| — | Reboot pendiente (kernel + libc6) | ⏳ | ver sección *Pendientes* |

---

## Arquitectura de red (importante para entender todo lo demás)

```
Internet (HTTPS 443 / HTTP 80)
        │
        ▼
  nginx del HOST  ── termina TLS (Let's Encrypt) ── único expuesto a internet
        │
        ├── admin.atiende.lat        → proxy_pass 127.0.0.1:82 → contenedor pedidos_platform_nginx (panel superadmin)
        └── atiende.lat / *.atiende.lat → proxy_pass 127.0.0.1:81 → contenedor atiende-nginx (app pedidos)

  mysql8 (MySQL 8.4) → solo red interna de Docker (3306), NO expuesto al host
```

**Decisión clave:** los contenedores nginx (81 y 82) **no se exponen a internet** — solo el nginx del host habla con ellos por `127.0.0.1`. Así todo el tráfico público pasa obligatoriamente por HTTPS.

---

## 1. Backends Docker atados a localhost

**Qué / por qué:** antes los contenedores publicaban en `0.0.0.0` (toda interfaz, incluida la IP pública), así que el panel superadmin y la app eran accesibles por HTTP plano en `IP:8082` / `IP:81`, salteando el HTTPS. Se ataron a `127.0.0.1`. También se renombró el puerto del panel `8082 → 82`.

**Dónde se edita (versionado en git):**
- `app/docker-compose.yml` → `ports: - "127.0.0.1:${APP_PORT:-81}:80"`
- `app-tenants/docker-compose.yml` → `ports: - "127.0.0.1:82:80"`

**Cómo editar:** cambiá la línea `ports:` del servicio nginx. El formato es `IP_DEL_HOST:PUERTO_HOST:PUERTO_CONTENEDOR`.
- `127.0.0.1:82:80` = solo accesible desde el propio server (seguro).
- `82:80` o `0.0.0.0:82:80` = expuesto a internet (⚠️ evitar).

Tras editar, aplicar:
```bash
cd /root/atiende/app-tenants && docker compose up -d pedidos-nginx   # recrea solo ese contenedor
cd /root/atiende/app          && docker compose up -d                # recrea nginx de la app
```

> Si cambiás el **número** de puerto (ej. 82 → otro), actualizá también el `proxy_pass` del nginx del host (ver sección siguiente) y recargá nginx.

**Cómo verificar:**
```bash
ss -tlnp | grep -E ':81 |:82 '          # debe decir 127.0.0.1:81 / 127.0.0.1:82
curl --max-time 5 http://137.184.76.211:82/   # desde afuera debe FALLAR (rechazado)
```

**Cómo revertir:** volver la línea a `"82:80"` y recrear el contenedor. (No recomendado — reexpone el panel.)

---

## 2. Firewall (ufw)

**Qué / por qué:** el firewall estaba inactivo. Se activó permitiendo solo SSH (22), HTTP (80) y HTTPS (443); todo lo demás entrante se bloquea.

**Reglas actuales:**
```
22/tcp   ALLOW IN   # SSH
80/tcp   ALLOW IN   # HTTP nginx
443/tcp  ALLOW IN   # HTTPS nginx
Default: deny (incoming), allow (outgoing)
```

**Cómo editar:**
```bash
ufw allow <puerto>/tcp comment 'descripción'   # abrir un puerto
ufw delete allow <puerto>/tcp                  # cerrar un puerto
ufw status numbered                            # ver reglas con número
ufw delete <número>                            # borrar regla por número
```

**Cómo verificar:** `ufw status verbose`

**Cómo revertir (desactivar todo el firewall):** `ufw disable` (⚠️ deja el server sin firewall).

> ⚠️ **Docker se saltea ufw.** Los puertos de contenedores NO se controlan con ufw sino con el bind a `127.0.0.1` (sección 1). Abrir/cerrar puertos de contenedores con ufw **no tiene efecto**.

---

## 3. fail2ban (anti fuerza bruta)

**Qué / por qué:** SSH recibe cientos de intentos de login automáticos por día (bots). fail2ban detecta los fallos repetidos y **banea la IP atacante** temporalmente.

**Dónde se edita:** `/etc/fail2ban/jail.local`

**Config actual:**
- Jail `sshd`: 4 intentos fallidos en 10 min → ban 1 hora.
- Jail `recidive`: una IP baneada 3 veces en 1 día → ban 1 semana.
- `ignoreip = 127.0.0.1/8 ::1` (nunca banea loopback).

**Cómo editar:** abrir `/etc/fail2ban/jail.local`, ajustar `bantime` / `findtime` / `maxretry`, y aplicar:
```bash
systemctl restart fail2ban
```

**Cómo verificar:**
```bash
fail2ban-client status sshd          # IPs baneadas ahora y total histórico
```

**Desbanear una IP (ej. si te baneaste vos):**
```bash
fail2ban-client set sshd unbanip <IP>
```

**Cómo revertir (desactivar):** `systemctl stop fail2ban && systemctl disable fail2ban`

### 3.1 Jail del login del panel (`atiende-login`)

**Qué / por qué:** además de SSH, el formulario de login del panel (`vistas/login.php`) era atacable por fuerza bruta vía HTTP sin ningún freno. Se agregó un jail que **banea la IP** tras varios intentos fallidos de login en el panel.

**Cómo funciona (la clave es el 401):**
- El endpoint `app/ajax/usuario.php?op=verificar` responde **HTTP 401 SOLO ante un fallo** de login (credenciales inválidas o tenant inexistente). Un login **exitoso responde 200**. Así el jail cuenta únicamente los fallos y nunca banea a un usuario que entra bien.
- fail2ban lee el **access log del nginx del host** (`/var/log/nginx/access.log`, formato combined) que ya registra la **IP real** del cliente, y matchea las líneas `POST /ajax/usuario.php?op=verificar … 401`.
- El front (`vistas/scripts/login.js`) maneja el 401 en `.fail()`: muestra "usuario/contraseña incorrectos" en el 401, y un aviso de "esperá unos minutos" ante cualquier otro estado (probable ban temporal).

**Piezas (2 archivos en el SO, fuera del repo):**
- `/etc/fail2ban/filter.d/atiende-login.conf` — el filtro:
  ```
  failregex = ^<HOST> -.*"POST /ajax/usuario\.php\?op=verificar[^"]*" 401
  ```
  (sin `datepattern`: fail2ban autodetecta el formato de fecha de nginx.)
- `/etc/fail2ban/jail.local` — la sección `[atiende-login]`. **Ojo:** el `[DEFAULT]` usa `backend = systemd`; este jail lo **override con `backend = polling`** para leer el archivo de nginx en vez del journal:
  ```ini
  [atiende-login]
  enabled  = true
  filter   = atiende-login
  backend  = polling
  logpath  = /var/log/nginx/access.log
  port     = http,https
  maxretry = 5
  findtime = 10m
  bantime  = 1h
  ```
- Config actual: **5 fallos en 10 min → ban 1 hora**. Reincidentes escalan al jail `recidive` (3 bans en 1 día → 1 semana).

**Cómo verificar:**
```bash
fail2ban-client status atiende-login                       # IPs baneadas / total
# Probar el filtro contra el log real (sin banear nada):
fail2ban-regex /var/log/nginx/access.log /etc/fail2ban/filter.d/atiende-login.conf
# Generar un 401 real de prueba con IP loopback (ignorada, no banea):
curl -sk -o /dev/null -w "%{http_code}\n" --resolve demo.atiende.lat:443:127.0.0.1 \
  "https://demo.atiende.lat/ajax/usuario.php?op=verificar" \
  -d "logina=x&clavea=y&empresa=demo"   # debe imprimir 401
```

**Desbanear una IP:** `fail2ban-client set atiende-login unbanip <IP>`

> **Si cambiás la ruta del endpoint o el formato de log de nginx**, actualizá el `failregex` y re-validá con `fail2ban-regex`. El jail depende de que el 401 siga saliendo **solo** en los fallos (no toques esa lógica en `usuario.php`).

---

## 4. SSH endurecido

**Qué / por qué:** se bloqueó el login de root por contraseña vía SSH. Ahora root **solo entra con llave**. (El login por contraseña ya estaba deshabilitado para todos).

**Dónde se edita:** `/etc/ssh/sshd_config`

**Valores aplicados:**
```
PermitRootLogin prohibit-password    # root solo con llave (no password)
PasswordAuthentication no            # nadie entra con contraseña por SSH
PubkeyAuthentication yes             # login con llave habilitado
```

**Cómo editar de forma SEGURA (orden importa):**
```bash
nano /etc/ssh/sshd_config        # hacer el cambio
sshd -t                          # 1. VALIDAR sintaxis (si falla, NO recargar)
systemctl reload ssh             # 2. aplicar SIN cortar sesiones (reload, no restart)
sshd -T | grep -i permitroot     # 3. verificar valor efectivo
```
> Tras un cambio en SSH, **abrí una sesión NUEVA sin cerrar la actual** para confirmar que seguís entrando. Es la red de seguridad contra dejarte afuera.

**Cómo revertir:** poner `PermitRootLogin yes` y `systemctl reload ssh`.

---

## 5. Acceso al servidor

### Vía principal — SSH con llave
- Llave autorizada: `ssh-ed25519 ... diegomottadev@gmail.com` (fingerprint `SHA256:Ni6hjGHb...`).
- Archivo en el server: `/root/.ssh/authorized_keys` (permisos `600`).
- La llave privada vive **solo en tu compu** (`C:\Users\ACER\.ssh\id_ed25519_atiende`).

**Conectar desde tu Windows:**
```powershell
ssh atiende        # usa el alias de ~/.ssh/config
# o explícito:
ssh -i $env:USERPROFILE\.ssh\id_ed25519_atiende root@137.184.76.211
```
Alias en `C:\Users\ACER\.ssh\config`:
```
Host atiende
    HostName 137.184.76.211
    User root
    Port 22
    IdentityFile ~/.ssh/id_ed25519_atiende
```

**Autorizar una llave nueva** (ej. otra compu): pegar la **pública** (`.pub`) en el server:
```bash
echo 'ssh-ed25519 AAAA... comentario' >> /root/.ssh/authorized_keys
```
**Quitar una llave:** editar `/root/.ssh/authorized_keys` y borrar la línea.

### Vía de emergencia — Web console de DigitalOcean
- No usa SSH ni la red: es la consola virtual del panel de DigitalOcean.
- Pide **usuario `root` + contraseña** (la contraseña de root está seteada; guardala en tu gestor).
- Sirve aunque SSH/firewall fallen por completo. Es tu red de seguridad definitiva.
- Si olvidás la contraseña: panel DigitalOcean → droplet → Access → **Reset Root Password**.

**Cambiar la contraseña de root:** en una sesión SSH, ejecutá `passwd` (interactivo).

---

## 6. Actualizaciones de seguridad

**Qué / por qué:** `unattended-upgrades` está instalado y activo (aplica parches de seguridad solo). Se aplicaron manualmente 4 parches pendientes (systemd, nginx, telnet).

**Cómo editar la política:** `/etc/apt/apt.conf.d/50unattended-upgrades` y `20auto-upgrades`.

**Aplicar updates de seguridad a mano:**
```bash
apt-get update
unattended-upgrade -v          # solo las de seguridad (conservador)
# o todo:
apt-get upgrade
```

**Verificar pendientes:**
```bash
apt-get -s upgrade | grep -i '^Inst.*securit'
```

---

## 7. Agente de seguridad `seguridad-servidor`

**Qué es:** un agente de Claude Code que audita y endurece la **infraestructura** del server (complementa a `security-audit`, que es de código PHP).

**Dónde vive (versionado):** `app/.claude/agents/seguridad-servidor.md`

**Qué audita:** puertos expuestos, ufw, fail2ban, SSH, updates de seguridad, integridad de archivos críticos, indicios de intrusión (llaves SSH raras, cron sospechoso, conexiones C2), disco.

**Cómo usarlo:** en una sesión de Claude Code dentro de `app/`, pedile *"usá el agente seguridad-servidor para auditar"*. Comandos: `auditar`, `auditar: {área}`, `endurecer: {control}`, `revisar-baseline`, `intrusión`, `resumen`.

**Cómo editarlo:** es un `.md` con frontmatter (`name`, `description`) + instrucciones. Editá el cuerpo para sumar chequeos o ajustar el baseline. Si cambiás la arquitectura del server (puertos, contenedores), **actualizá la tabla de baseline** dentro del agente para que no dé falsos positivos.

---

## Pendientes

### ⏳ Reboot por kernel + libc6
El último update de seguridad instaló un **kernel nuevo** y **libc6 nueva** que requieren reiniciar para activarse. **No es urgente** (los demás parches ya están activos; el kernel/libc son preventivos). Hacelo en una ventana de bajo tráfico.

**Verificado que el reboot es seguro:** todos los contenedores son `unless-stopped` (auto-arrancan), MySQL persiste `sql_mode` (`mysqld-auto.cnf`) y `mysql_native_password` (config), y ufw/fail2ban/nginx/docker están `enabled` al boot.

**Cómo hacerlo:**
```bash
sudo reboot          # ~30-60s de caída; tu sesión SSH se corta
# esperar ~1 min y volver:
ssh atiende
# verificar que volvió todo:
docker ps; ufw status; systemctl is-active fail2ban nginx
curl -I https://atiende.lat
```
Tras el reboot, el archivo `/var/run/reboot-required` desaparece.

---

## Apéndice — auditoría rápida (copiar y pegar)

```bash
# Puertos expuestos (solo deben verse 22/80/443 en 0.0.0.0)
ss -tlnp | grep -E '0\.0\.0\.0|\[::\]'
# Firewall
ufw status verbose
# fail2ban
fail2ban-client status sshd
fail2ban-client status atiende-login   # fuerza bruta al login del panel
# SSH
sshd -T | grep -Ei 'permitrootlogin|passwordauthentication'
# Intentos SSH fallidos / exitosos (24h)
journalctl -u ssh --since '24 hours ago' | grep -ci 'failed password'
journalctl -u ssh --since '24 hours ago' | grep -i 'accepted'
# Llaves SSH autorizadas (¿alguna desconocida?)
ssh-keygen -lf /root/.ssh/authorized_keys
# Updates de seguridad pendientes
apt-get -s upgrade 2>/dev/null | grep -ci '^Inst.*securit'
# Disco
df -h /
```

**Datos del server:** IP `137.184.76.211` · usuario `root` · Ubuntu 24.04 · DigitalOcean.
**Puertos públicos permitidos:** 22 (SSH), 80 (HTTP), 443 (HTTPS).
**Contenedores esperados:** `atiende-nginx`, `atiende-app`, `pedidos_platform_nginx`, `pedidos_platform_app`, `mysql8`.
