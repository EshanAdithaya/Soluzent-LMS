/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE TABLE "classes" (
  "id" int NOT NULL AUTO_INCREMENT,
  "name" varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  "description" text COLLATE utf8mb4_general_ci,
  "created_by" int DEFAULT NULL,
  "created_at" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY ("id"),
  KEY "classes_ibfk_1" ("created_by"),
  CONSTRAINT "classes_ibfk_1" FOREIGN KEY ("created_by") REFERENCES "users" ("id")
);

CREATE TABLE "enrollments" (
  "student_id" int NOT NULL,
  "class_id" int NOT NULL,
  "enrolled_at" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY ("student_id","class_id"),
  KEY "enrollments_ibfk_2" ("class_id"),
  CONSTRAINT "enrollments_ibfk_1" FOREIGN KEY ("student_id") REFERENCES "users" ("id"),
  CONSTRAINT "enrollments_ibfk_2" FOREIGN KEY ("class_id") REFERENCES "classes" ("id")
);

CREATE TABLE "materials" (
  "id" int NOT NULL AUTO_INCREMENT,
  "class_id" int DEFAULT NULL,
  "title" varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  "type" enum('link','pdf','image','youtubeLink') COLLATE utf8mb4_general_ci DEFAULT NULL,
  "content" text COLLATE utf8mb4_general_ci,
  "created_at" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "video_id" varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  "embed_url" varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY ("id"),
  KEY "materials_ibfk_1" ("class_id"),
  CONSTRAINT "materials_ibfk_1" FOREIGN KEY ("class_id") REFERENCES "classes" ("id")
);

CREATE TABLE "reset_tokens" (
  "id" int NOT NULL AUTO_INCREMENT,
  "user_id" int DEFAULT NULL,
  "token" varchar(6) COLLATE utf8mb4_general_ci DEFAULT NULL,
  "expires_at" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("id"),
  KEY "reset_tokens_ibfk_1" ("user_id"),
  CONSTRAINT "reset_tokens_ibfk_1" FOREIGN KEY ("user_id") REFERENCES "users" ("id")
);

CREATE TABLE "users" (
  "id" int NOT NULL AUTO_INCREMENT,
  "name" varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  "email" varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  "phone" varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  "password" varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  "role" enum('admin','student') COLLATE utf8mb4_general_ci DEFAULT NULL,
  "created_at" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "last_access" datetime DEFAULT NULL,
  PRIMARY KEY ("id"),
  UNIQUE KEY "email" ("email")
);

INSERT INTO `classes` (`id`, `name`, `description`, `created_by`, `created_at`) VALUES
(1, '1st class', 'this is the 1st class', NULL, '2024-12-02 02:26:23');


INSERT INTO `enrollments` (`student_id`, `class_id`, `enrolled_at`) VALUES
(1, 1, '2024-12-02 02:26:33');


INSERT INTO `materials` (`id`, `class_id`, `title`, `type`, `content`, `created_at`, `video_id`, `embed_url`) VALUES
(1, 1, 'test', 'image', '1733107397_20201031_184514.jpg', '2024-12-02 02:43:17', NULL, NULL);
INSERT INTO `materials` (`id`, `class_id`, `title`, `type`, `content`, `created_at`, `video_id`, `embed_url`) VALUES
(2, 1, 'tssa', 'link', 'https://youtu.be/zA5Z06Bo7s4', '2024-12-03 11:39:41', NULL, NULL);
INSERT INTO `materials` (`id`, `class_id`, `title`, `type`, `content`, `created_at`, `video_id`, `embed_url`) VALUES
(3, 1, 'test', 'link', 'https://youtu.be/zA5Z06Bo7s4qqqqqqqqqqqqq', '2024-12-03 11:40:54', NULL, NULL);
INSERT INTO `materials` (`id`, `class_id`, `title`, `type`, `content`, `created_at`, `video_id`, `embed_url`) VALUES
(4, 1, 'qqqqqqqqqqqqqq', 'youtubeLink', 'https://youtu.be/zA5Z06Bo7s4', '2024-12-03 11:48:59', NULL, NULL);



INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `created_at`, `last_access`) VALUES
(1, 'qwe', 'eshanguanthilaka10@gmail.com', '0766057574', '$2y$10$TvfnTdE2SOfZNK.Soh9knehiHPx6ClkLRcMydRx7vuoDW8U8Tir4q', 'admin', '2024-12-01 14:45:52', NULL);



/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- Modify users table to include teacher role
ALTER TABLE users 
MODIFY COLUMN role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student';

-- Create teacher profiles table for additional teacher information
CREATE TABLE teacher_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    qualification TEXT,
    bio TEXT,
    expertise VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_at TIMESTAMP NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Create teacher_classes junction table
CREATE TABLE teacher_classes (
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    is_owner BOOLEAN DEFAULT FALSE,
    can_modify BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (teacher_id, class_id),
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- Add indexes for better performance
CREATE INDEX idx_teacher_status ON teacher_profiles(status);
CREATE INDEX idx_teacher_approval ON teacher_profiles(approved_at);
CREATE INDEX idx_class_ownership ON teacher_classes(is_owner);

-- Add constraints to materials table to track which teacher added the material
ALTER TABLE materials
ADD COLUMN added_by INT,
ADD FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL;

-- Migrate existing classes to have proper ownership
-- This assumes the admin created all existing classes
INSERT INTO teacher_classes (teacher_id, class_id, is_owner, can_modify)
SELECT COALESCE(created_by, 1), id, TRUE, TRUE
FROM classes;

-- Update existing materials to set added_by to the class creator
UPDATE materials m
JOIN classes c ON m.class_id = c.id
SET m.added_by = COALESCE(c.created_by, 1);