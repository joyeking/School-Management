-- ===========================================================================
-- EthioSIS demo / sample data
-- ---------------------------------------------------------------------------
-- Creates Grade 9 subjects, courses, class periods, six students, their
-- enrolment and their schedules — so testers have something to explore.
--
-- Safe to run more than once (idempotent). Targets school_id 1, year 2025.
--
-- Apply with:
--   mariadb -u root -p rosariosis < deploy/sample-data.sql
-- (inside Docker, from the deploy/ folder:
--   docker compose exec -T db sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" rosariosis' < sample-data.sql )
-- ===========================================================================

SET @syear   = 2025;
SET @school  = 1;
SET @teacher = 2;          -- staff_id of the 'teacher' account
SET @fy_mp   = 1;          -- marking_period_id 'Full Year'
SET @cal     = 1;          -- attendance calendar id
SET @grade9  = 10;         -- school_gradelevels.id for Grade 9 (1=KG, 2=G1 ... 10=G9)
SET @enroll  = 7;          -- 2025 'Beginning of Year' enrolment code
SET @start   = '2025-09-01';
-- SHA-512 crypt of the password 'student' (same scheme RosarioSIS uses)
SET @pwd     = '$6$72d74b418fb471b3$J9GHhXGnIh50/Cs/9jVuLePCdpzJm74hnNhK2VSGULGZz1v1YAez/se3/jei04adzcbro69/b925OEBom8W0m.';

-- 1. Subjects -------------------------------------------------------------
INSERT INTO course_subjects (subject_id, syear, school_id, title, short_name, sort_order) VALUES
	(101, @syear, @school, 'Mathematics', 'MATH', 1),
	(102, @syear, @school, 'English',     'ENG',  2),
	(103, @syear, @school, 'Biology',     'BIO',  3),
	(104, @syear, @school, 'History',     'HIST', 4)
ON DUPLICATE KEY UPDATE title = VALUES(title), short_name = VALUES(short_name);

-- 2. Courses (Grade 9) ----------------------------------------------------
INSERT INTO courses (course_id, syear, subject_id, school_id, grade_level, title, short_name, credit_hours) VALUES
	(101, @syear, 101, @school, '10', 'Mathematics', 'MATH9', 1),
	(102, @syear, 102, @school, '10', 'English',     'ENG9',  1),
	(103, @syear, 103, @school, '10', 'Biology',     'BIO9',  1),
	(104, @syear, 104, @school, '10', 'History',     'HIST9', 1)
ON DUPLICATE KEY UPDATE title = VALUES(title), grade_level = VALUES(grade_level);

-- 3. Class periods (one per course) ---------------------------------------
INSERT INTO course_periods
	(course_period_id, syear, school_id, course_id, title, short_name, mp, marking_period_id,
	 teacher_id, room, total_seats, filled_seats, does_attendance, does_honor_roll, does_class_rank, calendar_id, credits) VALUES
	(101, @syear, @school, 101, 'Mathematics', 'MATH9-1', '1', @fy_mp, @teacher, '101', 30, 0, 'Y', 'Y', 'Y', @cal, 1),
	(102, @syear, @school, 102, 'English',     'ENG9-1',  '1', @fy_mp, @teacher, '102', 30, 0, 'Y', 'Y', 'Y', @cal, 1),
	(103, @syear, @school, 103, 'Biology',     'BIO9-1',  '1', @fy_mp, @teacher, '103', 30, 0, 'Y', 'Y', 'Y', @cal, 1),
	(104, @syear, @school, 104, 'History',     'HIST9-1', '1', @fy_mp, @teacher, '104', 30, 0, 'Y', 'Y', 'Y', @cal, 1)
ON DUPLICATE KEY UPDATE title = VALUES(title), teacher_id = VALUES(teacher_id);

-- 4. Time slots (Period 1..4, Monday–Friday) ------------------------------
DELETE FROM course_period_school_periods WHERE course_period_id BETWEEN 101 AND 104;
INSERT INTO course_period_school_periods (course_period_id, period_id, days) VALUES
	(101, 4, 'MTWHF'),   -- period_id 4  = 'Period 1'
	(102, 5, 'MTWHF'),   -- period_id 5  = 'Period 2'
	(103, 6, 'MTWHF'),   -- period_id 6  = 'Period 3'
	(104, 7, 'MTWHF');   -- period_id 7  = 'Period 4'

-- 5. Students (Grade 9) ---------------------------------------------------
INSERT INTO students (student_id, last_name, first_name, middle_name, username, password, custom_200000000, custom_200000004) VALUES
	(101, 'Kebede', 'Abebe',  'A', 'abebe',  @pwd, 'Male',   '2011-03-14'),
	(102, 'Alemu',  'Tigist', 'T', 'tigist', @pwd, 'Female', '2011-07-22'),
	(103, 'Bekele', 'Dawit',  'D', 'dawit',  @pwd, 'Male',   '2011-01-09'),
	(104, 'Girma',  'Hanna',  'H', 'hanna',  @pwd, 'Female', '2011-11-30'),
	(105, 'Tesfaye','Yonas',  'Y', 'yonas',  @pwd, 'Male',   '2011-05-18'),
	(106, 'Haile',  'Selam',  'S', 'selam',  @pwd, 'Female', '2011-09-02')
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name);

-- 6. Enrolment (Grade 9, beginning of year) -------------------------------
INSERT INTO student_enrollment
	(id, syear, school_id, student_id, grade_id, start_date, enrollment_code, calendar_id, next_school, last_school) VALUES
	(101, @syear, @school, 101, @grade9, @start, @enroll, @cal, @school, @school),
	(102, @syear, @school, 102, @grade9, @start, @enroll, @cal, @school, @school),
	(103, @syear, @school, 103, @grade9, @start, @enroll, @cal, @school, @school),
	(104, @syear, @school, 104, @grade9, @start, @enroll, @cal, @school, @school),
	(105, @syear, @school, 105, @grade9, @start, @enroll, @cal, @school, @school),
	(106, @syear, @school, 106, @grade9, @start, @enroll, @cal, @school, @school)
ON DUPLICATE KEY UPDATE grade_id = VALUES(grade_id), start_date = VALUES(start_date);

-- 7. Schedules (each student takes all four periods) ----------------------
DELETE FROM schedule WHERE student_id BETWEEN 101 AND 106;
INSERT INTO schedule (syear, school_id, student_id, start_date, course_id, course_period_id, mp, marking_period_id)
SELECT @syear, @school, s.student_id, @start, cp.course_id, cp.course_period_id, '1', @fy_mp
FROM students s
CROSS JOIN course_periods cp
WHERE s.student_id BETWEEN 101 AND 106
  AND cp.course_period_id BETWEEN 101 AND 104;
