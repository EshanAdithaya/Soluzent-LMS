# System Architecture

## Database Tables
```sql
-- Users table (both admin and students)
-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Generation Time: Feb 12, 2025 at 07:37 AM
-- Server version: 8.0.41
-- PHP Version: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lms`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_page`
--

CREATE TABLE `about_page` (
  `id` int NOT NULL,
  `hero_title` varchar(255) NOT NULL,
  `hero_subtitle` varchar(255) DEFAULT NULL,
  `story_title` varchar(255) DEFAULT NULL,
  `story_content` text,
  `mission_content` text,
  `vision_content` text,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text,
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `about_page`
--

INSERT INTO `about_page` (`id`, `hero_title`, `hero_subtitle`, `story_title`, `story_content`, `mission_content`, `vision_content`, `meta_title`, `meta_description`, `last_updated`, `updated_by`) VALUES
(1, 'About SOLUZENT LMS', 'Transforming education through innovative technology solutions', 'Our Story', 'SOLUZENT Technology was founded with a vision to make quality education accessible and affordable...', 'To provide an accessible, secure, and comprehensive learning platform...', 'To become the leading multi-mentor learning platform...', 'About Us - SOLUZENT LMS', 'Learn about SOLUZENT LMS, our mission, vision, and the team behind our innovative learning platform.', '2024-12-30 05:08:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `name`, `description`, `created_by`, `created_at`) VALUES
(14, 'Sinhala second', 'This is a test claass', 7, '2024-12-28 13:32:20'),
(22, 'simhala2', 'mfala', 7, '2024-12-30 07:37:11'),
(24, 'new', 'claaa', 7, '2024-12-30 07:44:23');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `student_id` int NOT NULL,
  `class_id` int NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `teacher_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`student_id`, `class_id`, `enrolled_at`, `teacher_id`) VALUES
(8, 14, '2024-12-28 13:32:30', 7),
(8, 22, '2024-12-30 07:37:25', 7);

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `id` int NOT NULL,
  `class_id` int DEFAULT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` enum('link','pdf','image','youtubeLink') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `video_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `embed_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `added_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `expires` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `remember_tokens`
--

INSERT INTO `remember_tokens` (`id`, `user_id`, `token`, `expires`, `created_at`) VALUES
(1, 1, 'c94903dd0219bac9873b5d3d8ee8c2cdbdfee03d8a05a62c9dc314fc78babfdb', '2025-01-16 17:22:37', '2024-12-17 17:22:38'),
(3, 7, '7c81c9f63026a4cafcf7d27ab63e89e1b0854251b33bb455d798ea85b20a8295', '2025-01-18 03:53:23', '2024-12-19 03:53:23'),
(4, 7, '8eb0ee5a71726946358d0e86e51ae40f7a6d690a1303b100f6d1f0c3ebf90a59', '2025-01-18 04:43:16', '2024-12-19 04:43:16');

-- --------------------------------------------------------

--
-- Table structure for table `reset_tokens`
--

CREATE TABLE `reset_tokens` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `token` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_classes`
--

CREATE TABLE `teacher_classes` (
  `teacher_id` int NOT NULL,
  `class_id` int NOT NULL,
  `is_owner` tinyint(1) DEFAULT '0',
  `can_modify` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_classes`
--

INSERT INTO `teacher_classes` (`teacher_id`, `class_id`, `is_owner`, `can_modify`, `created_at`) VALUES
(7, 14, 1, 1, '2024-12-28 13:32:21'),
(7, 22, 1, 1, '2024-12-30 07:37:12'),
(7, 24, 1, 1, '2024-12-30 07:44:24');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_invite_links`
--

CREATE TABLE `teacher_invite_links` (
  `id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `invite_code` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_invite_links`
--

INSERT INTO `teacher_invite_links` (`id`, `teacher_id`, `invite_code`, `expires_at`, `used_by`, `created_at`, `status`) VALUES
(5, 7, '815525a083731e2cf04ac382de93190a', '2025-12-05 18:48:32', 8, '2024-12-05 18:48:35', 'inactive'),
(6, 7, '93b454b732bcc3652179a3b9ae80d6a2', '2025-12-05 19:09:01', NULL, '2024-12-05 19:09:04', 'inactive'),
(7, 7, '269eacb489461eda4c4bf1f64c5b0836', '2025-12-20 14:52:06', NULL, '2024-12-20 14:52:06', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_profiles`
--

CREATE TABLE `teacher_profiles` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `city` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qualification` text COLLATE utf8mb4_general_ci,
  `bio` text COLLATE utf8mb4_general_ci,
  `linkedin_profile` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `expertise` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `teaching_certifications` text COLLATE utf8mb4_general_ci,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_profiles`
--

