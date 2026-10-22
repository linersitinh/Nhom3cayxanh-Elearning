# Hướng dẫn phát triển E-Learn 3cayxanh

## Stack
- Laravel 13, PHP 8.3+, MySQL 8.0.16+.
- Giao diện hiện dùng Blade view chứa HTML/CSS/JavaScript; không cần NPM build ở phiên bản này.
- Migrations là nguồn sự thật cho schema. `database/schema.mysql.sql` là tài liệu tham khảo; nếu sửa schema, cập nhật cả migration và sơ đồ ERD.

## Quy tắc quan trọng
- Không bỏ xác thực/session của Laravel; mọi request thay đổi trạng thái phải đi qua middleware CSRF `web`.
- Quyền truy cập được kiểm tra phía server, không dựa vào biến vai trò trong JavaScript.
- Không trả password/hash/remember token trong JSON.
- Mọi thao tác đa bảng cần dùng `DB::transaction`.
- Ràng buộc cốt lõi cần có cả xác thực ứng dụng và constraint/foreign key ở MySQL khi khả thi.
- Các test nghiệp vụ phải bao gồm sinh viên, giảng viên, quản trị viên và các trường hợp bị từ chối.

## UI/UX
Đọc `design-system_MASTER.md` trước khi sửa UI. Ưu tiên ngôn ngữ giao diện tiếng Việt, responsive ở 375/768/1024/1440 px, focus bàn phím rõ ràng, nhãn form gắn với input, độ tương phản dễ đọc, tôn trọng `prefers-reduced-motion`, không dùng emoji làm icon chức năng.

## Kiểm thử
- `python tests/preflight.py`: lint PHP + kiểm tra cấu hình + JS syntax (cần PHP CLI và Node.js).
- `composer install` rồi `php artisan test`: test tính năng.
- Với database: `php artisan migrate:fresh --seed` chỉ chạy vào database demo/test vì lệnh này xóa dữ liệu.
- Không được tuyên bố kiểm thử Laravel/MySQL thành công nếu chưa chạy trên môi trường có Composer, `pdo_mysql` và MySQL thật.
