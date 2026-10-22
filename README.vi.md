# E-Learn 3cayxanh v5 — Laravel 13 + MySQL

Bản triển khai Laravel thay thế backend Python/SQLite ở v4. Giao diện UI/UX hiện tại đã được chuyển vào Blade view và gọi API Laravel; database quan hệ dùng MySQL. Mô hình được thiết kế theo đề tài **Hệ thống E-learning hỗ trợ học tập, giảng dạy từ xa cho sinh viên và giảng viên**.

## Tình trạng kiểm thử

- Các file PHP đã được kiểm tra cú pháp bằng `php -l` trong môi trường tạo gói.
- Script JavaScript và cấu trúc frontend được kiểm tra tĩnh.
- Bộ test tính năng nằm tại `tests/Feature/ElearnAuthAndDataTest.php`.
- **Chưa thể chạy Composer/Laravel hoặc MySQL thật trong môi trường tạo gói** vì môi trường này không có Composer, driver `pdo_mysql` hay MySQL server. Vì vậy bản này là source Laravel có thể cài đặt, không được tuyên bố đã được integration-test trên MySQL. Sau khi cài đủ phần mềm, hãy chạy test và migration theo hướng dẫn dưới đây.

Laravel 13 yêu cầu PHP 8.3 trở lên. Tham khảo yêu cầu chính thức tại https://laravel.com/docs/13.x/deployment và ghi chú phiên bản tại https://github.com/laravel/docs/blob/13.x/releases.md.

## 1. Phần mềm cần có trên Windows

1. PHP 8.3+ và các extension: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `xml`, `ctype`, `tokenizer`, `curl`.
2. Composer 2.
3. MySQL 8.0.16+ đang chạy.
4. Chrome hoặc trình duyệt tương đương.

Kiểm tra PHP:

```powershell
php -v
php -m
composer --version
```

Đảm bảo `php -m` có `pdo_mysql`.

## 2. Tạo database MySQL

Mở MySQL client hoặc Workbench và chạy:

```sql
CREATE DATABASE elearn_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Sửa `.env.example` thành `.env` (script chạy cũng tự sao chép nếu chưa có). Cấu hình đúng tên user/password MySQL trong `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=elearn_db
DB_USERNAME=root
DB_PASSWORD=mat_khau_mysql_cua_may
```

Không dùng dấu ngoặc nhọn; thay mật khẩu ví dụ bằng mật khẩu thực trên máy cá nhân.

## 3. Cài đặt và khởi chạy

Tại thư mục project:

```powershell
composer install
php artisan key:generate
php artisan migrate --seed
php artisan route:list
php artisan serve --host=127.0.0.1 --port=8000
```

Hoặc nhấp đúp `start_windows.bat`. Mở `http://127.0.0.1:8000`.

## 4. Tài khoản demo

Mật khẩu cho tất cả tài khoản mẫu: `Demo123!`.

| Vai trò | Mã đăng nhập | Email |
|---|---|---|
| Sinh viên | `2380600984` | `khang.nguyen@hutech.edu.vn` |
| Giảng viên | `GV001` | `minhanh@hutech.edu.vn` |
| Quản trị viên | `ADMIN01` | `admin@elearn.local` |

Đây là tài khoản giả lập, chỉ sử dụng cho đồ án cục bộ.

## 5. Database và ràng buộc

Nguồn schema chính thức của ứng dụng là các Laravel migrations trong `database/migrations`:

- `users`: tài khoản, mã người dùng, email, mật khẩu hash, vai trò và trạng thái.
- `courses`: lớp học phần và giảng viên phụ trách.
- `enrollments`: bảng trung gian đăng ký sinh viên-lớp học phần; `UNIQUE(course_id, student_id)` ngăn đăng ký trùng.
- `lessons`: bài học và thứ tự trong lớp học phần.
- `assignments`: bài tập, hạn nộp, trạng thái và điểm tối đa.
- `submissions`: bài nộp, tối đa một bản hiện hành mỗi sinh viên/mỗi bài tập.
- `grades`: điểm, nhận xét, liên kết bài nộp hoặc đầu điểm thủ công.
- `v_course_summary`, `v_submission_summary`: view thống kê dùng trong trang báo cáo.
- Migration integrity bổ sung CHECK constraints và trigger phía MySQL cho vai trò giảng viên/sinh viên, đăng ký trước khi nộp bài và tính hợp lệ của điểm.

`database/schema.mysql.sql` là tài liệu tham khảo gọn cho bảng; dùng `php artisan migrate --seed` để tạo đủ bảng, views và triggers. `database/ERD.md` mô tả các thực thể quan hệ. `design-system_MASTER.md` là hệ thống thiết kế UI/UX đã tham khảo theo tài liệu UI UX Pro Max.

## 6. Kiểm thử

Trước khi cài framework, có thể chạy kiểm tra tĩnh không phụ thuộc Composer:

```powershell
python tests/preflight.py
```

Sau khi `composer install` và cấu hình DB test, chạy:

```powershell
php artisan test
php artisan route:list
php artisan migrate:fresh --seed
```

Bộ test hiện bao gồm xác thực, phân quyền xem dữ liệu, cấm sinh viên truy cập điểm của người khác, giới hạn quyền báo cáo, và kiểm tra ràng buộc đăng ký. Test dùng SQLite in-memory; để chạy được cần extension `pdo_sqlite`. MySQL migration/trigger cần được thử riêng trên MySQL thật:

```powershell
php artisan migrate:fresh --seed
php artisan test
```

**Lưu ý:** `migrate:fresh` xóa toàn bộ bảng ứng dụng trên database đã cấu hình. Chỉ chạy với database test/demo, tuyệt đối không chạy vào dữ liệu quan trọng.

## 7. Giới hạn

- Bản này chưa được chạy qua Composer vì không có Composer trong môi trường tạo file. Do đó cần cài dependencies và chạy test lần đầu trên máy có PHP/Composer/MySQL.
- Snapshot API được giữ để frontend prototype có thể dùng lại; thao tác ghi được xử lý bằng transaction và lọc theo vai trò. Đây là thiết kế cho đồ án, chưa phù hợp cho chỉnh sửa đồng thời nhiều người với độ tin cậy cao.
- Upload tệp thật, video conference, khôi phục mật khẩu, email verification, audit log và triển khai Internet chưa nằm trong phạm vi hiện tại.
