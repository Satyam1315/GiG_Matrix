<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Fetch conversations based on user role
if ($role === 'developer') {
    $query = "SELECT DISTINCT 
                u.id as other_user_id,
                u.username as other_username,
                u.role as other_role,
                MAX(m.created_at) as last_message_time
            FROM messages m
            JOIN users u ON (
                (m.sender_id = u.id AND m.receiver_id = ?) 
                OR 
                (m.receiver_id = u.id AND m.sender_id = ?)
            )
            WHERE u.id != ?
            GROUP BY u.id
            ORDER BY last_message_time DESC";
} else {
    $query = "SELECT DISTINCT 
                u.id as other_user_id,
                u.username as other_username,
                u.role as other_role,
                MAX(m.created_at) as last_message_time
            FROM messages m
            JOIN users u ON (
                (m.sender_id = u.id AND m.receiver_id = ?) 
                OR 
                (m.receiver_id = u.id AND m.sender_id = ?)
            )
            WHERE u.id != ?
            GROUP BY u.id
            ORDER BY last_message_time DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$conversations = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - GiG Matrix</title>
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

        .messages-container {
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

        .conversations-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .conversation-card {
            background: rgba(26, 26, 26, 0.95);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .conversation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            border-color: rgba(108, 92, 231, 0.3);
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

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #6c5ce7;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
        }

        .user-details {
            flex: 1;
        }

        .user-role {
            color: #6c5ce7;
            font-size: 0.9rem;
            text-transform: capitalize;
        }

        .last-message-time {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .conversation-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-message {
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-message:hover {
            background: rgba(108, 92, 231, 0.2);
            transform: translateY(-2px);
        }

        .no-conversations {
            text-align: center;
            padding: 3rem;
            background: rgba(26, 26, 26, 0.95);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
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

        .btn-logout:hover {
            background: #d44637;
            transform: translateY(-2px);
        }

        .btn-logout:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .conversations-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <nav class="nav-container">
            <div class="logo">
                <?php if ($role === 'freelancer'): ?>
                    <a href="freelancer-dashboard.php">GiG Matrix</a>
                <?php else: ?>
                    <a href="developer-dashboard.php">GiG Matrix</a>
                <?php endif; ?>
            </div>
            <div class="auth-buttons">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                </div>
                <a href="logout.php" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </nav>
    </header>

    <main class="main-content">
        <div class="messages-container">
            <h1 class="page-title">Messages</h1>

            <div class="conversations-list">
                <?php while ($conv = $conversations->fetch_assoc()): ?>
                    <div class="conversation-card">
                        <div class="user-info">
                            <div class="user-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="user-details">
                                <div class="user-name"><?php echo htmlspecialchars($conv['other_username']); ?></div>
                                <div class="user-role"><?php echo htmlspecialchars($conv['other_role']); ?></div>
                            </div>
                        </div>

                        <div class="last-message-time">
                            Last message: <?php echo date('M j, Y g:i A', strtotime($conv['last_message_time'])); ?>
                        </div>

                        <div class="conversation-actions">
                            <?php if ($role === 'freelancer'): ?>
                                <a href="send-message.php?developer_id=<?php echo $conv['other_user_id']; ?>" class="btn-message">
                                    <i class="fas fa-envelope"></i>
                                    View Message
                                </a>
                            <?php else: ?>
                                <a href="send-message.php?freelancer_id=<?php echo $conv['other_user_id']; ?>" class="btn-message">
                                    <i class="fas fa-envelope"></i>
                                    View Message
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>

                <?php if ($conversations->num_rows === 0): ?>
                    <div class="no-conversations">
                        <p>No messages found.</p>
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