INSERT INTO `teacher_profiles` (`id`, `user_id`, `address`, `city`, `state`, `postal_code`, `phone`, `email`, `qualification`, `bio`, `linkedin_profile`, `emergency_contact_phone`, `expertise`, `teaching_certifications`, `status`, `approved_at`, `approved_by`, `created_at`, `updated_at`) VALUES
(3, 7, '193/ A Udagama, Kirindiwela', 'Kirindiwela', 'Western', '11730', '0718782632', 'skw308@gmail.com', 'tesrt', 'test', 'https://lk.linkedin.com/', '0718782632', 'test', 'test', 'approved', '2024-12-19 06:03:14', 1, '2024-12-05 12:51:57', '2024-12-19 06:03:14');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_students`
--

CREATE TABLE `teacher_students` (
  `id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `student_id` int NOT NULL,
  `status` enum('pending','accepted','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_students`
--

INSERT INTO `teacher_students` (`id`, `teacher_id`, `student_id`, `status`, `created_at`) VALUES
(1, 7, 8, 'accepted', '2024-12-05 18:56:28');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `position` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `display_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `social_linkedin` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `social_twitter` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('admin','teacher','student') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_access` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `created_at`, `last_access`) VALUES
(1, 'Eshan Adithaya lead', 'eshanguanthilaka10@gmail.com', '0766057574', '$2y$10$ahURNIn5kjB8pbqnINqJdexni8CACwmwpvD0JH.QpsyVZqFjoj0tS', 'admin', '2024-12-01 14:45:52', '2024-12-30 08:23:16'),
(7, 'Sithija Kaushalya', 'skw308@gmail.com', '0718782632', '$2y$10$68YoFcasNS9ATZgvNzvJLOPGx9Nz1c66lG0o2pnZl0mM5SYcw/UyK', 'teacher', '2024-12-05 12:51:56', '2024-12-30 08:24:34'),
(8, 'test1', 'cocpissa12@gmail.com', '0766057574', '$2y$10$OMU/3Is04AUO/nUVXOsZW.d0vE6dp4G/QIY6JOXrqVygq7nfC8fJu', 'student', '2024-12-05 18:49:49', '2024-12-22 03:54:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_page`
--
ALTER TABLE `about_page`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `classes_ibfk_1` (`created_by`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`student_id`,`class_id`),
  ADD KEY `fk_enrollments_class` (`class_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `added_by` (`added_by`),
  ADD KEY `fk_materials_class` (`class_id`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token` (`token`),
  ADD KEY `expires` (`expires`);

--
-- Indexes for table `reset_tokens`
--
ALTER TABLE `reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reset_tokens_ibfk_1` (`user_id`);

--
-- Indexes for table `teacher_classes`
--
ALTER TABLE `teacher_classes`
  ADD PRIMARY KEY (`teacher_id`,`class_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `idx_class_ownership` (`is_owner`);

--
-- Indexes for table `teacher_invite_links`
--
ALTER TABLE `teacher_invite_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_invite_code` (`invite_code`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `used_by` (`used_by`);

--
-- Indexes for table `teacher_profiles`
--
ALTER TABLE `teacher_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_teacher_status` (`status`),
  ADD KEY `idx_teacher_approval` (`approved_at`);

--
-- Indexes for table `teacher_students`
--
ALTER TABLE `teacher_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_student` (`teacher_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_page`
--
ALTER TABLE `about_page`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reset_tokens`
--
ALTER TABLE `reset_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_invite_links`
--
ALTER TABLE `teacher_invite_links`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `teacher_profiles`
--
ALTER TABLE `teacher_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teacher_students`
--
ALTER TABLE `teacher_students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `about_page`
--
ALTER TABLE `about_page`
  ADD CONSTRAINT `about_page_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_enrollments_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `materials`
--
ALTER TABLE `materials`
  ADD CONSTRAINT `fk_materials_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `materials_ibfk_2` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reset_tokens`
--
ALTER TABLE `reset_tokens`
  ADD CONSTRAINT `reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `teacher_classes`
--
ALTER TABLE `teacher_classes`
  ADD CONSTRAINT `teacher_classes_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_classes_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_invite_links`
--
ALTER TABLE `teacher_invite_links`
  ADD CONSTRAINT `teacher_invite_links_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `teacher_invite_links_ibfk_2` FOREIGN KEY (`used_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `teacher_profiles`
--
ALTER TABLE `teacher_profiles`
  ADD CONSTRAINT `teacher_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_profiles_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teacher_students`
--
ALTER TABLE `teacher_students`
  ADD CONSTRAINT `teacher_students_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `teacher_students_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

```

## Core Features & Flow

### Authentication
1. Signup
   - Simple form: name, email, phone, password
   - Email verification
   - Default role: student

2. Login
   - Email/password
   - Role-based redirect

3. Password Reset
   - Request with email
   - 6-digit OTP via email
   - 15-minute expiry
   - New password entry

### Admin Dashboard
1. Classes Management
   - Create/edit classes
   - Upload materials
   - Student enrollment

2. User Management
   - View all users
   - Edit user details
   - Reset passwords

### Student Dashboard
1. My Classes
   - View enrolled classes
   - Access materials

2. Profile
   - Update personal info
   - Change password

## Security Measures
- Password hashing (bcrypt)
- Session management
- Input sanitization
- CSRF protection
- Role-based access control

## Tech Stack
- Frontend: HTML, JavaScript, Tailwind CSS
- Backend: PHP
- Database: MySQL
- File Storage: Local server storage