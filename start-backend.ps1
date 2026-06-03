# Keeps the Croose backend (php artisan serve) running.
# Launches it detached/hidden so it doesn't catch console-close (Ctrl-C) events,
# and restarts it automatically if it ever exits.
$ErrorActionPreference = 'SilentlyContinue'
Set-Location "C:\Users\Admin\Desktop\croosebackend"
while ($true) {
    Start-Process -FilePath "C:\php83\php.exe" `
        -ArgumentList '-c', 'C:\php83\php.ini', 'artisan', 'serve' `
        -WorkingDirectory "C:\Users\Admin\Desktop\croosebackend" `
        -WindowStyle Hidden -Wait
    Start-Sleep -Seconds 3
}
