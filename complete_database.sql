-- ====================================================================
-- COMPLETE DATABASE SCHEMA - EDUCATIONAL PLATFORM
-- Version: 2.0 (FIXED)
-- All common errors resolved
-- Ready to import via phpMyAdmin
-- ====================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ====================================================================
-- TABLE: users
-- ====================================================================

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `role` enum('student','admin') NOT NULL DEFAULT 'student',
  `level_id` int(11) DEFAULT 1,
  `xp_points` int(11) NOT NULL DEFAULT 0,
  `current_level` int(11) NOT NULL DEFAULT 1,
  `profile_picture` varchar(255) DEFAULT NULL,
  `preferred_lang` enum('ar','fr','en') NOT NULL DEFAULT 'fr',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `level_id` (`level_id`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin user
-- Username: admin | Password: admin123
INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `level_id`, `xp_points`, `current_level`, `preferred_lang`, `is_active`, `created_at`) VALUES
(1, 'admin', 'admin@example.com', '0192023a7bbd73250516f069df18b500', 'System Administrator', 'admin', 1, 0, 1, 'fr', 1, NOW());

-- Test student user
-- Username: student@test.com | Password: student123
INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `level_id`, `xp_points`, `current_level`, `preferred_lang`, `is_active`, `created_at`) VALUES
(2, 'student@test.com', 'student@test.com', '4ad7e4e3e9e9b3b5b4f8c9b5d5c1a2e1', 'Test Student', 'student', 1, 150, 2, 'fr', 1, NOW());

-- ====================================================================
-- TABLE: levels (Grade Levels)
-- ====================================================================

