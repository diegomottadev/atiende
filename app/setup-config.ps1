<#
.SYNOPSIS
  Regenera los configs locales (git-ignored) desde sus templates.

.DESCRIPTION
  config/database.php  <-  config/database.docker.<env>
  config/global.php     <-  config/global.docker.<env>

  Estos archivos NO se versionan (contienen secretos / valores de entorno) y
  por eso se PIERDEN ante un `git merge`/`git pull` que los borre del working
  tree. Este script los vuelve a generar. Es idempotente: por defecto NO pisa
  un archivo que ya existe (usar -Force para sobrescribir).

.EXAMPLE
  .\setup-config.ps1            # entorno dev (default)
  .\setup-config.ps1 -Prod      # entorno prod
  .\setup-config.ps1 -Force     # sobrescribe aunque ya exista
#>
param(
  [switch]$Prod,
  [switch]$Force
)

$ErrorActionPreference = 'Stop'
Set-Location -Path $PSScriptRoot

$env_ = if ($Prod) { 'prod' } else { 'dev' }

function Ensure-Config([string]$name) {
  $target   = "config/$name.php"
  $template = "config/$name.docker.$env_"
  if (-not (Test-Path $template)) {
    Write-Error "  x falta el template $template"
  }
  if ((Test-Path $target) -and (-not $Force)) {
    Write-Host "  = $target ya existe (omito; -Force para sobrescribir)"
  } else {
    Copy-Item -Path $template -Destination $target -Force
    Write-Host "  + $target  <-  $template"
  }
}

Write-Host "Generando config de entorno: $env_"
Ensure-Config 'database'
Ensure-Config 'global'
Write-Host "Listo."
