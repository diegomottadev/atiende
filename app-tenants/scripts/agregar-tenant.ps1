# Corre como tarea programada (SYSTEM). Lee pending_hosts.txt y agrega entradas al hosts.

$pendingFile = "C:\Users\ACER\Downloads\whatsbus2021-main\pedidos-platform\pending_hosts.txt"
$hostsFile   = "C:\Windows\System32\drivers\etc\hosts"
$domain      = "atiende.localhost"

if (-not (Test-Path $pendingFile)) { exit 0 }

$slugs = Get-Content $pendingFile -ErrorAction SilentlyContinue | Where-Object { $_.Trim() -ne "" }
if (-not $slugs) {
    Clear-Content $pendingFile -ErrorAction SilentlyContinue
    exit 0
}

$hostsContent = Get-Content $hostsFile -Raw

foreach ($slug in $slugs) {
    $slug = $slug.Trim()
    $entry = "127.0.0.1  $slug.$domain"

    if ($hostsContent -notmatch [regex]::Escape("$slug.$domain")) {
        Add-Content -Path $hostsFile -Value $entry
        $hostsContent += "`n$entry"
        Write-EventLog -LogName Application -Source "AtiendeTenantHosts" -EventId 1001 -EntryType Information -Message "Agregado: $entry" -ErrorAction SilentlyContinue
    }
}

Clear-Content $pendingFile
