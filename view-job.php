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

// Check if job ID is provided
if (!isset($_GET['id'])) {
    header("Location: my-applications.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$job_id = $_GET['id'];

// Fetch job details and application status
$query = "SELECT j.*, u.username as employer_name, u.email as employer_email,
                 a.status as application_status, a.created_at as application_date,
                 a.cover_letter
          FROM jobs j
          JOIN users u ON j.developer_id = u.id
          LEFT JOIN job_applications a ON j.id = a.job_id AND a.freelancer_id = ?
          WHERE j.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $job_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: my-applications.php");
    exit();
}

$job = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['title']); ?> - GiG Matrix</title>
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

        .job-container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(26, 26, 26, 0.95);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c5ce7;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #a55eea;
        }

        .job-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .job-title {
            font-size: 2rem;
            color: #fff;
            margin-bottom: 1rem;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .budget {
            color: #00b894;
            font-weight: 600;
        }

        .employer-info {
            color: #6c5ce7;
        }

        .job-description {
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            margin-bottom: 2rem;
            white-space: pre-line;
        }

        .requirements {
            margin-bottom: 2rem;
        }

        .requirements h3 {
            color: #fff;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .requirements ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .requirements li {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
            position: relative;
        }

        .requirements li::before {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: #6c5ce7;
            position: absolute;
            left: 0;
        }

        .application-status {
            margin-top: 2rem;
            padding: 2rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
        }

        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .status-badge {
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

        .application-details {
            margin-top: 1rem;
        }

        .detail-item {
            margin-bottom: 1rem;
        }

        .detail-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .detail-value {
            color: #fff;
            font-size: 1rem;
        }

        .cover-letter {
            white-space: pre-line;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .job-container {
                padding: 1rem;
            }

            .job-meta {
                gap: 1rem;
            }

            .job-title {
                font-size: 1.5rem;
            }
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
        <div class="job-container">
            <a href="my-applications.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to My Applications
            </a>

            <div class="job-header">
                <h1 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h1>
                <div class="job-meta">
                    <div class="meta-item">
                        <i class="fas fa-money-bill"></i>
                        <span class="budget">$<?php echo number_format($job['budget'], 2); ?></span>
                    </div>
                    <div class="meta-item employer-info">
                        <i class="fas fa-building"></i>
                        <span><?php echo htmlspecialchars($job['employer_name']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Posted <?php echo date('M d, Y', strtotime($job['created_at'])); ?></span>
                    </div>
                    <?php if (isset($job['duration']) && !empty($job['duration'])): ?>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo htmlspecialchars($job['duration']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="job-content">
                <div class="job-description">
                    <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                </div>

                <?php if (!empty($job['requirements'])): ?>
                <div class="requirements">
                    <h3>Requirements</h3>
                    <ul>
                        <?php
                        $requirements = explode("\n", $job['requirements']);
                        foreach ($requirements as $requirement):
                            if (trim($requirement)):
                        ?>
                            <li><?php echo htmlspecialchars(trim($requirement)); ?></li>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if ($job['application_status']): ?>
                <div class="application-status">
                    <div class="status-header">
                        <h3>Your Application Status</h3>
                        <span class="status-badge status-<?php echo strtolower($job['application_status']); ?>">
                            <?php echo ucfirst($job['application_status']); ?>
                        </span>
                    </div>
                    <div class="application-details">
                        <div class="detail-item">
                            <div class="detail-label">Applied On</div>
                            <div class="detail-value">
                                <?php echo date('F d, Y', strtotime($job['application_date'])); ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Proposed Amount</div>
                            <div class="detail-value">$<?php echo number_format($job['budget'], 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Cover Letter</div>
                            <div class="detail-value cover-letter">
                                <?php echo nl2br(htmlspecialchars($job['cover_letter'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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