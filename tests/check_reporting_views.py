"""Exercise the SQL text used by reporting-view migrations against Python SQLite.

This checks query syntax and aggregate intent, but is not a MySQL integration test.
"""
from pathlib import Path
import re, sqlite3
ROOT=Path(__file__).resolve().parents[1]
source=(ROOT/'database/migrations/2026_10_10_000008_create_reporting_views.php').read_text(encoding='utf-8')
statements=re.findall(r"DB::unprepared\('(CREATE VIEW .*?)'\);", source)
assert len(statements)==2, f'Expected 2 CREATE VIEW statements, got {len(statements)}'
db=sqlite3.connect(':memory:')
db.executescript('''
CREATE TABLE users(user_id INTEGER PRIMARY KEY, full_name TEXT NOT NULL);
CREATE TABLE courses(course_id INTEGER PRIMARY KEY, course_code TEXT, course_name TEXT, teacher_id INTEGER);
CREATE TABLE enrollments(course_id INTEGER, student_id INTEGER, progress INTEGER);
CREATE TABLE lessons(lesson_id INTEGER PRIMARY KEY, course_id INTEGER);
CREATE TABLE assignments(assignment_id INTEGER PRIMARY KEY, course_id INTEGER, title TEXT);
CREATE TABLE submissions(submission_id INTEGER PRIMARY KEY, assignment_id INTEGER, student_id INTEGER);
CREATE TABLE grades(grade_id INTEGER PRIMARY KEY, submission_id INTEGER);
INSERT INTO users VALUES(1,'Teacher');
INSERT INTO courses VALUES(1,'DB-01','Database',1),(2,'WEB-01','Web',1);
INSERT INTO enrollments VALUES(1,1,60),(1,2,80);
INSERT INTO lessons VALUES(1,1),(2,1),(3,2);
INSERT INTO assignments VALUES(1,1,'Assignment A'),(2,1,'Assignment B'),(3,2,'Assignment C');
INSERT INTO submissions VALUES(1,1,1),(2,1,2),(3,2,1);
INSERT INTO grades VALUES(1,1),(2,3);
''')
for sql in statements: db.execute(sql)
course=db.execute('SELECT student_count,assignment_count,lesson_count,avg_progress FROM v_course_summary WHERE course_id=1').fetchone()
assert course == (2,2,2,70.0), f'Unexpected course summary: {course}'
summary=db.execute('SELECT submission_count,graded_count,pending_count FROM v_submission_summary WHERE assignment_id=1').fetchone()
assert summary == (2,1,1), f'Unexpected submission summary: {summary}'
empty=db.execute('SELECT submission_count,graded_count,pending_count FROM v_submission_summary WHERE assignment_id=2').fetchone()
assert empty == (1,1,0), f'Unexpected graded-only summary: {empty}'
print('PASS: 2 reporting views execute and expected aggregates match in SQLite.')
print('NOTE: This validates the generic SQL expressions only, not MySQL-specific triggers or migration execution.')
