$("#frmAcceso").on('submit', function(e)
{
	e.preventDefault();
	logina=$("#logina").val();
	clavea=$("#clavea").val();
	empresa=$("#empresa").val().trim();

	// Autodetectar el slug del subdominio si el campo está vacío.
	// corp.atiende.localhost -> "corp" | atiende.localhost -> "" (cuenta principal)
	if (empresa === '') {
		var parts = window.location.hostname.split('.');
		if (parts.length > 1 && ['atiende','www','localhost'].indexOf(parts[0]) === -1) {
			empresa = parts[0];
		}
	}

	$.post("../ajax/usuario.php?op=verificar", {"logina":logina, "clavea":clavea, "empresa":empresa})
        .done(function(data)
        {
            if (data.trim()!='null')
            {
                $(location).attr("href","escritorio.php");
            }else{
                // Path de error infra (DB caída): el backend devuelve 'null' con 200
                Swal.fire({ icon: 'error', text: 'Usuario y/o contraseña incorrectos' });
            }
        })
        .fail(function(xhr)
        {
            // 401 = credenciales inválidas (el backend lo manda así para que fail2ban
            // cuente solo los fallos). Cualquier otro estado / sin respuesta puede ser
            // un bloqueo temporal por demasiados intentos (fail2ban banea la IP 1h).
            if (xhr.status === 401) {
                Swal.fire({ icon: 'error', text: 'Usuario y/o contraseña incorrectos' });
            } else {
                Swal.fire({
                    icon: 'warning',
                    text: 'No se pudo iniciar sesión. Si reintentaste muchas veces, esperá unos minutos antes de volver a probar.'
                });
            }
        });
});