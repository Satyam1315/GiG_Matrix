<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include 'db_config.php';

// Check if user is logged in and is a freelancer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: login.html");
    exit();
}

// Get user information from session
$username = $_SESSION['username'];
$email = $_SESSION['email'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Freelancer Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
        }

        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('images/freelancer.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            flex: 1;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer {
            margin-top: 0;
            /* padding: 1rem 0; */
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            transition: all 0.3s ease;
            flex-wrap: wrap;
        }

        .user-info:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .user-name {
            font-weight: 600;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }

        .user-name::before {
            content: '\f007';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: #6c5ce7;
            flex-shrink: 0;
        }

        .btn-logout {
            background: #e74c3c;
            color: #ffffff;
            padding: 0.7rem 1.4rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <header class="main-header">
        <div class="nav-container">
            <div class="logo">
                <a href="index.html">GiG Matrix</a>
            </div>
            <div class="auth-buttons">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                </div>
                <a href="logout.php" class="btn btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <section class="hero-section">
            <div class="hero-content">
                <h1>Welcome <span><?php echo htmlspecialchars($username); ?></span></h1>
                <p class="hero-description">Find jobs, manage applications, and grow your freelance career</p>
                <div class="hero-buttons">
                    <a href="browse-jobs.php" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Find Jobs
                    </a>
                    <a href="my-applications.php" class="btn btn-secondary">
                        <i class="fas fa-file-alt"></i>
                        My Applications
                    </a>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-user"></i>
                        Profile
                    </a>
                    <a href="messages.php" class="btn btn-secondary">
                        <i class="fas fa-envelope"></i>
                        Messages
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-section">
          <h3>About Us</h3>
          <p>Connecting businesses with top talent in Asia. We help companies grow by providing access to
            skilled professionals who work in your time zone.</p>
        </div>

        <div class="footer-section">
          <h3>Quick Links</h3>
          <ul class="footer-links">
            <li><a href="#">How it Works</a></li>
            <!-- <li><a href="#">Pricing</a></li> -->
            <!-- <li><a href="#">Testimonials</a></li> -->
            <li><a href="#">Careers</a></li>
            <li><a href="#">Blog</a></li>
          </ul>
        </div>

        <div class="footer-section">
          <h3>For Talents</h3>
          <ul class="footer-links">
            <li><a href="#">Find Work</a></li>
            <li><a href="browse-jobs.html">Browse Jobs</a></li>
            <!-- <li><a href="#">Skill Tests</a></li> -->
            <!-- <li><a href="#">Resources</a></li> -->
            <li><a href="#">Community</a></li>
          </ul>
        </div>

        <div class="footer-section">
          <h3>Contact Us</h3>
          <ul class="contact-info">
            <li><i class="fas fa-envelope"></i> gigsupport@gmail.com</li>
            <li><i class="fas fa-phone"></i> +91 9876543210</li>
            <li><i class="fas fa-map-marker-alt"></i> Chambal, Bihar, India</li>
          </ul>
        </div>
      </div>

      <div class="social-links">
        <a href="https://www.facebook.com" class="social-link"><i class="fab fa-facebook-f"></i></a>
        <a href="https://www.twitter.com" class="social-link"><i class="fab fa-twitter"></i></a>
        <a href="https://www.linkedin.com" class="social-link"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://www.instagram.com" class="social-link"><i class="fab fa-instagram"></i></a>
        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
      </div>

      <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> GiG Matrix. All rights reserved.</p>
        <div class="legal-links">
          <a href="#">Privacy Policy</a>
          <a href="#">Terms of Service</a>
          <a href="#">Cookie Policy</a>
        </div>
      </div>
    </div>
    </footer>
</body>

</html>