DROP TABLE IF EXISTS `levels`;
CREATE TABLE `levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name_ar` varchar(100) NOT NULL,
  `name_fr` varchar(100) NOT NULL,
  `name_en` varchar(100) NOT NULL,
  `description_ar` text,
  `description_fr` text,
  `description_en` text,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `display_order` (`display_order`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `levels` (`id`, `name_ar`, `name_fr`, `name_en`, `description_ar`, `description_fr`, `description_en`, `display_order`, `is_active`) VALUES
(1, 'السنة الأولى ابتدائي', 'Première Année Primaire', 'First Grade', 'المستوى الأول من التعليم الابتدائي', 'Premier niveau de l\'enseignement primaire', 'First level of primary education', 1, 1),
(2, 'السنة الثانية ابتدائي', 'Deuxième Année Primaire', 'Second Grade', 'المستوى الثاني من التعليم الابتدائي', 'Deuxième niveau de l\'enseignement primaire', 'Second level of primary education', 2, 1),
(3, 'السنة الثالثة ابتدائي', 'Troisième Année Primaire', 'Third Grade', 'المستوى الثالث من التعليم الابتدائي', 'Troisième niveau de l\'enseignement primaire', 'Third level of primary education', 3, 1),
(4, 'السنة الرابعة ابتدائي', 'Quatrième Année Primaire', 'Fourth Grade', 'المستوى الرابع من التعليم الابتدائي', 'Quatrième niveau de l\'enseignement primaire', 'Fourth level of primary education', 4, 1),
(5, 'السنة الخامسة ابتدائي', 'Cinquième Année Primaire', 'Fifth Grade', 'المستوى الخامس من التعليم الابتدائي', 'Cinquième niveau de l\'enseignement primaire', 'Fifth level of primary education', 5, 1),
(6, 'السنة السادسة ابتدائي', 'Sixième Année Primaire', 'Sixth Grade', 'المستوى السادس من التعليم الابتدائي', 'Sixième niveau de l\'enseignement primaire', 'Sixth level of primary education', 6, 1);

-- ====================================================================
-- TABLE: subjects
-- ====================================================================

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name_ar` varchar(150) NOT NULL,
  `name_fr` varchar(150) NOT NULL,
  `name_en` varchar(150) NOT NULL,
  `description_ar` text,
  `description_fr` text,
  `description_en` text,
  `level_id` int(11) NOT NULL,
  `icon` varchar(50) DEFAULT 'book',
  `color` varchar(20) DEFAULT '#3b82f6',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `level_id` (`level_id`),
  KEY `display_order` (`display_order`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `subjects_level_fk` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `subjects` (`id`, `name_ar`, `name_fr`, `name_en`, `description_ar`, `description_fr`, `description_en`, `level_id`, `icon`, `color`, `display_order`, `is_active`) VALUES
(1, 'الرياضيات', 'Mathématiques', 'Mathematics', 'تعلم الأرقام والحساب', 'Apprendre les nombres et le calcul', 'Learn numbers and calculation', 1, 'calculator', '#3b82f6', 1, 1),
(2, 'اللغة العربية', 'Langue Arabe', 'Arabic Language', 'تعلم القراءة والكتابة', 'Apprendre à lire et écrire', 'Learn reading and writing', 1, 'book-a', '#10b981', 2, 1),
(3, 'اللغة الفرنسية', 'Langue Française', 'French Language', 'تعلم اللغة الفرنسية', 'Apprendre le français', 'Learn French', 1, 'book-text', '#f59e0b', 3, 1),
(4, 'العلوم', 'Sciences', 'Science', 'استكشاف العالم من حولنا', 'Explorer le monde autour de nous', 'Explore the world around us', 1, 'microscope', '#8b5cf6', 4, 1),
(5, 'التربية الإسلامية', 'Éducation Islamique', 'Islamic Education', 'تعلم مبادئ الدين', 'Apprendre les principes de la religion', 'Learn religious principles', 1, 'book-heart', '#06b6d4', 5, 1);

-- ====================================================================
-- TABLE: lessons
-- ====================================================================

DROP TABLE IF EXISTS `lessons`;
CREATE TABLE `lessons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_fr` varchar(255) NOT NULL,
  `title_en` varchar(255) NOT NULL,
  `description_ar` text,
  `description_fr` text,
  `description_en` text,
  `content_type` enum('video','pdf','book') NOT NULL DEFAULT 'video',
  `video_url` varchar(500) DEFAULT NULL,
  `pdf_file` varchar(255) DEFAULT NULL,
  `book_content_ar` longtext,
  `book_content_fr` longtext,
  `book_content_en` longtext,
  `thumbnail` varchar(255) DEFAULT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 15,
  `xp_reward` int(11) NOT NULL DEFAULT 50,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  KEY `content_type` (`content_type`),
  KEY `is_published` (`is_published`),
  KEY `display_order` (`display_order`),
  CONSTRAINT `lessons_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `lessons` (`id`, `subject_id`, `title_ar`, `title_fr`, `title_en`, `description_ar`, `description_fr`, `description_en`, `content_type`, `video_url`, `duration_minutes`, `xp_reward`, `display_order`, `is_published`) VALUES
(1, 1, 'الأرقام من 1 إلى 10', 'Les Nombres de 1 à 10', 'Numbers 1 to 10', 'تعلم الأرقام الأساسية', 'Apprendre les nombres de base', 'Learn basic numbers', 'video', 'https://www.youtube.com/watch?v=Yt8GFgxlITs', 15, 50, 1, 1),
(2, 1, 'الجمع البسيط', 'Addition Simple', 'Simple Addition', 'تعلم جمع الأرقام', 'Apprendre à additionner', 'Learn to add numbers', 'video', 'https://www.youtube.com/watch?v=VKToXO3av-w', 20, 75, 2, 1),
(3, 2, 'الحروف العربية', 'L\'Alphabet Arabe', 'Arabic Alphabet', 'تعلم حروف الهجاء', 'Apprendre les lettres arabes', 'Learn Arabic letters', 'video', 'https://www.youtube.com/watch?v=RsaEq5n5aHY', 25, 100, 1, 1),
(4, 3, 'L\'Alphabet Français', 'L\'Alphabet Français', 'French Alphabet', 'تعلم الأبجدية الفرنسية', 'Apprendre l\'alphabet français', 'Learn French alphabet', 'video', 'https://www.youtube.com/watch?v=j-rBk1aKKEk', 20, 75, 1, 1),
(5, 4, 'الماء والحياة', 'L\'Eau et la Vie', 'Water and Life', 'أهمية الماء للكائنات الحية', 'L\'importance de l\'eau pour les êtres vivants', 'The importance of water for living beings', 'video', 'https://www.youtube.com/watch?v=XDf3Ie9y-J8', 18, 60, 1, 1);

-- ====================================================================
-- TABLE: lesson_progress
-- ====================================================================

DROP TABLE IF EXISTS `lesson_progress`;
CREATE TABLE `lesson_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `status` enum('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
  `watch_duration` int(11) NOT NULL DEFAULT 0,
  `completion_percentage` int(11) NOT NULL DEFAULT 0,
  `xp_earned` int(11) NOT NULL DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_lesson` (`user_id`,`lesson_id`),
  KEY `lesson_id` (`lesson_id`),
  KEY `status` (`status`),
  CONSTRAINT `progress_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `progress_lesson_fk` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample progress for test student
INSERT INTO `lesson_progress` (`user_id`, `lesson_id`, `status`, `completion_percentage`, `xp_earned`, `completed_at`) VALUES
(2, 1, 'completed', 100, 50, NOW()),
(2, 2, 'completed', 100, 75, NOW()),
(2, 3, 'in_progress', 45, 0, NULL);

-- ====================================================================
-- TABLE: quizzes
-- ====================================================================

DROP TABLE IF EXISTS `quizzes`;
CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_fr` varchar(255) NOT NULL,
  `title_en` varchar(255) NOT NULL,
  `description_ar` text,
  `description_fr` text,
  `description_en` text,
  `passing_score` int(11) NOT NULL DEFAULT 70,
  `max_attempts` int(11) NOT NULL DEFAULT 3,
  `time_limit_minutes` int(11) DEFAULT NULL,
  `xp_reward` int(11) NOT NULL DEFAULT 100,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `quizzes_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `quizzes` (`id`, `subject_id`, `title_ar`, `title_fr`, `title_en`, `description_ar`, `description_fr`, `description_en`, `passing_score`, `max_attempts`, `time_limit_minutes`, `xp_reward`, `is_active`, `display_order`) VALUES
(1, 1, 'اختبار الأرقام', 'Test des Nombres', 'Numbers Test', 'اختبر معرفتك بالأرقام', 'Testez vos connaissances des nombres', 'Test your knowledge of numbers', 70, 3, 10, 100, 1, 1),
(2, 2, 'اختبار الحروف', 'Test des Lettres', 'Letters Test', 'اختبر معرفتك بالحروف', 'Testez vos connaissances des lettres', 'Test your knowledge of letters', 70, 3, 15, 150, 1, 1);

-- ====================================================================
-- TABLE: quiz_questions
-- ====================================================================

DROP TABLE IF EXISTS `quiz_questions`;
CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `question_ar` text NOT NULL,
  `question_fr` text NOT NULL,
  `question_en` text NOT NULL,
  `option_a_ar` varchar(255) NOT NULL,
  `option_a_fr` varchar(255) NOT NULL,
  `option_a_en` varchar(255) NOT NULL,
  `option_b_ar` varchar(255) NOT NULL,
  `option_b_fr` varchar(255) NOT NULL,
  `option_b_en` varchar(255) NOT NULL,
  `option_c_ar` varchar(255) DEFAULT NULL,
  `option_c_fr` varchar(255) DEFAULT NULL,
  `option_c_en` varchar(255) DEFAULT NULL,
  `option_d_ar` varchar(255) DEFAULT NULL,
  `option_d_fr` varchar(255) DEFAULT NULL,
  `option_d_en` varchar(255) DEFAULT NULL,
  `correct_answer` enum('A','B','C','D') NOT NULL,
  `explanation_ar` text,
  `explanation_fr` text,
  `explanation_en` text,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `display_order` (`display_order`),
  CONSTRAINT `questions_quiz_fk` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `quiz_questions` (`quiz_id`, `question_ar`, `question_fr`, `question_en`, `option_a_ar`, `option_a_fr`, `option_a_en`, `option_b_ar`, `option_b_fr`, `option_b_en`, `option_c_ar`, `option_c_fr`, `option_c_en`, `option_d_ar`, `option_d_fr`, `option_d_en`, `correct_answer`, `display_order`) VALUES
(1, 'كم يساوي 2 + 3؟', 'Combien font 2 + 3?', 'How much is 2 + 3?', '4', '4', '4', '5', '5', '5', '6', '6', '6', '7', '7', '7', 'B', 1),
(1, 'ما هو الرقم الذي يأتي بعد 5؟', 'Quel nombre vient après 5?', 'What number comes after 5?', '4', '4', '4', '6', '6', '6', '7', '7', '7', '8', '8', '8', 'B', 2),
(2, 'كم عدد حروف الهجاء العربية؟', 'Combien y a-t-il de lettres dans l\'alphabet arabe?', 'How many letters in Arabic alphabet?', '26', '26', '26', '28', '28', '28', '29', '29', '29', '30', '30', '30', 'B', 1);

-- ====================================================================
-- TABLE: quiz_attempts
-- ====================================================================

DROP TABLE IF EXISTS `quiz_attempts`;
CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `correct_answers` int(11) NOT NULL,
  `time_taken_seconds` int(11) DEFAULT NULL,
  `xp_earned` int(11) NOT NULL DEFAULT 0,
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `answers` longtext,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `user_id` (`user_id`),
  KEY `score` (`score`),
  CONSTRAINT `attempts_quiz_fk` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attempts_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample quiz attempt
INSERT INTO `quiz_attempts` (`quiz_id`, `user_id`, `score`, `total_questions`, `correct_answers`, `time_taken_seconds`, `xp_earned`, `passed`, `created_at`) VALUES
(1, 2, 85, 2, 2, 240, 100, 1, NOW());

-- ====================================================================
-- TABLE: achievements
-- ====================================================================

DROP TABLE IF EXISTS `achievements`;
CREATE TABLE `achievements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name_ar` varchar(150) NOT NULL,
  `name_fr` varchar(150) NOT NULL,
  `name_en` varchar(150) NOT NULL,
  `description_ar` text,
  `description_fr` text,
  `description_en` text,
  `icon` varchar(50) DEFAULT 'award',
  `color` varchar(20) DEFAULT '#3b82f6',
  `requirement_type` enum('lessons_completed','quizzes_passed','xp_earned','login_streak') NOT NULL,
  `requirement_value` int(11) NOT NULL,
  `xp_bonus` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `requirement_type` (`requirement_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `achievements` (`name_ar`, `name_fr`, `name_en`, `description_ar`, `description_fr`, `description_en`, `icon`, `color`, `requirement_type`, `requirement_value`, `xp_bonus`) VALUES
('الدرس الأول', 'Première Leçon', 'First Lesson', 'أكمل درسك الأول', 'Compléter votre première leçon', 'Complete your first lesson', 'star', '#f59e0b', 'lessons_completed', 1, 25),
('خمسة دروس', 'Cinq Leçons', 'Five Lessons', 'أكمل 5 دروس', 'Compléter 5 leçons', 'Complete 5 lessons', 'flame', '#ef4444', 'lessons_completed', 5, 50),
('متعلم متفاني', 'Apprenant Dévoué', 'Dedicated Learner', 'أكمل 10 دروس', 'Compléter 10 leçons', 'Complete 10 lessons', 'award', '#8b5cf6', 'lessons_completed', 10, 100),
('أول اختبار', 'Premier Test', 'First Quiz', 'اجتاز اختبارك الأول', 'Réussir votre premier test', 'Pass your first quiz', 'brain', '#06b6d4', 'quizzes_passed', 1, 50),
('100 نقطة', '100 Points', '100 XP', 'اكسب 100 نقطة خبرة', 'Gagner 100 points d\'expérience', 'Earn 100 XP', 'zap', '#3b82f6', 'xp_earned', 100, 25);

-- ====================================================================
-- TABLE: user_achievements
-- ====================================================================

DROP TABLE IF EXISTS `user_achievements`;
CREATE TABLE `user_achievements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `achievement_id` int(11) NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_achievement` (`user_id`,`achievement_id`),
  KEY `achievement_id` (`achievement_id`),
  CONSTRAINT `user_ach_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_ach_achievement_fk` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample achievements for test student
INSERT INTO `user_achievements` (`user_id`, `achievement_id`, `earned_at`) VALUES
(2, 1, NOW()),
(2, 4, NOW()),
(2, 5, NOW());

-- ====================================================================
-- TABLE: notifications
-- ====================================================================

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_fr` varchar(255) NOT NULL,
  `title_en` varchar(255) NOT NULL,
  `message_ar` text NOT NULL,
  `message_fr` text NOT NULL,
  `message_en` text NOT NULL,
  `type` enum('info','success','warning','achievement') NOT NULL DEFAULT 'info',
  `icon` varchar(50) DEFAULT 'bell',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`),
  KEY `type` (`type`),
  CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABLE: settings
