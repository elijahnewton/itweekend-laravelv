$iniPath = 'C:\Users\28Q\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.5_Microsoft.Winget.Source_8wekyb3d8bbwe\php.ini'
$extDir  = 'C:\Users\28Q\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.5_Microsoft.Winget.Source_8wekyb3d8bbwe\ext'

# Extensions required by Laravel 11 + Filament
$required = @(
    'fileinfo',
    'pdo_mysql',
    'pdo_sqlite',
    'sqlite3',
    'gd',
    'zip',
    'exif',
    'intl',
    'soap',
    'sockets',
    'sodium'
)

# Read current ini (already fixed to UTF-8)
$content = Get-Content $iniPath -Raw

# Set extension_dir if not already set correctly
if ($content -notmatch 'extension_dir\s*=\s*"ext"') {
    $content = $content -replace ';?\s*extension_dir\s*=.*', "extension_dir = `"$extDir`""
}

foreach ($ext in $required) {
    $dll = "php_$ext.dll"
    # Check if dll exists
    if (-not (Test-Path "$extDir\$dll")) {
        Write-Host "SKIP (no dll): $dll"
        continue
    }
    # If already enabled (uncommented), skip
    if ($content -match "(?m)^extension=$dll") {
        Write-Host "ALREADY ON : $ext"
        continue
    }
    # If commented out, uncomment
    if ($content -match "(?m)^;extension=$dll") {
        $content = $content -replace "(?m)^;extension=$dll", "extension=$dll"
        Write-Host "UNCOMMENTED: $ext"
    } else {
        # Append fresh
        $content = $content.TrimEnd() + "`r`nextension=$dll`r`n"
        Write-Host "APPENDED   : $ext"
    }
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($iniPath, $content, $utf8NoBom)
Write-Host "`nDone. Extensions enabled."
