-- ============================================================
--  Educational Platform — Database Schema
--  Compatible : MySQL 5.7 / 8.0  |  InfinityFree
--  Charset    : utf8mb4  (full Unicode + emoji support)
--  Collation  : utf8mb4_unicode_ci
--  PASSWORD:    Using MD5 (simple compatibility)
-- ============================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET collation_connection = 'utf8mb4_unicode_ci';
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS quiz_attempts;
DROP TABLE IF EXISTS quiz_questions;
DROP TABLE IF EXISTS quizzes;
DROP TABLE IF EXISTS lesson_progress;
DROP TABLE IF EXISTS lessons;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS levels;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  1. LEVELS
-- ============================================================
CREATE TABLE levels (
    id            INT          NOT NULL AUTO_INCREMENT,
    name_ar       VARCHAR(100) NOT NULL,
    name_fr       VARCHAR(100) NOT NULL,
    name_en       VARCHAR(100) NOT NULL,
    slug          VARCHAR(50)  NOT NULL,
    display_order TINYINT      NOT NULL DEFAULT 0,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_levels_slug (slug),
    KEY idx_levels_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  2. USERS
-- ============================================================
CREATE TABLE users (
    id                 INT          NOT NULL AUTO_INCREMENT,
    username           VARCHAR(50)  NOT NULL,
    email              VARCHAR(150) NOT NULL,
    password           VARCHAR(255) NOT NULL COMMENT 'MD5 hash',
    full_name          VARCHAR(150) NOT NULL,
    role               ENUM('student','admin','staff','super_admin') NOT NULL DEFAULT 'student',
    level_id           INT          NULL DEFAULT NULL,
    xp_points          INT          NOT NULL DEFAULT 0,
    current_level      INT          NOT NULL DEFAULT 1,
    preferred_language ENUM('ar','fr','en') NOT NULL DEFAULT 'ar',
    is_active          TINYINT(1)   NOT NULL DEFAULT 1,
    last_login         TIMESTAMP    NULL DEFAULT NULL,
    created_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email    (email),
    KEY idx_users_role           (role),
    KEY idx_users_level          (level_id),
    KEY idx_users_xp             (xp_points DESC),
    KEY idx_users_active         (is_active),
    CONSTRAINT fk_users_level
        FOREIGN KEY (level_id) REFERENCES levels (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  3. SUBJECTS
-- ============================================================
CREATE TABLE subjects (
    id            INT          NOT NULL AUTO_INCREMENT,
    name_ar       VARCHAR(150) NOT NULL,
    name_fr       VARCHAR(150) NOT NULL,
    name_en       VARCHAR(150) NOT NULL,
    level_id      INT          NOT NULL,
    color         VARCHAR(7)   NOT NULL DEFAULT '#6D28D9',
    display_order TINYINT      NOT NULL DEFAULT 0,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_subjects_level (level_id),
    KEY idx_subjects_order (display_order),
    CONSTRAINT fk_subjects_level
        FOREIGN KEY (level_id) REFERENCES levels (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  4. LESSONS
-- ============================================================
CREATE TABLE lessons (
    id               INT           NOT NULL AUTO_INCREMENT,
    subject_id       INT           NOT NULL,
    title_ar         VARCHAR(255)  NOT NULL,
    title_fr         VARCHAR(255)  NOT NULL DEFAULT '',
    title_en         VARCHAR(255)  NOT NULL DEFAULT '',
    description_ar   TEXT          NULL,
    description_fr   TEXT          NULL,
    description_en   TEXT          NULL,
    content_type     ENUM('video','pdf','book') NOT NULL DEFAULT 'video',
    url              VARCHAR(1000) NOT NULL,
    thumbnail_url    VARCHAR(1000) NULL,
    duration_minutes SMALLINT      NOT NULL DEFAULT 0,
    xp_reward        SMALLINT      NOT NULL DEFAULT 10,
    display_order    SMALLINT      NOT NULL DEFAULT 0,
    is_published     TINYINT(1)    NOT NULL DEFAULT 1,
    created_by       INT           NULL DEFAULT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_lessons_subject   (subject_id),
    KEY idx_lessons_published (is_published),
    KEY idx_lessons_order     (display_order),
    KEY idx_lessons_type      (content_type),
    CONSTRAINT fk_lessons_subject
        FOREIGN KEY (subject_id) REFERENCES subjects (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_lessons_creator
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  5. LESSON_PROGRESS
-- ============================================================
CREATE TABLE lesson_progress (
    id           INT       NOT NULL AUTO_INCREMENT,
    user_id      INT       NOT NULL,
    lesson_id    INT       NOT NULL,
    status       ENUM('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
    xp_earned    SMALLINT  NOT NULL DEFAULT 0,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_progress_user_lesson (user_id, lesson_id),
    KEY idx_progress_user   (user_id),
    KEY idx_progress_lesson (lesson_id),
    KEY idx_progress_status (status),
    KEY idx_progress_completed (completed_at),
    CONSTRAINT fk_progress_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_progress_lesson
        FOREIGN KEY (lesson_id) REFERENCES lessons (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  6. QUIZZES
-- ============================================================
CREATE TABLE quizzes (
    id            INT          NOT NULL AUTO_INCREMENT,
    subject_id    INT          NOT NULL,
    title_ar      VARCHAR(255) NOT NULL,
    title_fr      VARCHAR(255) NOT NULL DEFAULT '',
    title_en      VARCHAR(255) NOT NULL DEFAULT '',
    passing_score TINYINT      NOT NULL DEFAULT 70,
    xp_reward     SMALLINT     NOT NULL DEFAULT 50,
    max_attempts  TINYINT      NOT NULL DEFAULT 3,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_quizzes_subject (subject_id),
    KEY idx_quizzes_active  (is_active),
    CONSTRAINT fk_quizzes_subject
        FOREIGN KEY (subject_id) REFERENCES subjects (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  7. QUIZ_QUESTIONS
-- ============================================================
CREATE TABLE quiz_questions (
    id              INT  NOT NULL AUTO_INCREMENT,
    quiz_id         INT  NOT NULL,
    question_ar     TEXT NOT NULL,
    question_fr     TEXT NOT NULL,
    question_en     TEXT NOT NULL,
    option_a_ar     VARCHAR(500) NOT NULL,
    option_a_fr     VARCHAR(500) NOT NULL DEFAULT '',
    option_a_en     VARCHAR(500) NOT NULL DEFAULT '',
    option_b_ar     VARCHAR(500) NOT NULL,
    option_b_fr     VARCHAR(500) NOT NULL DEFAULT '',
    option_b_en     VARCHAR(500) NOT NULL DEFAULT '',
    option_c_ar     VARCHAR(500) NULL DEFAULT NULL,
    option_c_fr     VARCHAR(500) NULL DEFAULT NULL,
    option_c_en     VARCHAR(500) NULL DEFAULT NULL,
    option_d_ar     VARCHAR(500) NULL DEFAULT NULL,
    option_d_fr     VARCHAR(500) NULL DEFAULT NULL,
    option_d_en     VARCHAR(500) NULL DEFAULT NULL,
    correct_answer  ENUM('A','B','C','D') NOT NULL,
    points          TINYINT  NOT NULL DEFAULT 1,
    display_order   SMALLINT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_questions_quiz  (quiz_id),
    KEY idx_questions_order (display_order),
    CONSTRAINT fk_questions_quiz
        FOREIGN KEY (quiz_id) REFERENCES quizzes (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  8. QUIZ_ATTEMPTS
-- ============================================================
CREATE TABLE quiz_attempts (
    id               INT        NOT NULL AUTO_INCREMENT,
    quiz_id          INT        NOT NULL,
    user_id          INT        NOT NULL,
    score            TINYINT    NOT NULL DEFAULT 0,
    total_questions  TINYINT    NOT NULL,
    correct_answers  TINYINT    NOT NULL DEFAULT 0,
    xp_earned        SMALLINT   NOT NULL DEFAULT 0,
    passed           TINYINT(1) NOT NULL DEFAULT 0,
    completed_at     TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attempts_quiz (quiz_id),
    KEY idx_attempts_user (user_id),
    KEY idx_attempts_date (completed_at),
    CONSTRAINT fk_attempts_quiz
        FOREIGN KEY (quiz_id) REFERENCES quizzes (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_attempts_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  SEED DATA
-- ============================================================

INSERT INTO levels (name_ar, name_fr, name_en, slug, display_order) VALUES
('الأولى إعدادي',  '1ère Année Collège', '7th Grade',       '1ac',  1),
('الثانية إعدادي', '2ème Année Collège', '8th Grade',       '2ac',  2),
('الثالثة إعدادي', '3ème Année Collège', '9th Grade',       '3ac',  3),
('الجذع المشترك',  'Tronc Commun',       '10th Grade (TC)', 'tc',   4),
('الأولى باك',      '1ère Bac',           '11th Grade',      '1bac', 5),
('الثانية باك',     '2ème Bac',           '12th Grade (Bac)','2bac', 6);

-- ============================================================
--  ADMIN USER - MD5 PASSWORD
-- ============================================================
-- Username: admin
-- Password: admin123
-- MD5 Hash: 0192023a7bbd73250516f069df18b500
INSERT INTO users (username, email, password, full_name, role, preferred_language, is_active) VALUES (
    'admin',
    'admin@school.ma',
    '0192023a7bbd73250516f069df18b500',
    'Administrator',
    'admin',
    'ar',
    1
);

-- Subjects for 3ème Collège (level id = 3)
INSERT INTO subjects (name_ar, name_fr, name_en, level_id, color, display_order) VALUES
('الرياضيات',          'Mathématiques',                     'Mathematics',           3, '#1D4ED8', 1),
('الفيزياء والكيمياء', 'Physique-Chimie',                   'Physics & Chemistry',   3, '#7C3AED', 2),
('علوم الحياة والأرض', 'Sciences de la Vie et de la Terre', 'Life & Earth Sciences', 3, '#065F46', 3),
('اللغة العربية',      'Langue Arabe',                      'Arabic Language',       3, '#92400E', 4),
('اللغة الفرنسية',     'Langue Française',                  'French Language',       3, '#1E40AF', 5),
('اللغة الإنجليزية',   'Langue Anglaise',                   'English Language',      3, '#9D174D', 6);

-- Subjects for 2ème Bac (level id = 6)
INSERT INTO subjects (name_ar, name_fr, name_en, level_id, color, display_order) VALUES
('الرياضيات',          'Mathématiques',                     'Mathematics',           6, '#1D4ED8', 1),
('الفيزياء والكيمياء', 'Physique-Chimie',                   'Physics & Chemistry',   6, '#7C3AED', 2),
('علوم الحياة والأرض', 'Sciences de la Vie et de la Terre', 'Life & Earth Sciences', 6, '#065F46', 3),
('الفلسفة',             'Philosophie',                       'Philosophy',            6, '#B45309', 4);


-- ============================================================
--  IMPORTANT NOTES
-- ============================================================
-- 
-- ADMIN LOGIN CREDENTIALS:
-- Username: admin
-- Password: admin123
-- 
-- ⚠️ WARNING: MD5 is NOT secure for production!
-- This is only for compatibility/testing purposes.
-- Change to bcrypt after login works.
-- 
-- To change password after login:
-- UPDATE users SET password = MD5('your_new_password') WHERE username = 'admin';
-- 
-- ============================================================
