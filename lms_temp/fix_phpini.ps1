$phpDir = 'C:\Users\28Q\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.5_Microsoft.Winget.Source_8wekyb3d8bbwe'
$iniPath = "$phpDir\php.ini"
$certPath = "$phpDir\cacert.pem"

# Read the file as raw bytes, strip null bytes (UTF-16 corruption)
$bytes = [System.IO.File]::ReadAllBytes($iniPath)
$content = [System.Text.Encoding]::UTF8.GetString($bytes) -replace [char]0, ''

# Remove any previously (mis-)appended cainfo/cafile lines
$lines = $content -split "`r?`n" | Where-Object {
    $_ -notmatch '^\s*curl\.cainfo' -and $_ -notmatch '^\s*openssl\.cafile'
}

# Append correct lines
$lines += "curl.cainfo=`"$certPath`""
$lines += "openssl.cafile=`"$certPath`""

# Write back as UTF-8 without BOM
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllLines($iniPath, $lines, $utf8NoBom)

Write-Host "php.ini fixed successfully."
Write-Host "curl.cainfo set to: $certPath"
