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

--new updates comes from here 
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



--check the database when update this part only
-- Update teacher_profiles with essential fields
ALTER TABLE teacher_profiles
ADD COLUMN address TEXT AFTER user_id,
ADD COLUMN city VARCHAR(100) AFTER address,
ADD COLUMN state VARCHAR(100) AFTER city,
ADD COLUMN postal_code VARCHAR(20) AFTER state,
ADD COLUMN phone VARCHAR(20) AFTER postal_code,
ADD COLUMN email VARCHAR(100) AFTER phone,
ADD COLUMN qualification TEXT AFTER email,
ADD COLUMN expertise TEXT AFTER qualification,
ADD COLUMN teaching_certifications TEXT AFTER expertise,
ADD COLUMN bio TEXT AFTER teaching_certifications,
ADD COLUMN linkedin_profile VARCHAR(255) AFTER bio,
ADD COLUMN emergency_contact_phone VARCHAR(20) AFTER linkedin_profile,
ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER emergency_contact_phone;



--another update 
-- For materials table
ALTER TABLE materials 
DROP FOREIGN KEY materials_ibfk_1;

ALTER TABLE materials
DROP INDEX materials_ibfk_1;

ALTER TABLE materials
ADD CONSTRAINT fk_materials_class 
FOREIGN KEY (class_id) 
REFERENCES classes(id) 
ON DELETE CASCADE;

-- For enrollments table
ALTER TABLE enrollments 
DROP FOREIGN KEY enrollments_ibfk_2;

ALTER TABLE enrollments
DROP INDEX enrollments_ibfk_2;

ALTER TABLE enrollments
ADD CONSTRAINT fk_enrollments_class 
FOREIGN KEY (class_id) 
REFERENCES classes(id) 
ON DELETE CASCADE;



--another update for tacher student relationship

-- Create table for teacher-student connections
CREATE TABLE teacher_students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    UNIQUE KEY unique_teacher_student (teacher_id, student_id)
);

-- Modify enrollments table
ALTER TABLE enrollments 
ADD COLUMN teacher_id INT NOT NULL,
ADD FOREIGN KEY (teacher_id) REFERENCES users(id);

--teacher invite
-- Add table for teacher invite links
CREATE TABLE teacher_invite_links (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    invite_code VARCHAR(32) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    FOREIGN KEY (used_by) REFERENCES users(id),
    UNIQUE KEY unique_invite_code (invite_code)
);

--update to student invite link 
-- Add status column to track link usability
ALTER TABLE teacher_invite_links
ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active';