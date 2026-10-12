-- ============================================================================
--  EthioSIS — Ethiopia localization patch  (MySQL / MariaDB)
-- ----------------------------------------------------------------------------
--  Adapts a fresh RosarioSIS 12.x installation to the Ethiopian school system:
--    * School identity & contact details
--    * Currency (ETB / Birr), product name & title
--    * Grade levels: Kindergarten + Grades 1 to 12 (adds 9-12)
--    * Marking periods on the Ethiopian calendar year (September - June)
--    * Ethiopian letter grade scale (A/B/C/D/F)
--    * Student fields: removes US Social Security, replaces US Ethnicity
--      categories with Ethiopian Regions, renames Language -> Mother Tongue
--    * Ethiopian student enrollment codes (transferred in/out, dropped out...)
--
--  Safe to run more than once. Gregorian dates are kept in the database; the
--  Ethiopian calendar is only a display layer (see functions/EthiopianCalendar.fnc.php).
--
--  Apply with:
--    mysql -u root rosariosis < ethiopia.sql
--  or in phpMyAdmin / Adminer import.
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- 1. School identity
-- ----------------------------------------------------------------------------
ALTER TABLE schools MODIFY state VARCHAR(50);

UPDATE schools
SET title        = 'Addis Ababa Secondary School',
    short_name   = 'AASS',
    address      = 'Bole Sub-City, Woreda 03',
    city         = 'Addis Ababa',
    state        = 'Addis Ababa',
    zipcode      = '1000',
    phone        = '+251 11 557 0000',
    principal    = 'Mr. Principal',
    www_address  = 'www.example.edu.et',
    school_number = 'ET-AA-001'
WHERE id = 1
  AND (title = 'Default School' OR title LIKE '%School%' OR title IS NOT NULL);

-- Create the matching school row for the new school year (2026/2027).
-- Several tables use a composite (school_id, syear) foreign key.
INSERT IGNORE INTO schools
    (syear, id, title, address, city, state, zipcode, phone, principal, www_address, school_number, short_name, reporting_gp_scale, number_days_rotation)
SELECT 2026, id, title, address, city, state, zipcode, phone, principal, www_address, school_number, short_name, reporting_gp_scale, number_days_rotation
FROM schools
WHERE syear = 2025 AND id = 1;

-- ----------------------------------------------------------------------------
-- 2. Configuration: currency, names
-- ----------------------------------------------------------------------------
UPDATE config SET config_value = 'Br'   WHERE title = 'CURRENCY';
UPDATE config SET config_value = 'EthioSIS' WHERE title = 'NAME';
UPDATE config SET config_value = 'Ethiopian Student Information System' WHERE title = 'TITLE';
UPDATE config SET config_value = 'EthioSIS' WHERE title = 'THEME';
UPDATE config SET config_value = '.'    WHERE title = 'DECIMAL_SEPARATOR';
UPDATE config SET config_value = ','    WHERE title = 'THOUSANDS_SEPARATOR';

-- Skip the "Confirm Successful Installation" first-login wizard + optional
-- rosariosis.org poll. That wizard also rewrites the admin password, which is
-- the usual cause of an "Incorrect username or password" right after setup.
-- To get the wizard back, set LOGIN = 'No' (School Setup > Configuration).
UPDATE config SET config_value = 'Yes' WHERE title = 'LOGIN';
UPDATE config SET config_value = NULL  WHERE title = 'FORCE_PASSWORD_CHANGE_ON_FIRST_LOGIN';

-- ----------------------------------------------------------------------------
-- 3. Grade levels: Kindergarten + Grades 1..12
-- ----------------------------------------------------------------------------
INSERT INTO school_gradelevels (school_id, short_name, title, sort_order)
SELECT 1, '09', 'Grade 9', 10 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1) x WHERE EXISTS
    (SELECT 1 FROM school_gradelevels g WHERE g.school_id = 1 AND g.short_name = '09'));

INSERT INTO school_gradelevels (school_id, short_name, title, sort_order)
SELECT 1, '10', 'Grade 10', 11 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM school_gradelevels WHERE school_id = 1 AND short_name = '10');

INSERT INTO school_gradelevels (school_id, short_name, title, sort_order)
SELECT 1, '11', 'Grade 11', 12 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM school_gradelevels WHERE school_id = 1 AND short_name = '11');

INSERT INTO school_gradelevels (school_id, short_name, title, sort_order)
SELECT 1, '12', 'Grade 12', 13 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM school_gradelevels WHERE school_id = 1 AND short_name = '12');

-- Normalise titles
UPDATE school_gradelevels SET title = 'Kindergarten' WHERE school_id = 1 AND short_name = 'KG';
UPDATE school_gradelevels
SET title = CONCAT('Grade ', CAST(short_name AS UNSIGNED))
WHERE school_id = 1 AND short_name REGEXP '^[0-9]+$';

