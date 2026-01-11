@echo off
echo Starting ngrok tunnel for FATUCKS Inventory System on port 8080...
echo.
echo Make sure XAMPP Apache is running on port 8080
echo.
ngrok http 8080
pause