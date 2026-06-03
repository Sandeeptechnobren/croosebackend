# start-tunnel.ps1 — self-healing cloudflared quick tunnel for the Chatterly webhook.
#
# Exposes the local backend (127.0.0.1:8000) on a public https URL so Chatterly can POST
# incoming WhatsApp messages and session.connected events. On every (re)start it:
#   1. launches the tunnel,
#   2. captures the assigned https://*.trycloudflare.com URL,
#   3. writes CHATTERLY_WEBHOOK_URL into .env,
#   4. re-registers that URL with Chatterly for all instances (php artisan chatterly:webhook),
#   5. waits for the tunnel to die, then loops and does it all again.

$ErrorActionPreference = 'SilentlyContinue'

$Cloudflared = 'C:\Users\Admin\Desktop\cloudflared.exe'
$Php         = 'C:\php83\php.exe'
$Backend     = 'C:\Users\Admin\Desktop\croosebackend'
$EnvFile     = Join-Path $Backend '.env'
$LocalUrl    = 'http://127.0.0.1:8000'

function Update-Env([string]$publicUrl) {
    $hook  = "$publicUrl/api/chatterly/webhook"
    $lines = Get-Content $EnvFile
    if ($lines -match '^CHATTERLY_WEBHOOK_URL=') {
        $lines = $lines -replace '^CHATTERLY_WEBHOOK_URL=.*', "CHATTERLY_WEBHOOK_URL=$hook"
    } else {
        $lines += "CHATTERLY_WEBHOOK_URL=$hook"
    }
    Set-Content -Path $EnvFile -Value $lines -Encoding utf8
    return $hook
}

# Kill any stale tunnels so we don't run duplicates
Get-Process cloudflared -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue

while ($true) {
    $log    = Join-Path $env:TEMP ("cf-out-{0}.log" -f (Get-Random))
    $logErr = "$log.err"
    Remove-Item $log, $logErr -Force -ErrorAction SilentlyContinue

    $proc = Start-Process -FilePath $Cloudflared `
        -ArgumentList @('tunnel', '--no-autoupdate', '--url', $LocalUrl) `
        -RedirectStandardOutput $log -RedirectStandardError $logErr `
        -WindowStyle Hidden -PassThru

    # Wait (up to 60s) for the public URL to appear in the tunnel output
    $publicUrl = $null
    for ($i = 0; $i -lt 60 -and -not $publicUrl; $i++) {
        Start-Sleep -Seconds 1
        $content = (Get-Content $log -Raw -ErrorAction SilentlyContinue) + `
                   (Get-Content $logErr -Raw -ErrorAction SilentlyContinue)
        if ($content -match 'https://[a-z0-9-]+\.trycloudflare\.com') {
            $publicUrl = $Matches[0]
        }
    }

    $hook = $null
    if ($publicUrl) {
        $hook = Update-Env $publicUrl
        Push-Location $Backend
        & $Php artisan chatterly:webhook $hook | Out-Null
        Pop-Location
    }

    # Health-check loop. The quick-tunnel hostname can stop routing (Cloudflare 530 "origin
    # unregistered" / 1016) while cloudflared keeps running, so process-exit alone isn't enough.
    # We must verify via PUBLIC DNS (8.8.8.8) + `curl --resolve`, because THIS machine's default
    # resolver does not resolve fresh trycloudflare subdomains — using it would false-fail a
    # tunnel that Chatterly can actually reach, churning a healthy tunnel every cycle.
    $tunnelHost = ([uri]$hook).Host
    $fails = 0
    while ($proc -and -not $proc.HasExited) {
        Start-Sleep -Seconds 90
        if (-not $hook) { break }   # never got a URL; restart
        $healthy = $false
        try {
            $ip = (Resolve-DnsName $tunnelHost -Server 8.8.8.8 -Type A -ErrorAction Stop |
                   Select-Object -First 1).IPAddress
            if ($ip) {
                # Any real HTTP response from our origin (2xx-4xx) means the tunnel routes;
                # 530/000 means the cloudflared origin is unregistered/unreachable.
                $code = & curl.exe -s -o NUL -w "%{http_code}" -X POST $hook `
                    --resolve "$($tunnelHost):443:$ip" -H "Content-Type: application/json" `
                    -d '{"event":"ping"}' --max-time 25
                if ($code -match '^[234]\d\d$') { $healthy = $true }
            }
        } catch { $healthy = $false }

        if ($healthy) { $fails = 0 } else {
            $fails++
            if ($fails -ge 2) {
                Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
                break
            }
        }
    }

    Start-Sleep -Seconds 3
}
