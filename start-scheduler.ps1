# Keeps the Laravel scheduler running so scheduled broadcasts actually fire.
# `schedule:work` is a long-running process that triggers due scheduled commands every
# minute — here that's `broadcast:run` (registered every minute in app/Console/Kernel.php),
# which delivers any due broadcast via Chatterly. Restarts automatically if it ever exits.
$ErrorActionPreference = 'SilentlyContinue'
Set-Location "C:\Users\Admin\Desktop\croosebackend"
while ($true) {
    Start-Process -FilePath "C:\php83\php.exe" `
        -ArgumentList '-c', 'C:\php83\php.ini', 'artisan', 'schedule:work' `
        -WorkingDirectory "C:\Users\Admin\Desktop\croosebackend" `
        -WindowStyle Hidden -Wait
    Start-Sleep -Seconds 3
}
