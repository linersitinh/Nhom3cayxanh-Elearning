# E-Learn – Design System Master

## Product assumptions
- Sản phẩm: LMS cho sinh viên, giảng viên và quản trị viên đại học.
- Công việc chính của sinh viên: tìm lớp học, đọc bài học, nộp bài, xem điểm.
- Công việc chính của giảng viên: quản lý lớp, giao bài, đọc bài nộp, chấm điểm.
- Công việc chính của quản trị viên: quản lý danh tính và xem báo cáo dữ liệu.
- Ngữ cảnh sử dụng: laptop phòng học, trình duyệt trên máy cá nhân, điện thoại ở mức xem/thao tác cơ bản.
- Giai đoạn hiện tại: prototype HTML/CSS/JS, dữ liệu chỉ nằm trong `localStorage`.

## Design direction
- Phong cách: SaaS giáo dục gọn gàng, tập trung nội dung, bề mặt sáng và độ tương phản cao.
- Tránh: gradient tím/hồng kiểu AI, glassmorphism trang trí, hiệu ứng gây xao nhãng, emoji làm icon điều hướng.
- Điều hướng desktop: sidebar tối có nhãn; mobile: thanh điều hướng ngang có nhãn, cuộn trong vùng điều hướng chứ không gây cuộn ngang toàn trang.
- Mỗi màn hình chỉ có một CTA chính; hành động phụ ít nổi bật hơn.

## Tokens
- `--navy: #172344` – sidebar / chrome chính.
- `--blue: #4056D6` – hành động chính và trạng thái đang chọn.
- `--ink: #18243D` – nội dung chính.
- `--muted: #59657C` – nội dung phụ có độ tương phản tốt hơn.
- `--bg: #F5F7FB` – nền trang.
- `--line: #E2E7F0` – đường viền và phân cách.
- `--green: #157B5C` – trạng thái thành công.
- `--orange: #9B5C0B` – chờ xử lý/cảnh báo.
- `--red: #B42332` – lỗi hoặc hành động nguy hiểm.

## Typography & layout
- System font stack, ưu tiên khả năng hiển thị tiếng Việt ổn định và tránh phụ thuộc CDN.
- Nội dung cơ bản 15px desktop; 14–16px cho nội dung chính trên điện thoại.
- Nội dung phụ và metadata mặc định >= 11–12px, không dùng chữ siêu nhỏ cho thông tin thiết yếu.
- Nhịp khoảng cách theo bội số 4/8px; card radius 14–16px; một thang đổ bóng nhẹ.
- Breakpoints kiểm tra: 375px, 420px, 760px, 1150px, 1440px.

## Interaction and accessibility
- Điều hướng sử dụng button thật và có `aria-current`.
- Phím Tab phải hiển thị focus; Enter/Space mở thẻ khóa học; Escape đóng modal/menu.
- Modal có `role=dialog`, `aria-modal`, tên truy cập và focus được chuyển vào/khôi phục sau khi đóng.
- Nút icon có accessible name; icon trang trí dùng SVG và `aria-hidden`.
- Biểu mẫu có label liên kết bằng `for`/`id`; validate trường ngay tại chỗ thông qua HTML constraints và thông báo ngữ cảnh.
- Không dựa vào màu sắc duy nhất để phân biệt trạng thái: badge luôn có chữ trạng thái.
- Hỗ trợ `prefers-reduced-motion`; animation chỉ dùng ngắn cho phản hồi tương tác.
- Kích thước mục tiêu bấm khoảng 40–46px; spacing điều khiển đủ rộng.

## Responsive rules
- 1150px trở xuống: giảm gutter, dùng một cột cho layout chính và giữ thống kê hai cột.
- 760px trở xuống: chuyển sidebar thành navigation ngang phía trên, ẩn thông tin người dùng thứ cấp, modal dùng gần toàn màn hình.
- 420px trở xuống: thẻ khóa học một cột; các nhóm thống kê và CTA không tràn màn hình.
- Bảng dài được phép cuộn bên trong `.tablewrap`; không cho toàn trang cuộn ngang.

## Known limitations / next iteration
- Tạm dùng HTML template literal và `localStorage`; cần server-side authorization khi chuyển Laravel.
- Các giá trị thống kê mẫu một số nơi vẫn được giả lập và cần được chuyển thành truy vấn database thật.
- Cần chạy kiểm tra trực quan thủ công ở các viewport mục tiêu khi mở trong Chrome trên máy người dùng.
