@echo off
title RentNear - Hackathon Repository Submission
color 0b

echo ================================================================
echo      SUBMITTING RENTNEAR TO TEJAS INDIA HACKATHON 2026
echo      Repo: Team-Titanic-Coders
echo ================================================================
echo.

set "GIT_CMD=git"
where git >nul 2>nul
if %errorlevel% neq 0 (
    if exist "C:\Users\kumar\AppData\Local\MinGit\cmd\git.exe" (
        set "GIT_CMD=C:\Users\kumar\AppData\Local\MinGit\cmd\git.exe"
    ) else (
        echo [!] Git is not installed in your PATH.
        echo.
        echo Easiest Way to Upload:
        echo 1. Open: https://github.com/Tejas-India-Hackathon-2026/Team-Titanic-Coders/upload
        echo 2. Drag and drop all files from this folder into the browser!
        echo 3. Click "Commit changes".
        echo.
        pause
        exit /b
    )
)

echo [1/4] Staging changes and committing...
"%GIT_CMD%" init
"%GIT_CMD%" add .
"%GIT_CMD%" commit -m "Fix GitHub Actions: Add composer.json, Explore Map, and Bachelor features"
"%GIT_CMD%" branch -M main

echo.
echo [2/4] Setting Remote Repository URL...
"%GIT_CMD%" remote remove origin 2>nul
"%GIT_CMD%" remote add origin https://github.com/Tejas-India-Hackathon-2026/Team-Titanic-Coders.git

echo.
echo [3/4] Pushing code to GitHub...
echo (If prompted, please sign in with your GitHub username/token in the browser or terminal)
"%GIT_CMD%" push -u origin main --force

echo.
echo ================================================================
echo [4/4] SUCCESS! Check your hackathon submission at:
echo https://github.com/Tejas-India-Hackathon-2026/Team-Titanic-Coders
echo ================================================================
pause
