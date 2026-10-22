@echo off
setlocal
cd /d "%~dp0"
where php >nul 2>nul || (echo [LOI] Chua tim thay PHP. Cai PHP 8.3+ va them vao PATH.& pause & exit /b 1)
where composer >nul 2>nul || (echo [LOI] Chua tim thay Composer. Cai Composer truoc khi chay.& pause & exit /b 1)
if not exist .env copy .env.example .env >nul
if not exist vendor\autoload.php (
  echo Dang cai thu vien Laravel...
  composer install
  if errorlevel 1 (pause & exit /b 1)
)
findstr /C:"APP_KEY=base64:" .env >nul || php artisan key:generate
php artisan migrate --seed
if errorlevel 1 (echo [LOI] Migration that bai. Kiem tra MySQL va file .env.& pause & exit /b 1)
echo E-Learn dang chay tai http://127.0.0.1:8000
echo Giu cua so nay mo trong luc demo. Nhan Ctrl+C de dung.
php artisan serve --host=127.0.0.1 --port=8000
