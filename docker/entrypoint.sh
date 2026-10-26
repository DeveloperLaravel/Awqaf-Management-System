$path = "docker\entrypoint.sh"
$content = [System.IO.File]::ReadAllText((Resolve-Path $path))
$content = $content -replace "^\uFEFF", ""
$content = $content -replace "`r`n|`r", "`n"

if (-not $content.StartsWith("#!/bin/sh")) {
    $content = "#!/bin/sh`n" + $content
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText((Resolve-Path $path), $content, $utf8NoBom)