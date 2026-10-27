# download-vendor.ps1 - Bootstrap, Icons, Chart.js aur Poppins ko assets\vendor mein download karta hai
# Sirf EK baar chalana hai (internet chahiye). Is ke baad website internet ke baghair bhi chalegi.
#
# Kaise chalayen: project folder mein terminal kholo aur likho:
#   powershell -ExecutionPolicy Bypass -File .\download-vendor.ps1

$root = Join-Path $PSScriptRoot 'assets\vendor'
$cdn  = 'https://cdn.jsdelivr.net/npm'

$files = @(
  @{ Url = "$cdn/bootstrap@5.3.3/dist/css/bootstrap.min.css";            Path = 'bootstrap\bootstrap.min.css' },
  @{ Url = "$cdn/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js";       Path = 'bootstrap\bootstrap.bundle.min.js' },
  @{ Url = "$cdn/bootstrap-icons@1.11.3/font/bootstrap-icons.css";       Path = 'bootstrap-icons\bootstrap-icons.css' },
  @{ Url = "$cdn/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff2"; Path = 'bootstrap-icons\fonts\bootstrap-icons.woff2' },
  @{ Url = "$cdn/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff";  Path = 'bootstrap-icons\fonts\bootstrap-icons.woff' },
  @{ Url = "$cdn/chart.js@4.4.3/dist/chart.umd.min.js";                  Path = 'chartjs\chart.umd.min.js' },
  @{ Url = "$cdn/@fontsource/poppins@5.0.8/files/poppins-latin-400-normal.woff2"; Path = 'poppins\poppins-latin-400-normal.woff2' },
  @{ Url = "$cdn/@fontsource/poppins@5.0.8/files/poppins-latin-500-normal.woff2"; Path = 'poppins\poppins-latin-500-normal.woff2' },
  @{ Url = "$cdn/@fontsource/poppins@5.0.8/files/poppins-latin-600-normal.woff2"; Path = 'poppins\poppins-latin-600-normal.woff2' },
  @{ Url = "$cdn/@fontsource/poppins@5.0.8/files/poppins-latin-700-normal.woff2"; Path = 'poppins\poppins-latin-700-normal.woff2' }
)

$ok = 0; $failed = 0
foreach ($f in $files) {
  $dest = Join-Path $root $f.Path
  New-Item -ItemType Directory -Force -Path (Split-Path $dest) | Out-Null
  try {
    Invoke-WebRequest -Uri $f.Url -OutFile $dest -UseBasicParsing
    Write-Host ("OK      " + $f.Path) -ForegroundColor Green
    $ok++
  } catch {
    Write-Host ("FAILED  " + $f.Path + "  (" + $_.Exception.Message + ")") -ForegroundColor Red
    $failed++
  }
}
Write-Host ""
Write-Host "Done: $ok downloaded, $failed failed."
if ($failed -gt 0) { Write-Host "Jo FAILED hain unke liye website khud CDN use karti rahegi (design nahi tootega)." -ForegroundColor Yellow }
