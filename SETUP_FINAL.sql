SET SERVEROUTPUT ON
SET ECHO OFF
SET FEEDBACK OFF

PROMPT =====================================================
PROMPT  UniReg Database Setup — Starting...
PROMPT =====================================================

-- ── STEP 1: Clean drop of everything (safe re-run) ──────────
PROMPT Cleaning old objects...

DECLARE
BEGIN
    BEGIN EXECUTE IMMEDIATE 'DROP TRIGGER trg_auto_waitlist_enroll';   EXCEPTION WHEN OTHERS THEN NULL; END;
    BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE enroll_student';            EXCEPTION WHEN OTHERS THEN NULL; END;
    BEGIN EXECUTE IMMEDIATE 'DROP VIEW course_enrollment_summary';      EXCEPTION WHEN OTHERS THEN NULL; END;
    BEGIN EXECUTE IMMEDIATE 'DROP TABLE enrollments CASCADE CONSTRAINTS PURGE'; EXCEPTION WHEN OTHERS THEN NULL; END;
    BEGIN EXECUTE IMMEDIATE 'DROP TABLE courses    CASCADE CONSTRAINTS PURGE'; EXCEPTION WHEN OTHERS THEN NULL; END;
    BEGIN EXECUTE IMMEDIATE 'DROP TABLE students   CASCADE CONSTRAINTS PURGE'; EXCEPTION WHEN OTHERS THEN NULL; END;
    BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE enrollment_seq';             EXCEPTION WHEN OTHERS THEN NULL; END;
    DBMS_OUTPUT.PUT_LINE('  >> Old objects removed OK');
END;
/

-- ── STEP 2: Create tables ────────────────────────────────────
PROMPT Creating tables...

CREATE TABLE students (
    student_id  NUMBER(10)    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name        VARCHAR2(100) NOT NULL,
    email       VARCHAR2(150) NOT NULL,
    cgpa        NUMBER(4,2)   NOT NULL,
    CONSTRAINT uq_student_email UNIQUE (email),
    CONSTRAINT chk_cgpa CHECK (cgpa BETWEEN 0 AND 4)
);

CREATE TABLE courses (
    course_id   NUMBER(10)    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    course_name VARCHAR2(200) NOT NULL,
    max_seats   NUMBER(5)     NOT NULL,
    day         VARCHAR2(20)  NOT NULL,
    start_time  NUMBER(4)     NOT NULL,
    end_time    NUMBER(4)     NOT NULL,
    CONSTRAINT chk_seats CHECK (max_seats > 0),
    CONSTRAINT chk_times CHECK (end_time > start_time),
    CONSTRAINT chk_day   CHECK (day IN (
        'SUNDAY','MONDAY','TUESDAY','WEDNESDAY',
        'THURSDAY','FRIDAY','SATURDAY'))
);

CREATE TABLE enrollments (
    enrollment_id NUMBER(10)   NOT NULL,
    student_id    NUMBER(10)   NOT NULL,
    course_id     NUMBER(10)   NOT NULL,
    status        VARCHAR2(12) NOT NULL,
    enrolled_at   TIMESTAMP    DEFAULT SYSTIMESTAMP,
    CONSTRAINT pk_enrollment     PRIMARY KEY (enrollment_id),
    CONSTRAINT fk_enr_student    FOREIGN KEY (student_id)
                                 REFERENCES students(student_id) ON DELETE CASCADE,
    CONSTRAINT fk_enr_course     FOREIGN KEY (course_id)
                                 REFERENCES courses(course_id)   ON DELETE CASCADE,
    CONSTRAINT uq_student_course UNIQUE (student_id, course_id),
    CONSTRAINT chk_status        CHECK (status IN ('ENROLLED','WAITLISTED'))
);

PROMPT   >> Tables created OK

-- ── STEP 3: Sequence ─────────────────────────────────────────
PROMPT Creating sequence...

CREATE SEQUENCE enrollment_seq
    START WITH 1
    INCREMENT BY 1
    NOCACHE
    NOCYCLE;

PROMPT   >> Sequence created OK

-- ── STEP 4: View ─────────────────────────────────────────────
PROMPT Creating view...

CREATE OR REPLACE VIEW course_enrollment_summary AS
SELECT
    c.course_id,
    c.course_name,
    c.max_seats,
    c.day,
    c.start_time,
    c.end_time,
    COUNT(CASE WHEN e.status = 'ENROLLED'   THEN 1 END) AS enrolled_count,
    COUNT(CASE WHEN e.status = 'WAITLISTED' THEN 1 END) AS waitlisted_count,
    c.max_seats - COUNT(CASE WHEN e.status = 'ENROLLED' THEN 1 END) AS seats_available
FROM courses c
LEFT JOIN enrollments e ON c.course_id = e.course_id
GROUP BY
    c.course_id, c.course_name, c.max_seats,
    c.day, c.start_time, c.end_time;

PROMPT   >> View created OK

-- ── STEP 5: Stored Procedure ─────────────────────────────────
PROMPT Creating stored procedure...

