<#
.SYNOPSIS
  Regenera el .env local (git-ignored) del panel de tenants desde su template.

.DESCRIPTION
  .env  <-  .env.docker.<env>

  El .env del panel de tenants (pedidos-platform) NO se versiona (secretos /
  valores de entorno) y por eso se PIERDE ante un `git merge`/`git pull` que lo
  borre del working tree. Sintoma: el panel tira
    Dotenv\Exception\InvalidPathException: Unable to read ... /var/www/pedidos-platform/.env
  Este script lo vuelve a generar. Idempotente: por defecto NO pisa un .env que
  ya existe (usar -Force para sobrescribir).

.EXAMPLE
  .\setup-config.ps1            # entorno dev (default)
  .\setup-config.ps1 -Prod      # entorno prod (valores a COMPLETAR a mano)
  .\setup-config.ps1 -Force     # sobrescribe aunque ya exista
#>
param(
  [switch]$Prod,
  [switch]$Force
)

$ErrorActionPreference = 'Stop'
Set-Location -Path $PSScriptRoot

$env_ = if ($Prod) { 'prod' } else { 'dev' }
$template = ".env.docker.$env_"
$target   = ".env"

if (-not (Test-Path $template)) {
  Write-Error "  x falta el template $template"
}
if ((Test-Path $target) -and (-not $Force)) {
  Write-Host "  = $target ya existe (omito; -Force para sobrescribir)"
} else {
  Copy-Item -Path $template -Destination $target -Force
  Write-Host "  + $target  <-  $template"
  if ($env_ -eq 'prod') {
    Write-Host "  ! recorda completar los valores COMPLETAR_* en $target"
  }
}
Write-Host "Listo."
