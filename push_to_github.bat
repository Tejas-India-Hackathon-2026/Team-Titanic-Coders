@echo off
title RentNear - GitHub Repository Sync
color 0b

echo ================================================================
echo      CONNECTING & PUSHING RENTNEAR TO GITHUB REPOSITORY
echo      Repo: Tejas-India-Hackathon-2026/Team-Titanic-Coders
echo ================================================================
echo.

set "GIT_CMD=C:\Users\kumar\AppData\Local\MinGit\cmd\git.exe"
if not exist "%GIT_CMD%" (
    where git >nul 2>nul
    if %errorlevel% equ 0 (
        set "GIT_CMD=git"
    ) else (
        echo [!] Git not found.
        echo Please use the GitHub Web upload method below:
        echo https://github.com/Tejas-India-Hackathon-2026/Team-Titanic-Coders/upload
        pause
        exit /b
    )
)

echo [1/3] Staging all files and committing...
"%GIT_CMD%" init
"%GIT_CMD%" add .
"%GIT_CMD%" commit -m "Update RentNear: Verified Owner Blue Tick, 1-Month Stays, Room Booking Token Payments, and Explore Map" 2>nul
"%GIT_CMD%" branch -M main

echo.
echo [2/3] Setting Remote Origin...
"%GIT_CMD%" remote remove origin 2>nul
"%GIT_CMD%" remote add origin https://github.com/Tejas-India-Hackathon-2026/Team-Titanic-Coders.git

echo.
echo [3/3] Pushing to GitHub...
echo.
echo NOTE: If a GitHub login window pops up, click "Sign in with your browser" to authorize!
echo.

"%GIT_CMD%" push -u origin main --force

echo.
echo ================================================================
echo Done! Check your live repository at:
echo https://github.com/Tejas-India-Hackathon-2026/Team-Titanic-Coders
echo ================================================================
pause