-- Rebuild the "next grade" chain from sort order
UPDATE school_gradelevels g
JOIN (
    SELECT a.id AS id, b.id AS next_id
    FROM school_gradelevels a
    LEFT JOIN school_gradelevels b
        ON b.school_id = a.school_id AND b.sort_order = a.sort_order + 1
    WHERE a.school_id = 1
) m ON m.id = g.id
SET g.next_grade_id = m.next_id
WHERE g.school_id = 1;

-- ----------------------------------------------------------------------------
-- 4. Marking periods — Ethiopian school year (September to June)
--    Quarter 1 & 2 belong to Semester 1; Quarter 3 & 4 to Semester 2.
-- ----------------------------------------------------------------------------

-- 4a. Re-date the existing (2025/2026) year
UPDATE school_marking_periods SET start_date = '2025-09-01', end_date = '2026-06-30' WHERE mp = 'FY';
UPDATE school_marking_periods SET start_date = '2025-09-01', end_date = '2026-01-31' WHERE mp = 'SEM' AND sort_order = 1;
UPDATE school_marking_periods SET start_date = '2026-02-01', end_date = '2026-06-30' WHERE mp = 'SEM' AND sort_order = 2;
UPDATE school_marking_periods SET start_date = '2025-09-01', end_date = '2025-10-31' WHERE mp = 'QTR' AND sort_order = 1;
UPDATE school_marking_periods SET start_date = '2025-11-01', end_date = '2026-01-31' WHERE mp = 'QTR' AND sort_order = 2;
UPDATE school_marking_periods SET start_date = '2026-02-01', end_date = '2026-03-31' WHERE mp = 'QTR' AND sort_order = 3;
UPDATE school_marking_periods SET start_date = '2026-04-01', end_date = '2026-06-30' WHERE mp = 'QTR' AND sort_order = 4;

-- 4b. Create the 2026/2027 year so the application is "current"
INSERT IGNORE INTO school_marking_periods
    (marking_period_id, syear, mp, school_id, parent_id, title, short_name, sort_order, start_date, end_date, does_grades, does_comments)
VALUES
    (8,  2026, 'FY',  1, NULL, 'Full Year',   'FY', 1, '2026-09-01', '2027-06-30', NULL, NULL),
    (9,  2026, 'SEM', 1, 8,    'Semester 1',  'S1', 1, '2026-09-01', '2027-01-31', NULL, NULL),
    (10, 2026, 'SEM', 1, 8,    'Semester 2',  'S2', 2, '2027-02-01', '2027-06-30', NULL, NULL),
    (11, 2026, 'QTR', 1, 9,    'Quarter 1',   'Q1', 1, '2026-09-01', '2026-10-31', 'Y', 'Y'),
    (12, 2026, 'QTR', 1, 9,    'Quarter 2',   'Q2', 2, '2026-11-01', '2027-01-31', 'Y', 'Y'),
    (13, 2026, 'QTR', 1, 10,   'Quarter 3',   'Q3', 3, '2027-02-01', '2027-03-31', 'Y', 'Y'),
    (14, 2026, 'QTR', 1, 10,   'Quarter 4',   'Q4', 4, '2027-04-01', '2027-06-30', 'Y', 'Y');

-- ----------------------------------------------------------------------------
-- 5. Ethiopian grading scale: A 90, B 80, C 70, D 60, F fail
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO report_card_grade_scales
    (id, syear, school_id, title, comment, sort_order, gp_scale, gp_passing_value)
VALUES
    (2, 2026, 1, 'Main', NULL, 1, 4.00, 0.00);

DELETE FROM report_card_grades WHERE school_id = 1 AND syear IN (2025, 2026);

INSERT INTO report_card_grades
    (syear, school_id, title, sort_order, gpa_value, break_off, comment, grade_scale_id)
VALUES
    -- 2025/2026 scale (id 1)
    (2025, 1, 'A',   1, 4.00, 90.00, 'Excellent',    1),
    (2025, 1, 'B',   2, 3.00, 80.00, 'Very good',    1),
    (2025, 1, 'C',   3, 2.00, 70.00, 'Good',         1),
    (2025, 1, 'D',   4, 1.00, 60.00, 'Satisfactory', 1),
    (2025, 1, 'F',   5, 0.00,  0.00, 'Fail',         1),
    (2025, 1, 'I',   6, NULL,  NULL, 'Incomplete',   1),
    (2025, 1, 'N/A', 7, NULL,  NULL, NULL,           1),
    -- 2026/2027 scale (id 2)
    (2026, 1, 'A',   1, 4.00, 90.00, 'Excellent',    2),
    (2026, 1, 'B',   2, 3.00, 80.00, 'Very good',    2),
    (2026, 1, 'C',   3, 2.00, 70.00, 'Good',         2),
    (2026, 1, 'D',   4, 1.00, 60.00, 'Satisfactory', 2),
    (2026, 1, 'F',   5, 0.00,  0.00, 'Fail',         2),
    (2026, 1, 'I',   6, NULL,  NULL, 'Incomplete',   2),
    (2026, 1, 'N/A', 7, NULL,  NULL, NULL,           2);

