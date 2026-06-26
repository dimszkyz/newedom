param(
    [string]$ProjectRoot = (Get-Location).Path
)

$PatchRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoPatch = Join-Path $PatchRoot "repo"
$DeleteList = Join-Path $PatchRoot "DELETE_FILES.txt"

Write-Host "Copy patch files to $ProjectRoot"
Copy-Item -Path (Join-Path $RepoPatch "*") -Destination $ProjectRoot -Recurse -Force

Write-Host "Delete old files/folders"
Get-Content $DeleteList | ForEach-Object {
    $item = $_.Trim()
    if ($item -ne "") {
        $target = Join-Path $ProjectRoot $item
        if (Test-Path $target) {
            Remove-Item $target -Recurse -Force
            Write-Host "Deleted $item"
        }
    }
}

Write-Host "Done. Run these commands from project root:"
Write-Host "composer dump-autoload"
Write-Host "php artisan optimize:clear"
Write-Host "php artisan migrate:fresh --seed"
