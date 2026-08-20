@echo off
title RentNear - Online Property Rental Platform
color 0b

echo ================================================================
echo           RENTNEAR - ONLINE PROPERTY RENTAL PLATFORM
echo ================================================================
echo.
echo Starting RentNear Application Server...
echo.

set PHP_CMD=php

if exist "C:\xampp\php\php.exe" (
    set PHP_CMD=C:\xampp\php\php.exe
)

echo Using PHP: %PHP_CMD%
echo.
echo ----------------------------------------------------------------
echo   Server URL : http://localhost:8000
echo   Demo Owner : owner@rentnear.com / owner123
echo   Demo Renter: renter@rentnear.com / renter123
echo   Demo Admin : admin@rentnear.com / admin123
echo ----------------------------------------------------------------
echo.
echo Opening browser in 2 seconds...
start "" "http://localhost:8000"

%PHP_CMD% -S localhost:8000
pause