CREATE OR REPLACE PROCEDURE enroll_student (
    p_student_id  IN  NUMBER,
    p_course_id   IN  NUMBER,
    p_status      OUT VARCHAR2,
    p_message     OUT VARCHAR2
) AS
    v_enrolled_count  NUMBER;
    v_max_seats       NUMBER;
    v_conflict_count  NUMBER;
    v_dup_count       NUMBER;
    v_cgpa            NUMBER;
    v_day             VARCHAR2(20);
    v_start           NUMBER;
    v_end             NUMBER;
    v_new_id          NUMBER;
    v_assign_status   VARCHAR2(12);
    ex_duplicate      EXCEPTION;
    ex_time_conflict  EXCEPTION;
    ex_not_found      EXCEPTION;
BEGIN
    -- Check student exists
    BEGIN
        SELECT cgpa INTO v_cgpa
        FROM   students
        WHERE  student_id = p_student_id;
    EXCEPTION
        WHEN NO_DATA_FOUND THEN RAISE ex_not_found;
    END;

    -- Check course exists
    BEGIN
        SELECT max_seats, day, start_time, end_time
        INTO   v_max_seats, v_day, v_start, v_end
        FROM   courses
        WHERE  course_id = p_course_id;
    EXCEPTION
        WHEN NO_DATA_FOUND THEN RAISE ex_not_found;
    END;

    -- Check duplicate enrollment
    SELECT COUNT(*) INTO v_dup_count
    FROM   enrollments
    WHERE  student_id = p_student_id
    AND    course_id  = p_course_id;
    IF v_dup_count > 0 THEN RAISE ex_duplicate; END IF;

    -- Check time conflict
    SELECT COUNT(*) INTO v_conflict_count
    FROM   enrollments e
    JOIN   courses     c ON c.course_id = e.course_id
    WHERE  e.student_id = p_student_id
    AND    e.status     = 'ENROLLED'
    AND    c.day        = v_day
    AND    c.start_time < v_end
    AND    c.end_time   > v_start;
    IF v_conflict_count > 0 THEN RAISE ex_time_conflict; END IF;

    -- Check seat availability and assign status
    SELECT COUNT(*) INTO v_enrolled_count
    FROM   enrollments
    WHERE  course_id = p_course_id
    AND    status    = 'ENROLLED';

    IF v_enrolled_count < v_max_seats THEN
        v_assign_status := 'ENROLLED';
    ELSE
        v_assign_status := 'WAITLISTED';
    END IF;

    -- Insert the enrollment record
    v_new_id := enrollment_seq.NEXTVAL;
    INSERT INTO enrollments (enrollment_id, student_id, course_id, status)
    VALUES (v_new_id, p_student_id, p_course_id, v_assign_status);
    COMMIT;

    p_status  := v_assign_status;
    p_message := 'Successfully ' || LOWER(v_assign_status) || ' in course.';

EXCEPTION
    WHEN ex_duplicate THEN
        p_status  := 'ERROR';
        p_message := 'Already enrolled or waitlisted in this course.';
        ROLLBACK;
    WHEN ex_time_conflict THEN
        p_status  := 'ERROR';
        p_message := 'Schedule conflict on ' || v_day || '.';
        ROLLBACK;
    WHEN ex_not_found THEN
        p_status  := 'ERROR';
        p_message := 'Student or course not found.';
        ROLLBACK;
    WHEN OTHERS THEN
        p_status  := 'ERROR';
        p_message := 'Unexpected error: ' || SQLERRM;
        ROLLBACK;
END enroll_student;
/

PROMPT   >> Stored procedure created OK

-- ── STEP 6: COMPOUND TRIGGER (fixed — no mutating error) ─────
PROMPT Creating compound trigger...

