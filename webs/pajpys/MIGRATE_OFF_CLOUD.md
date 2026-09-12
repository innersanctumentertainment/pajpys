# Move PAJPYS from Cursor Cloud to your PC

You cannot pull files directly from a Cloud Agent VM to your desktop. The repo on GitHub is the bridge.

## One-time setup (on your Windows PC)

### Option A — automated (recommended)

1. Open PowerShell.
2. Create the handoff folder and clone:

```powershell
mkdir "$env:USERPROFILE\Desktop\webs" -Force
cd "$env:USERPROFILE\Desktop\webs"
git clone https://github.com/innersanctumentertainment/pajpys.git _pajpys-temp
.\_pajpys-temp\webs\setup-local-machine.ps1
```

3. Delete the temp clone if you like: `Remove-Item -Recurse _pajpys-temp`

### Option B — manual

```powershell
mkdir "$env:USERPROFILE\Projects" -Force
cd "$env:USERPROFILE\Projects"
git clone https://github.com/innersanctumentertainment/pajpys.git
cd pajpys
.\tools\install-local.ps1
mkdir "$env:USERPROFILE\Desktop\webs\pajpys" -Force
Copy-Item webs\pajpys\* "$env:USERPROFILE\Desktop\webs\pajpys\"
```

## Result

| What | Where |
|------|-------|
| Source code | `C:\Users\KyleGospel\Projects\pajpys` |
| Handoff / master prompt | `Desktop\webs\pajpys\` |
| Dev server | `.\tools\serve.ps1` → http://127.0.0.1:8095 |

## Free up this Cloud Agent chat

After local setup works, start a **new** Cloud Agent session for your next project. Paste `MASTER_PROMPT.md` into a **local** Cursor chat when you want to continue PAJPYS on your machine.

## Keep production in sync

From your local clone:

```powershell
git pull origin main
# make changes, commit, push
git push origin main
```

GitHub Actions deploys to https://pajpys.agapetech.org/ on push to `main`.
