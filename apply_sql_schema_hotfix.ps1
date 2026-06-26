param(
    [string]$ProjectRoot = (Get-Location).Path
)

$PatchRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoPatch = Join-Path $PatchRoot "repo"

Copy-Item -Path (Join-Path $RepoPatch "*") -Destination $ProjectRoot -Recurse -Force

$delete = @(
    "app\Models\EdomAnswer.php",
    "app\Models\Edom.php",
    "app\Models\EdomCategory.php",
    "app\Models\EdomOption.php",
    "app\Models\EdomSetting.php",
    "app\Models\SettingsEdom.php",
    "app\Models\Prodi.php",
    "app\Models\MataKuliah.php",
    "app\Models\Course.php",
    "app\Filament\Resources\MataKuliahs",
    "app\Filament\Resources\Edoms",
    "app\Filament\Resources\EdomCategories",
    "app\Filament\Resources\EdomOptions",
    "app\Filament\Resources\Prodis"
)

foreach ($item in $delete) {
    $target = Join-Path $ProjectRoot $item
    if (Test-Path $target) {
        Remove-Item $target -Recurse -Force
    }
}

Write-Host "SQL schema hotfix applied."
Write-Host "Run:"
Write-Host "composer dump-autoload"
Write-Host "php artisan optimize:clear"
Write-Host "php artisan migrate:fresh --seed"