-- ----------------------------------------------------------------------------
-- 6. Student fields: drop US Social Security, localise Region & Mother Tongue
-- ----------------------------------------------------------------------------
DELETE FROM custom_fields WHERE id = 200000003; /* Social Security */
ALTER TABLE students DROP COLUMN IF EXISTS custom_200000003;
ALTER TABLE staff    DROP COLUMN IF EXISTS custom_200000003;

UPDATE custom_fields
SET title = 'Region',
    select_options = 'Addis Ababa\nAfar\nAmhara\nBenishangul-Gumuz\nCentral Ethiopia\nDire Dawa\nGambella\nHarari\nOromia\nSidama\nSomali\nSouth Ethiopia\nSouth West Ethiopia\nTigray\nOther'
WHERE id = 200000001;

UPDATE custom_fields
SET title = 'Mother Tongue',
    select_options = 'Afar\nAmharic\nAnuak\nArgobba\nAwngi\nBasketo\nBench\nBerta\nBoren\nBurji\nDasenech\nDawro\nGamo\nGedeo\nGurage\nHadiyya\nHarari\nIrob\nKafa\nKonso\nKunama\nKistane\nMaale\nMajang\nMe''en\nMursi\nNayi\nNuer\nNyangatom\nOromo\nQimant\nSaho\nSebat Bet Gurage\nShinasha\nSidaama\nSilt''e\nSomali\nTigrinya\nTsamai\nWolaytta\nXamtanga\nYemsa\nZay\nEnglish\nOther'
WHERE id = 200000005;

-- ----------------------------------------------------------------------------
-- 7. Ethiopian student enrollment codes
-- ----------------------------------------------------------------------------
DELETE FROM student_enrollment_codes WHERE syear IN (2025, 2026);

INSERT INTO student_enrollment_codes (syear, title, short_name, type, default_code, sort_order) VALUES
    (2025, 'Beginning of Year', 'EBY',  'Add',  'Y', 1),
    (2025, 'Transferred In',    'TIN',  'Add',  NULL, 2),
    (2025, 'Re-enrolled',       'REEN', 'Add',  NULL, 3),
    (2025, 'Transferred Out',   'TOUT', 'Drop', NULL, 4),
    (2025, 'Dropped Out',       'DROP', 'Drop', NULL, 5),
    (2025, 'Graduated',         'GRAD', 'Drop', NULL, 6),
    (2025, 'Expelled',          'EXP',  'Drop', NULL, 7),
    (2026, 'Beginning of Year', 'EBY',  'Add',  'Y', 1),
    (2026, 'Transferred In',    'TIN',  'Add',  NULL, 2),
    (2026, 'Re-enrolled',       'REEN', 'Add',  NULL, 3),
    (2026, 'Transferred Out',   'TOUT', 'Drop', NULL, 4),
    (2026, 'Dropped Out',       'DROP', 'Drop', NULL, 5),
    (2026, 'Graduated',         'GRAD', 'Drop', NULL, 6),
    (2026, 'Expelled',          'EXP',  'Drop', NULL, 7);

-- ----------------------------------------------------------------------------
-- 8. Preparatory streams (Grades 11-12)
--    Adds a "Stream" student field: Natural Science / Social Science.
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO custom_fields (id, type, title, sort_order, select_options, category_id)
VALUES (200000012, 'select', 'Stream', 12, 'Natural Science\nSocial Science', 1);

ALTER TABLE students ADD COLUMN IF NOT EXISTS custom_200000012 text;

-- ----------------------------------------------------------------------------
-- 9. Simplify the side menu (keep only the core school modules).
--    Eligibility, Discipline, Accounting, Student Billing, Food Service,
--    Resources and Custom are switched off so the menu is not overwhelming.
--    Important: every module key is kept, with a false value, so PHP 8 never
--    hits an "undefined array key" warning.
--    Re-enable any module later from School Setup > Configuration > Modules.
-- ----------------------------------------------------------------------------
UPDATE config SET config_value = 'a:13:{s:12:"School_Setup";b:1;s:8:"Students";b:1;s:5:"Users";b:1;s:10:"Scheduling";b:1;s:6:"Grades";b:1;s:10:"Attendance";b:1;s:11:"Eligibility";b:0;s:10:"Discipline";b:0;s:10:"Accounting";b:0;s:15:"Student_Billing";b:0;s:12:"Food_Service";b:0;s:9:"Resources";b:0;s:6:"Custom";b:0;}'
WHERE title = 'MODULES';

-- ============================================================================
--  Done. The 2026/2027 marking periods are ready; run School Setup > Rollover
--  (which also creates the 2026 user/school rows) when you start the new
--  school year, then set $DefaultSyear = '2026' in config.inc.php.
-- ============================================================================
