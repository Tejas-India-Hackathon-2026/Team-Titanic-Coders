@echo off
title RentNear - GitHub Auto-Sync Background Daemon
color 0A

echo ========================================================
echo       RENTNEAR - GITHUB REAL-TIME AUTO-UPDATE DAEMON
echo ========================================================
echo.
echo Repo: https://github.com/Tejas-India-Hackathon-2026/Team-Titanic-Coders.git
echo Branch: main
echo.
echo [STATUS] Auto-Sync is ACTIVE. Watching for any file changes...
echo [INFO] Whenever you or the AI makes code changes, this tool
echo        will automatically Add, Commit, and Push to GitHub!
echo.
echo (Keep this window minimized to keep Auto-Update running)
echo ========================================================
echo.

set GIT_PATH=C:\Users\kumar\AppData\Local\MinGit\cmd\git.exe
if not exist "%GIT_PATH%" set GIT_PATH=git

:loop
timeout /t 10 /nobreak >nul

:: Check if git status has changes
"%GIT_PATH%" status --porcelain > "%TEMP%\git_status_check.txt" 2>&1
for %%A in ("%TEMP%\git_status_check.txt") do if %%~zA GTR 0 (
    echo.
    echo [%TIME%] Changes detected! Starting Auto-Sync to GitHub...
    "%GIT_PATH%" add .
    
    for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
    set TIMESTAMP=%datetime:~0,4%-%datetime:~4,2%-%datetime:~6,2% %datetime:~8,2%:%datetime:~10,2%:%datetime:~12,2%
    
    "%GIT_PATH%" commit -m "Auto-update: %TIMESTAMP% - Live Platform Sync"
    echo [%TIME%] Pushing commits to GitHub (origin main)...
    "%GIT_PATH%" push origin main
    
    if %ERRORLEVEL% EQU 0 (
        echo [%TIME%] [SUCCESS] Successfully synced all code to GitHub!
    ) else (
        echo [%TIME%] [NOTICE] Push queued or waiting for authentication.
    )
    echo --------------------------------------------------------
)

goto loop
