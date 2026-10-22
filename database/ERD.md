# ERD — Hệ thống E-learning (Laravel + MySQL v5)

> Đây là mô hình logic đang được triển khai trong các Laravel migrations. Cần tiếp tục đối chiếu với yêu cầu chính thức của giảng viên trước khi chốt báo cáo học thuật.

```mermaid
erDiagram
    USERS ||--o{ COURSES : "giang vien phu trach"
    USERS ||--o{ ENROLLMENTS : "sinh vien dang ky"
    COURSES ||--o{ ENROLLMENTS : "co sinh vien"
    COURSES ||--o{ LESSONS : "gom bai hoc"
    COURSES ||--o{ ASSIGNMENTS : "co bai tap"
    ASSIGNMENTS ||--o{ SUBMISSIONS : "nhan bai nop"
    USERS ||--o{ SUBMISSIONS : "nop bai"
    USERS ||--o{ GRADES : "nhan diem"
    COURSES ||--o{ GRADES : "co dau diem"
    ASSIGNMENTS o|--o{ GRADES : "co the gan diem"
    SUBMISSIONS o|--o| GRADES : "duoc cham diem"

    USERS {
        bigint user_id PK
        varchar full_name
        varchar user_code UK
        varchar email UK
        varchar password "hash"
        enum role
        varchar status
    }
    COURSES {
        bigint course_id PK
        varchar course_code UK
        varchar course_name
        bigint teacher_id FK
        varchar term
        varchar category
        text description
        varchar color
        varchar symbol
    }
    ENROLLMENTS {
        bigint enrollment_id PK
        bigint course_id FK
        bigint student_id FK
        tinyint progress
        timestamp enrolled_at
    }
    LESSONS {
        bigint lesson_id PK
        bigint course_id FK
        varchar title
        varchar duration
        varchar lesson_type
        int lesson_order
    }
    ASSIGNMENTS {
        bigint assignment_id PK
        bigint course_id FK
        varchar title
        varchar assignment_type
        date due_date
        decimal max_points
        boolean is_open
        text description
    }
    SUBMISSIONS {
        bigint submission_id PK
        bigint assignment_id FK
        bigint student_id FK
        timestamp submitted_at
        mediumtext answer
    }
    GRADES {
        bigint grade_id PK
        bigint student_id FK
        bigint course_id FK
        bigint assignment_id FK "nullable"
        bigint submission_id FK_UK "nullable"
        varchar work_title
        decimal score
        decimal max_score
        text feedback
    }
```

## Các quan hệ và ràng buộc chính

1. `USERS` có ba vai trò: `student`, `teacher`, `admin`. Mật khẩu được Laravel hash trong cột `users.password`; JSON không trả về hash hoặc remember token.
2. Một giảng viên có thể phụ trách nhiều lớp học phần; mỗi lớp có một giảng viên chính (`courses.teacher_id`). Trigger MySQL kiểm tra người phụ trách có role `teacher`.
3. Sinh viên và lớp học phần có quan hệ N–N qua `ENROLLMENTS`; `UNIQUE(course_id, student_id)` ngăn đăng ký trùng. Trigger MySQL kiểm tra người đăng ký có role `student`; `CHECK(progress BETWEEN 0 AND 100)` giới hạn tiến độ.
4. `LESSONS` lưu nội dung và thứ tự trong một lớp học phần.
5. `ASSIGNMENTS` thuộc một lớp học phần; `CHECK(max_points > 0 AND max_points <= 1000)` kiểm tra điểm tối đa.
6. `SUBMISSIONS` ghi bài nộp; `UNIQUE(assignment_id, student_id)` giới hạn một bài nộp hiện hành cho mỗi sinh viên và bài tập. Trigger kiểm tra sinh viên đã đăng ký lớp của bài tập.
7. `GRADES` có thể là điểm thủ công (`assignment_id` và `submission_id` rỗng) hoặc điểm gắn với bài nộp. `submission_id` là khóa ngoại duy nhất; trigger MySQL kiểm tra sự nhất quán giữa sinh viên, khóa học, bài tập, bài nộp và khoảng điểm.

## View báo cáo

- `v_course_summary`: sĩ số, số bài tập, số bài học và tiến độ học trung bình theo lớp học phần.
- `v_submission_summary`: số bài đã nộp, đã chấm và còn chờ chấm theo bài tập.

Các quan hệ, khóa ngoại, chỉ mục, view, CHECK constraints và trigger được khai báo trong `database/migrations`. `database/schema.mysql.sql` là bản tham khảo gọn cho các bảng cốt lõi; nên cài đặt bằng `php artisan migrate --seed` để khởi tạo đủ schema.
