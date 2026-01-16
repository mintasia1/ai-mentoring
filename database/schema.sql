-- CUHK Law E-Mentoring Platform Database Schema
-- Created: 2026-01-15

-- Users table (base table for all user types)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('mentee', 'mentor', 'admin', 'super_admin') NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- Mentee profiles
CREATE TABLE IF NOT EXISTS mentee_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    student_id VARCHAR(50),
    programme_level ENUM('JD', 'LLB', 'LLM', 'PhD', 'Other') NOT NULL,
    year_of_study INT,
    interests TEXT,
    goals TEXT,
    practice_area_preference VARCHAR(255),
    language_preference VARCHAR(100),
    location VARCHAR(100),
    bio TEXT,
    rematch_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_practice_area (practice_area_preference)
);

-- Mentor profiles
CREATE TABLE IF NOT EXISTS mentor_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    alumni_id VARCHAR(50),
    graduation_year INT,
    programme_level ENUM('JD', 'LLB', 'LLM', 'PhD', 'Other') NOT NULL,
    practice_area VARCHAR(255) NOT NULL,
    current_position VARCHAR(255),
    company VARCHAR(255),
    expertise TEXT,
    interests TEXT,
    language VARCHAR(100),
    location VARCHAR(100),
    bio TEXT,
    max_mentees INT DEFAULT 3,
    current_mentees INT DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_practice_area (practice_area),
    INDEX idx_verified (is_verified)
);

-- Mentorship requests
CREATE TABLE IF NOT EXISTS mentorship_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentee_id INT NOT NULL,
    mentor_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'declined', 'completed', 'cancelled') DEFAULT 'pending',
    message TEXT,
    mentor_response TEXT,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (mentee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_mentee (mentee_id),
    INDEX idx_mentor (mentor_id),
    INDEX idx_status (status),
    INDEX idx_requested_at (requested_at)
);

-- Active mentorships
CREATE TABLE IF NOT EXISTS mentorships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentee_id INT NOT NULL,
    mentor_id INT NOT NULL,
    request_id INT NOT NULL,
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    status ENUM('active', 'completed', 'terminated') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mentee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES mentorship_requests(id) ON DELETE CASCADE,
    INDEX idx_mentee (mentee_id),
    INDEX idx_mentor (mentor_id),
    INDEX idx_status (status)
);

-- Workspace (for communication and goal tracking)
CREATE TABLE IF NOT EXISTS workspace_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentorship_id INT NOT NULL,
    created_by INT NOT NULL,
    item_type ENUM('note', 'goal', 'milestone') NOT NULL,
    title VARCHAR(255),
    content TEXT NOT NULL,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    due_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mentorship_id) REFERENCES mentorships(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_mentorship (mentorship_id),
    INDEX idx_type (item_type),
    INDEX idx_status (status)
);

-- Matching scores (for smart matching algorithm)
CREATE TABLE IF NOT EXISTS matching_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentee_id INT NOT NULL,
    mentor_id INT NOT NULL,
    practice_area_match BOOLEAN DEFAULT FALSE,
    programme_match BOOLEAN DEFAULT FALSE,
    interest_score DECIMAL(3,2) DEFAULT 0.00,
    location_match BOOLEAN DEFAULT FALSE,
    language_match BOOLEAN DEFAULT FALSE,
    total_score DECIMAL(5,2) DEFAULT 0.00,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mentee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_mentee (mentee_id),
    INDEX idx_mentor (mentor_id),
    INDEX idx_score (total_score)
);

-- Audit logs (for super admin)
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);

-- Sessions table
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
);

-- Insert default super admin (password: admin123 - CHANGE IN PRODUCTION)
-- Password hash generated with: password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO users (email, password_hash, role, first_name, last_name) 
VALUES ('admin@cuhk.edu.hk', '$2y$10$h6lrp9K0Sh.cXBxyF3KvTOdAF6.SGwIuEzkt6XXAAKBz73XpuhFte', 'super_admin', 'System', 'Admin')
ON DUPLICATE KEY UPDATE email=email;