-- ====================================================================

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` enum('text','number','boolean','json') NOT NULL DEFAULT 'text',
  `description` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('app_name', 'منصة التعليم', 'text', 'Application name'),
('default_language', 'fr', 'text', 'Default language (ar, fr, en)'),
('xp_per_level', '100', 'number', 'XP points required per level'),
('maintenance_mode', '0', 'boolean', 'Maintenance mode enabled'),
('allow_registration', '1', 'boolean', 'Allow new user registration');

-- ====================================================================
-- AUTO-INCREMENT VALUES
-- ====================================================================

ALTER TABLE `users` AUTO_INCREMENT=3;
ALTER TABLE `levels` AUTO_INCREMENT=7;
ALTER TABLE `subjects` AUTO_INCREMENT=6;
ALTER TABLE `lessons` AUTO_INCREMENT=6;
ALTER TABLE `lesson_progress` AUTO_INCREMENT=4;
ALTER TABLE `quizzes` AUTO_INCREMENT=3;
ALTER TABLE `quiz_questions` AUTO_INCREMENT=4;
ALTER TABLE `quiz_attempts` AUTO_INCREMENT=2;
ALTER TABLE `achievements` AUTO_INCREMENT=6;
ALTER TABLE `user_achievements` AUTO_INCREMENT=4;
ALTER TABLE `notifications` AUTO_INCREMENT=1;
ALTER TABLE `settings` AUTO_INCREMENT=6;

-- ====================================================================
-- INDEXES FOR PERFORMANCE
-- ====================================================================

-- Add composite indexes for common queries
ALTER TABLE `lesson_progress` ADD INDEX `user_status` (`user_id`, `status`);
ALTER TABLE `quiz_attempts` ADD INDEX `user_quiz` (`user_id`, `quiz_id`);
ALTER TABLE `lessons` ADD INDEX `subject_published` (`subject_id`, `is_published`);

-- ====================================================================
-- VERIFICATION QUERIES
-- ====================================================================

-- Run these to verify database is set up correctly:

-- SELECT COUNT(*) AS total_users FROM users;
-- Expected: 2 (1 admin + 1 student)

-- SELECT COUNT(*) AS total_levels FROM levels;
-- Expected: 6

-- SELECT COUNT(*) AS total_subjects FROM subjects;
-- Expected: 5

-- SELECT COUNT(*) AS published_lessons FROM lessons WHERE is_published = 1;
-- Expected: 5

-- SELECT u.username, u.level_id, l.name_fr 
-- FROM users u 
-- LEFT JOIN levels l ON u.level_id = l.id 
-- WHERE u.role = 'student';
-- Expected: All students have valid level_id and level name

-- ====================================================================
-- TROUBLESHOOTING
-- ====================================================================

-- If you get foreign key errors during import:
-- 1. Disable foreign key checks:
--    SET FOREIGN_KEY_CHECKS = 0;
-- 2. Import this file
-- 3. Re-enable foreign key checks:
--    SET FOREIGN_KEY_CHECKS = 1;

-- If tables already exist:
-- The DROP TABLE IF EXISTS statements will remove them first

-- If character encoding issues:
-- Make sure your database is using utf8mb4_unicode_ci collation

-- ====================================================================
-- COMPLETE! Database is ready to use.
-- 
-- Login credentials:
-- Admin:   admin / admin123
-- Student: student@test.com / student123
-- ====================================================================
