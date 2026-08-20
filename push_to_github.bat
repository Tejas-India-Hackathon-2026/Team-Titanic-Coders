@echo off
title RentNear - Hackathon Repository Submission
color 0b

echo ================================================================
echo      SUBMITTING RENTNEAR TO TEJAS INDIA HACKATHON 2026
echo      Repo: Team-Titanic-Coders
echo ================================================================
echo.

where git >nul 2>nul
if %errorlevel% neq 0 (
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

echo [1/4] Initializing Git...
git init
git add .
git commit -m "Submit RentNear - Online Property Rental Platform (Frontend + Backend + Database)"
git branch -M main

echo.
echo [2/4] Setting Remote Repository URL...
git remote remove origin 2>nul
git remote add origin https://github.com/Tejas-India-Hackathon-2026/Team-Titanic-Coders.git

echo.
echo [3/4] Pushing code to GitHub...
echo (If prompted, please sign in to your GitHub account)
git push -u origin main

echo.
echo ================================================================
echo [4/4] SUCCESS! Check your hackathon submission at:
echo https://github.com/Tejas-India-Hackathon-2026/Team-Titanic-Coders
echo ================================================================
pause