CREATE OR REPLACE TRIGGER trg_auto_waitlist_enroll
FOR DELETE ON enrollments
COMPOUND TRIGGER

    -- Stores course IDs that lost an ENROLLED seat during this DELETE
    TYPE t_course_ids IS TABLE OF NUMBER INDEX BY PLS_INTEGER;
    v_dropped_courses t_course_ids;
    v_idx             PLS_INTEGER := 0;

    -- AFTER EACH ROW:
    -- Table is still mutating here — only store the course_id, never query
    AFTER EACH ROW IS
    BEGIN
        IF :OLD.status = 'ENROLLED' THEN
            v_idx := v_idx + 1;
            v_dropped_courses(v_idx) := :OLD.course_id;
        END IF;
    END AFTER EACH ROW;

    -- AFTER STATEMENT:
    -- DELETE is fully done — table is stable, safe to query freely
    -- Promote best waitlisted student (highest CGPA, earliest signup)
    AFTER STATEMENT IS
        v_enrolled_count NUMBER;
        v_max_seats      NUMBER;
        v_day            VARCHAR2(20);
        v_start          NUMBER;
        v_end            NUMBER;
        v_conflict       NUMBER;
        v_course_id      NUMBER;

        CURSOR c_waitlist(p_course_id NUMBER) IS
            SELECT e.enrollment_id, e.student_id
            FROM   enrollments e
            JOIN   students    s ON s.student_id = e.student_id
            WHERE  e.course_id = p_course_id
            AND    e.status    = 'WAITLISTED'
            ORDER  BY s.cgpa DESC,
                      e.enrolled_at ASC;
    BEGIN
        FOR i IN 1 .. v_idx LOOP
            v_course_id := v_dropped_courses(i);

            -- Count how many seats are currently taken
            SELECT COUNT(*) INTO v_enrolled_count
            FROM   enrollments
            WHERE  course_id = v_course_id
            AND    status    = 'ENROLLED';

            -- Get course schedule details
            SELECT max_seats, day, start_time, end_time
            INTO   v_max_seats, v_day, v_start, v_end
            FROM   courses
            WHERE  course_id = v_course_id;

            -- Seat is free — find best waitlisted student
            IF v_enrolled_count < v_max_seats THEN
                FOR rec IN c_waitlist(v_course_id) LOOP

                    -- Check for time conflict with this student's other courses
                    SELECT COUNT(*) INTO v_conflict
                    FROM   enrollments e
                    JOIN   courses     c ON c.course_id = e.course_id
                    WHERE  e.student_id = rec.student_id
                    AND    e.status     = 'ENROLLED'
                    AND    c.day        = v_day
                    AND    c.start_time < v_end
                    AND    c.end_time   > v_start;

                    -- No conflict — promote this student
                    IF v_conflict = 0 THEN
                        UPDATE enrollments
                        SET    status = 'ENROLLED'
                        WHERE  enrollment_id = rec.enrollment_id;
                        EXIT;
                    END IF;

                END LOOP;
            END IF;

        END LOOP;

    EXCEPTION
        WHEN OTHERS THEN
            -- Silently handle errors so DROP always succeeds
            NULL;
    END AFTER STATEMENT;

END trg_auto_waitlist_enroll;
/

PROMPT   >> Trigger created OK

-- ── STEP 7: Seed data ────────────────────────────────────────
PROMPT Inserting seed data...

INSERT INTO students (name, email, cgpa) VALUES ('Alice Rahman',  'alice@uni.edu',  3.92);
INSERT INTO students (name, email, cgpa) VALUES ('Bob Hossain',   'bob@uni.edu',    3.45);
INSERT INTO students (name, email, cgpa) VALUES ('Carla Ahmed',   'carla@uni.edu',  3.78);
INSERT INTO students (name, email, cgpa) VALUES ('David Khan',    'david@uni.edu',  2.90);
INSERT INTO students (name, email, cgpa) VALUES ('Eva Begum',     'eva@uni.edu',    3.60);
INSERT INTO students (name, email, cgpa) VALUES ('Farhan Islam',  'farhan@uni.edu', 3.15);
INSERT INTO students (name, email, cgpa) VALUES ('Grace Noor',    'grace@uni.edu',  3.88);
INSERT INTO students (name, email, cgpa) VALUES ('Hamid Uddin',   'hamid@uni.edu',  2.75);

INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
    VALUES ('Database Systems',        3, 'SUNDAY',    900,  1030);
INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
    VALUES ('Operating Systems',       4, 'MONDAY',   1100,  1230);
INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
    VALUES ('Algorithms & Complexity', 3, 'TUESDAY',   800,   930);
INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
    VALUES ('Computer Networks',       5, 'WEDNESDAY',1300,  1430);
INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
    VALUES ('Software Engineering',    2, 'THURSDAY',  900,  1030);
INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
    VALUES ('Artificial Intelligence', 4, 'SUNDAY',   1100,  1230);
INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
    VALUES ('Linear Algebra',          3, 'MONDAY',    800,   930);
INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
    VALUES ('Web Technologies',        5, 'FRIDAY',   1400,  1530);

COMMIT;

PROMPT   >> Seed data inserted OK

-- ── STEP 8: Final verification ──────────────────────────────
PROMPT
PROMPT =====================================================
PROMPT  VERIFICATION — All counts should match below
PROMPT =====================================================

SELECT
    'Students  : ' || COUNT(*) || ' rows (expected: 8)' AS result
FROM students
UNION ALL
SELECT
    'Courses   : ' || COUNT(*) || ' rows (expected: 8)'
FROM courses
UNION ALL
SELECT
    'Enrollments: ' || COUNT(*) || ' rows (expected: 0)'
FROM enrollments;

PROMPT
SELECT
    object_type  || ' — ' ||
    object_name  || ' — ' ||
    status       AS database_objects
FROM user_objects
WHERE object_type IN ('TABLE','VIEW','PROCEDURE','TRIGGER','SEQUENCE')
ORDER BY object_type, object_name;

PROMPT
PROMPT =====================================================
PROMPT  ALL 7 objects above should show status: VALID
PROMPT  Setup is complete! Open the app:
PROMPT  http://localhost/unireg/index.php
PROMPT =====================================================

EXIT;
