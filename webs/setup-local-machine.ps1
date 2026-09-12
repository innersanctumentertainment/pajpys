# Run this ONCE on your Windows machine to migrate PAJPYS off Cursor Cloud
# and match your other AgapeTech projects layout.
#
# Usage (PowerShell):
#   cd $env:USERPROFILE\Desktop\webs
#   git clone https://github.com/innersanctumentertainment/pajpys.git _pajpys-temp
#   .\_pajpys-temp\webs\setup-local-machine.ps1
#
# Or download setup-local-machine.ps1 from the repo and run it.

$ErrorActionPreference = "Stop"

$ProjectsRoot = Join-Path $env:USERPROFILE "Projects"
$RepoName = "pajpys"
$RepoUrl = "https://github.com/innersanctumentertainment/pajpys.git"
$ProjectPath = Join-Path $ProjectsRoot $RepoName
$DesktopWebs = Join-Path ([Environment]::GetFolderPath("Desktop")) "webs"
$HandoffPath = Join-Path $DesktopWebs "pajpys"

Write-Host "PAJPYS local migration" -ForegroundColor Cyan
Write-Host "  Projects folder: $ProjectPath"
Write-Host "  Handoff folder:  $HandoffPath"
Write-Host ""

New-Item -ItemType Directory -Force -Path $ProjectsRoot | Out-Null
New-Item -ItemType Directory -Force -Path $DesktopWebs | Out-Null
New-Item -ItemType Directory -Force -Path $HandoffPath | Out-Null

if (Test-Path $ProjectPath) {
    Write-Host "Updating existing clone at $ProjectPath ..."
    Set-Location $ProjectPath
    git pull origin main
} else {
    Write-Host "Cloning $RepoUrl ..."
    git clone $RepoUrl $ProjectPath
    Set-Location $ProjectPath
}

Write-Host "Running first-time install ..."
& (Join-Path $ProjectPath "tools\install-local.ps1")

Write-Host "Copying handoff docs to Desktop\webs\pajpys ..."
Copy-Item -Force (Join-Path $ProjectPath "webs\pajpys\MASTER_PROMPT.md") $HandoffPath
Copy-Item -Force (Join-Path $ProjectPath "webs\pajpys\PROJECT_HANDOFF.md") $HandoffPath
Copy-Item -Force (Join-Path $ProjectPath "webs\pajpys\MIGRATE_OFF_CLOUD.md") $HandoffPath
Copy-Item -Force (Join-Path $ProjectPath "webs\pajpys\README.txt") $HandoffPath

Write-Host ""
Write-Host "Done." -ForegroundColor Green
Write-Host "  Code:    $ProjectPath"
Write-Host "  Handoff: $HandoffPath"
Write-Host "  Dev:     cd $ProjectPath; .\tools\serve.ps1"
Write-Host "  URL:     http://127.0.0.1:8095"
Write-Host ""
Write-Host "Open MASTER_PROMPT.md from Desktop\webs\pajpys when starting a new Cursor chat."
Write-Host ""
