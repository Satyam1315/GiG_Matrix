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

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch all applications for the freelancer with job details
$query = "SELECT a.*, j.title as job_title, j.description as job_description, 
                 j.budget, j.status as job_status, u.username as employer_name 
          FROM job_applications a 
          JOIN jobs j ON a.job_id = j.id 
          JOIN users u ON j.developer_id = u.id 
          WHERE a.freelancer_id = ? 
          ORDER BY a.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$applications = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - GiG Matrix</title>
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
            padding: 2rem;
            margin-top: 60px;
        }

        .applications-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-title {
            color: #fff;
            margin-bottom: 2rem;
            font-size: 2rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .page-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, #6c5ce7, #a55eea);
        }

        .applications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .application-card {
            background: rgba(26, 26, 26, 0.95);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .application-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .job-title {
            font-size: 1.25rem;
            color: #fff;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .employer-name {
            color: #6c5ce7;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .job-description {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .application-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        .budget {
            color: #00b894;
            font-weight: 600;
        }

        .application-status {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .status-accepted {
            background: rgba(46, 213, 115, 0.2);
            color: #2ed573;
            border: 1px solid rgba(46, 213, 115, 0.3);
        }

        .status-rejected {
            background: rgba(255, 71, 87, 0.2);
            color: #ff4757;
            border: 1px solid rgba(255, 71, 87, 0.3);
        }

        .application-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn-view {
            flex: 1;
            text-align: center;
            padding: 0.8rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border: 1px solid rgba(108, 92, 231, 0.3);
        }

        .btn-view:hover {
            background: #6c5ce7;
            color: #fff;
        }

        .no-applications {
            text-align: center;
            padding: 3rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .no-applications i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.3);
        }

        .no-applications p {
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .applications-grid {
                grid-template-columns: 1fr;
            }

            .application-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="nav-container">
            <div class="logo">
                <a href="freelancer-dashboard.php">GiG Matrix</a>
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
        <div class="applications-container">
            <h1 class="page-title">My Applications</h1>

            <?php if ($applications->num_rows > 0): ?>
                <div class="applications-grid">
                    <?php while ($app = $applications->fetch_assoc()): ?>
                        <div class="application-card">
                            <h2 class="job-title"><?php echo htmlspecialchars($app['job_title']); ?></h2>
                            <div class="employer-name">
                                <i class="fas fa-building"></i>
                                <?php echo htmlspecialchars($app['employer_name']); ?>
                            </div>
                            <p class="job-description"><?php echo htmlspecialchars($app['job_description']); ?></p>
                            <div class="application-meta">
                                <span class="budget">$<?php echo number_format($app['budget'], 2); ?></span>
                                <span class="application-date">
                                    <i class="far fa-clock"></i>
                                    <?php echo date('M d, Y', strtotime($app['created_at'])); ?>
                                </span>
                            </div>
                            <div class="status-container">
                                <span class="application-status status-<?php echo strtolower($app['status']); ?>">
                                    <?php echo ucfirst($app['status']); ?>
                                </span>
                            </div>
                            <div class="application-actions">
                                <a href="view-job.php?id=<?php echo $app['job_id']; ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> View Job Details
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-applications">
                    <i class="fas fa-folder-open"></i>
                    <p>You haven't applied to any jobs yet.</p>
                    <a href="browse-jobs.php" class="btn btn-primary">Browse Available Jobs</a>
                </div>
            <?php endif; ?>
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
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Blog</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>For Talents</h3>
                    <ul class="footer-links">
                        <li><a href="#">Find Work</a></li>
                        <li><a href="browse-jobs.html">Browse Jobs</a></li>
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