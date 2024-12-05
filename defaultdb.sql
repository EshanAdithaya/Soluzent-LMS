-- First create the users table
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('admin','student') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_access` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
);

-- Then create the classes table that references users
CREATE TABLE `classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `classes_ibfk_1` (`created_by`),
  CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
);

CREATE TABLE `enrollments` (
  `student_id` int NOT NULL,
  `class_id` int NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`student_id`,`class_id`),
  KEY `enrollments_ibfk_2` (`class_id`),
  CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`)
);

CREATE TABLE `materials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int DEFAULT NULL,
  `title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` enum('link','pdf','image','youtubeLink') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `content` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `video_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `embed_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `materials_ibfk_1` (`class_id`),
  CONSTRAINT `materials_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`)
);

CREATE TABLE `reset_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `token` varchar(6) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reset_tokens_ibfk_1` (`user_id`),
  CONSTRAINT `reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
);


-- Teacher role modifications
ALTER TABLE `users` 
MODIFY COLUMN `role` ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student';

CREATE TABLE `teacher_profiles` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `qualification` TEXT,
    `bio` TEXT,
    `expertise` VARCHAR(255),
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `approved_at` TIMESTAMP NULL,
    `approved_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

CREATE TABLE `teacher_classes` (
    `teacher_id` INT NOT NULL,
    `class_id` INT NOT NULL,
    `is_owner` BOOLEAN DEFAULT FALSE,
    `can_modify` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`teacher_id`, `class_id`),
    FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE
);

CREATE INDEX `idx_teacher_status` ON `teacher_profiles`(`status`);
CREATE INDEX `idx_teacher_approval` ON `teacher_profiles`(`approved_at`);
CREATE INDEX `idx_class_ownership` ON `teacher_classes`(`is_owner`);

ALTER TABLE `materials`
ADD COLUMN `added_by` INT,
ADD FOREIGN KEY (`added_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;

INSERT INTO `teacher_classes` (`teacher_id`, `class_id`, `is_owner`, `can_modify`)
SELECT COALESCE(`created_by`, 1), `id`, TRUE, TRUE
FROM `classes`;

UPDATE `materials` m
JOIN `classes` c ON m.`class_id` = c.`id`
SET m.`added_by` = COALESCE(c.`created_by`, 1);

