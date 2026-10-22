# Kết quả tự kiểm thử — E-Learn Laravel v5

## Đã chạy trong môi trường tạo gói

- `php -l` trên tất cả các file PHP trong `app`, `bootstrap`, `config`, `database/migrations`, `database/seeders`, `public`, `routes`, `tests`: **không có lỗi cú pháp**.
- `python tests/preflight.py`: **PASS** — kiểm tra cú pháp PHP, JSON/XML cấu hình, CSRF token trong Blade, 7 đường dẫn API được gọi trực tiếp trong frontend, cú pháp JavaScript nhúng và hai truy vấn view báo cáo.
- `python tests/check_reporting_views.py`: **PASS** — thực thi hai biểu thức view với dữ liệu kiểm thử trên Python SQLite, xác nhận số sinh viên/bài tập/bài học/tiến độ và số bài nộp/đã chấm/chờ chấm. Đây chỉ là test cú pháp và aggregate cho SQL chung, không thay thế test MySQL.
- `composer.json` và `phpunit.xml` parse hợp lệ.
- Bộ kiểm thử regression của prototype v4 chạy lại: **25/25 test đạt**. Lưu ý các test này kiểm tra backend Python/SQLite của v4, không phải Laravel v5.

## Chưa thể chạy tại môi trường tạo gói

Không có Composer, không có extension `pdo_mysql`, không có MySQL client/server và không có `vendor/`. Vì vậy chưa chạy `composer install`, `php artisan migrate`, `php artisan test` hoặc kiểm thử trigger/view trên MySQL thật. Bộ test tính năng Laravel đã được tạo ở `tests/Feature/ElearnAuthAndDataTest.php`, cần chạy sau khi cài dependencies.

## Các bước xác minh bắt buộc trên máy chạy demo

```powershell
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
php artisan route:list
```

`migrate:fresh` xóa toàn bộ bảng trong database đã cấu hình, chỉ chạy với database demo/test. Nếu migration trigger báo lỗi, giữ nguyên log lỗi và kiểm tra phiên bản MySQL tối thiểu 8.0.16 trước khi tiếp tục.
