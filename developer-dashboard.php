<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include 'db_config.php';

// Check if user is logged in and is a developer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'developer') {
    header("Location: login.html");
    exit();
}

// Get user information from session
$username = $_SESSION['username'];
$email = $_SESSION['email'];

// Fetch freelancers who have applied to developer's jobs
$query = "SELECT DISTINCT u.id, u.username 
          FROM users u 
          JOIN job_applications ja ON u.id = ja.freelancer_id 
          JOIN jobs j ON ja.job_id = j.id 
          WHERE j.developer_id = ? AND u.role = 'freelancer'
          ORDER BY u.username";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$freelancers = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Developer Dashboard</title>
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

        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('images/developer.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            flex: 1;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
        }

        .footer {
            margin-top: 0;
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

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-toggle {
            padding-right: 2rem !important;
            position: relative;
        }

        .dropdown-toggle::after {
            content: '\f107';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .dropdown-content {
            position: absolute;
            background: rgba(26, 26, 26, 0.95);
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-height: 300px;
            overflow-y: auto;
            width: 100%;
            margin-top: 0.5rem;
            display: none; /* Hidden by default */
            opacity: 1;
            transition: opacity 0.2s ease-in-out;
        }

        .dropdown-content.show {
            opacity: 1;
            display: block;
        }

        .dropdown-content a {
            color: #fff;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }

        .dropdown-content a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .dropdown-item-disabled {
            color: rgba(255, 255, 255, 0.5);
            padding: 12px 16px;
            display: block;
            cursor: not-allowed;
        }

        /* Scrollbar styles for dropdown */
        .dropdown-content::-webkit-scrollbar {
            width: 8px;
        }

        .dropdown-content::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }

        .dropdown-content::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }

        .dropdown-content::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            margin-top: 2rem;
        }

        .hero-buttons .btn {
            min-width: 180px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
        }

        .dropdown {
            min-width: 180px;
        }

        .dropdown-toggle {
            width: 100%;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
        }

        .dropdown-content {
            min-width: 180px;
            width: 100%;
            margin-top: 0.5rem;
        }

        .dropdown-content a {
            text-align: left;
            padding: 0.8rem 1.2rem;
        }

        .dropdown-item-disabled {
            padding: 0.8rem 1.2rem;
            color: rgba(255, 255, 255, 0.5);
            display: block;
        }
    </style>
</head>

<body>
    <header class="main-header">
        <nav class="nav-container">
            <div class="logo">
                <a href="developer-dashboard.php">GiG Matrix</a>
            </div>
            <div class="auth-buttons">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                </div>
                <a href="logout.php" class="btn btn-logout">Logout</a>
            </div>
        </nav>
    </header>

    <main class="hero-section">
        <div class="container hero-container">
            <div class="hero-content">
                <h1>Welcome <span><?php echo htmlspecialchars($username); ?></span></h1>
                <p class="hero-description">Post jobs and manage applications from talented freelancers</p>
                <div class="hero-buttons">
                    <a href="post-job.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i>
                        Post New Job
                    </a>
                    <a href="my-jobs.php" class="btn btn-secondary">
                        <i class="fas fa-briefcase"></i>
                        My Jobs
                    </a>
                    <a href="applications.php" class="btn btn-secondary">
                        <i class="fas fa-file-alt"></i>
                        Applications
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle">
                            <i class="fas fa-envelope"></i>
                            Messages
                        </button>
                        <div class="dropdown-content">
                            <?php if ($freelancers->num_rows > 0): ?>
                                <?php while ($freelancer = $freelancers->fetch_assoc()): ?>
                                    <a href="send-message.php?freelancer_id=<?php echo $freelancer['id']; ?>">
                                        <?php echo htmlspecialchars($freelancer['username']); ?>
                                    </a>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <span class="dropdown-item-disabled">No freelancers to message</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownToggle = document.querySelector('.dropdown-toggle');
            const dropdownContent = document.querySelector('.dropdown-content');
            
            // Toggle dropdown when clicking the button
            dropdownToggle.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent event from bubbling to document
                dropdownContent.classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!dropdownContent.contains(e.target)) {
                    dropdownContent.classList.remove('show');
                }
            });
        });
    </script>
</body>

</html>