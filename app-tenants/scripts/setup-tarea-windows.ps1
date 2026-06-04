# Ejecutar UNA SOLA VEZ como Administrador
# Registra la tarea programada que agrega subdominos al hosts automaticamente

$scriptPath = Join-Path $PSScriptRoot "agregar-tenant.ps1"

$action = New-ScheduledTaskAction `
    -Execute "powershell.exe" `
    -Argument "-NonInteractive -ExecutionPolicy Bypass -File `"$scriptPath`""

$trigger = New-ScheduledTaskTrigger -RepetitionInterval (New-TimeSpan -Minutes 1) -Once -At (Get-Date)

$settings = New-ScheduledTaskSettingsSet `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 1) `
    -MultipleInstances IgnoreNew

$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -RunLevel Highest

Register-ScheduledTask `
    -TaskName "AtiendeTenantHosts" `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Principal $principal `
    -Description "Agrega subdominios atiende.localhost al hosts cuando se provisiona un tenant nuevo" `
    -Force

Write-Host "Tarea 'AtiendeTenantHosts' registrada. Corre cada 1 minuto como SYSTEM."
Write-Host "Para verificar: Get-ScheduledTask -TaskName 'AtiendeTenantHosts'"
Write-Host "Para eliminar:  Unregister-ScheduledTask -TaskName 'AtiendeTenantHosts' -Confirm:`$false"
