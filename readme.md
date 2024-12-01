# System Architecture

## Database Tables
```sql
-- Users table (both admin and students)
-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255),
    role ENUM('admin', 'student'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Classes table
CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Class materials
CREATE TABLE materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT,
    title VARCHAR(100),
    type ENUM('link', 'pdf', 'image'),
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Student enrollments
CREATE TABLE enrollments (
    student_id INT,
    class_id INT,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id, class_id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Password reset tokens
CREATE TABLE reset_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    token VARCHAR(6),
    expires_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

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