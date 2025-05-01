-- Create jobs table
CREATE TABLE `jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `budget` decimal(10,2) NOT NULL,
  `required_skills` text NOT NULL,
  `status` enum('open','in_progress','completed','cancelled') NOT NULL DEFAULT 'open',
  `category` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create job_applications table
CREATE TABLE `job_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `resume_path` varchar(255) NOT NULL,
  `cover_letter` text NOT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_application` (`job_id`,`freelancer_id`),
  KEY `freelancer_id` (`freelancer_id`),
  CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_applications_ibfk_2` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE messages (
         id INT AUTO_INCREMENT PRIMARY KEY,
         sender_id INT NOT NULL,
         receiver_id INT NOT NULL,
         message TEXT NOT NULL,
         created_at DATETIME NOT NULL,
         FOREIGN KEY (sender_id) REFERENCES users(id),
         FOREIGN KEY (receiver_id) REFERENCES users(id)
);


CREATE TABLE IF NOT EXISTS freelancer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(255),
    bio TEXT,
    skills TEXT,
    experience TEXT,
    education TEXT,
    portfolio_url VARCHAR(255),
    linkedin_url VARCHAR(255),
    github_url VARCHAR(255),
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
); 

-- Insert sample jobs for testing
INSERT INTO `jobs` (`client_id`, `title`, `description`, `budget`, `required_skills`, `category`) VALUES
(1, 'WordPress E-commerce Website', 'Need a professional WordPress developer to create an e-commerce website with WooCommerce integration.', 1500.00, 'WordPress,PHP,WooCommerce,HTML,CSS', 'web'),
(1, 'Mobile App Development - iOS', 'Looking for an experienced iOS developer to create a fitness tracking app.', 3000.00, 'Swift,iOS,CoreData,UIKit', 'mobile'),
(1, 'Frontend React Developer', 'Need a React developer to build a modern, responsive dashboard interface.', 2000.00, 'React,JavaScript,HTML,CSS,Redux', 'web'),
(1, 'Python Backend Developer', 'Looking for a Python developer to create RESTful APIs using Django.', 2500.00, 'Python,Django,REST API,PostgreSQL', 'web'),
(1, 'Cross-platform Mobile App', 'Need a Flutter developer to build a cross-platform messaging app.', 4000.00, 'Flutter,Dart,Firebase,Mobile Development', 'mobile'); 