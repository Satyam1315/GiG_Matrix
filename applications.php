<?php
session_start();
include 'db_config.php';

// Check if user is logged in and is a client
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'developer') {
    header("Location: login.html");
    exit();
}

// Initialize message variables
$message = '';
$error = '';

// Handle application status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id']) && isset($_POST['status'])) {
    $application_id = intval($_POST['application_id']);
    $new_status = $_POST['status'];
    $client_id = $_SESSION['user_id'];

    // Verify that this application belongs to one of the client's jobs
    $verify_query = "SELECT a.id FROM job_applications a 
                    JOIN jobs j ON a.job_id = j.id 
                    WHERE a.id = ? AND j.developer_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $application_id, $client_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update application status
        $update_query = "UPDATE job_applications SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $new_status, $application_id);

        if ($stmt->execute()) {
            // Store success message in session
            $_SESSION['success_message'] = "Application status updated successfully!";
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            // Store error message in session
            $_SESSION['error_message'] = "Error updating application status.";
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } else {
        // Store error message in session
        $_SESSION['error_message'] = "Invalid application.";
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get messages from session and clear them
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Get all applications for the client's jobs
$applications_query = "SELECT a.*, j.title as job_title, j.description as job_description, 
                             j.budget as job_budget, u.username as applicant_name, 
                             u.email as applicant_email, a.cover_letter,
                             fp.profile_picture
                      FROM job_applications a 
                      JOIN jobs j ON a.job_id = j.id 
                      JOIN users u ON a.freelancer_id = u.id 
                      LEFT JOIN freelancer_profiles fp ON u.id = fp.user_id
                      WHERE j.developer_id = ? 
                      ORDER BY a.created_at DESC";
$stmt = $conn->prepare($applications_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$applications = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications - DevHub</title>
    <link rel="stylesheet" href="style.css">
    <!-- <link rel="stylesheet" href="dashboard.css"> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Applications Page Specific Styles */
        .applications-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .applications-container h1 {
            margin-bottom: 30px;
            color: #fff;
            font-size: 28px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            padding-bottom: 15px;
        }

        .applications-container h1:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, #007bff, #00c6ff);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideDown 0.5s ease-out forwards;
            max-width: 500px;
            width: 90%;
        }

        @keyframes slideDown {
            0% {
                transform: translate(-50%, -100%);
                opacity: 0;
            }

            100% {
                transform: translate(-50%, 0);
                opacity: 1;
            }
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.9);
            color: #fff;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .alert-error {
            background-color: rgba(220, 53, 69, 0.9);
            color: #fff;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .alert-close {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
            font-size: 18px;
            color: inherit;
            opacity: 0.7;
        }

        .alert-close:hover {
            opacity: 1;
        }

        .applications-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .application-card {
            background: rgba(26, 26, 26, 0.98);
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            padding: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .application-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
        }

        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .applicant-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .applicant-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .applicant-info h3 {
            margin: 0;
            font-size: 18px;
            color: #fff;
        }

        .job-title {
            margin: 5px 0 0;
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }

        .application-status {
            padding: 0.7rem 1.4rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: inline-block;
            text-align: center;
            min-width: 120px;
        }

        .application-status::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 150%;
            height: 150%;
            background: rgba(255, 255, 255, 0.1);
            transform: translate(-50%, -50%) rotate(45deg) scale(0);
            transition: transform 0.6s ease;
        }

        .application-status:hover::before {
            transform: translate(-50%, -50%) rotate(45deg) scale(1);
        }

        .application-status.pending {
            background: #ffc107;
            color: #000;
            border: 2px solid #ffc107;
        }

        .application-status.accepted {
            background: #28a745;
            color: #fff;
            border: 2px solid #28a745;
        }

        .application-status.rejected {
            background: #dc3545;
            color: #fff;
            border: 2px solid #dc3545;
        }

        .application-status.completed {
            background: #17a2b8;
            color: #fff;
            border: 2px solid #17a2b8;
        }

        .application-status:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .application-details {
            margin-bottom: 20px;
            padding: 15px;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 5px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .application-date {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin-bottom: 10px;
        }

        .application-section {
            margin-bottom: 15px;
        }

        .application-section h4 {
            margin: 0 0 10px;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
        }

        .application-section p {
            margin: 0 0 10px;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.5;
        }

        .application-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .status-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-select {
            padding: 0.7rem 1.4rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            background-color: rgba(26, 26, 26, 0.8);
            font-size: 0.95rem;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1rem;
            padding-right: 2.5rem;
            min-width: 150px;
        }

        .status-select:focus {
            outline: none;
            border-color: #6c5ce7;
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.2);
        }

        .status-select:hover {
            border-color: rgba(255, 255, 255, 0.4);
            background-color: rgba(26, 26, 26, 0.9);
        }

        .status-select option {
            background-color: #1a1a1a;
            color: #fff;
            padding: 0.7rem;
        }

        .btn-contact {
            display: inline-block;
            padding: 0.7rem 1.4rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        .btn-contact:hover {
            background: linear-gradient(to right, #218838, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .no-applications {
            text-align: center;
            padding: 40px;
            background: rgba(26, 26, 26, 0.98);
            border-radius: 8px;
            color: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .application-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .application-status {
                align-self: flex-start;
            }

            .application-actions {
                flex-direction: column;
                align-items: flex-start;
            }

            .status-form {
                width: 100%;
                justify-content: space-between;
            }

            .btn-contact {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <header class="main-header">
        <div class="nav-container">
            <div class="logo">
                <a href="developer-dashboard.php">GiG Matrix</a>
            </div>
            <div class="nav-links">
                <a href="developer-dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
                <a href="post-job.php" class="nav-link">
                    <i class="fas fa-plus-circle"></i>
                    Post New Job
                </a>
                <a href="my-jobs.php" class="nav-link">
                    <i class="fas fa-briefcase"></i>
                    My Jobs
                </a>
            </div>
            <div class="auth-buttons">
                <a href="logout.php" class="btn btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="applications-container">
            <h1>Job Applications</h1>

            <?php if ($message): ?>
                <div class="alert alert-success" id="success-alert">
                    <?php echo $message; ?>
                    <span class="alert-close" onclick="closeAlert('success-alert')">&times;</span>
                </div>
                <script>
                    setTimeout(function() {
                        closeAlert('success-alert');
                    }, 5000);
                </script>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error" id="error-alert">
                    <?php echo $error; ?>
                    <span class="alert-close" onclick="closeAlert('error-alert')">&times;</span>
                </div>
                <script>
                    setTimeout(function() {
                        closeAlert('error-alert');
                    }, 5000);
                </script>
            <?php endif; ?>

            <div class="applications-list">
                <?php while ($app = $applications->fetch_assoc()): ?>
                    <div class="application-card">
                        <div class="application-header">
                            <div class="applicant-info">
                                <img src="<?php 
                                    if (!empty($app['profile_picture'])) {
                                        echo 'uploads/profile_pictures/' . htmlspecialchars($app['profile_picture']);
                                    } else {
                                        echo 'images/default-avatar.png';
                                    }
                                ?>" alt="Applicant" class="applicant-avatar">
                                <div>
                                    <h3><?php echo htmlspecialchars($app['applicant_name']); ?></h3>
                                    <p class="job-title"><?php echo htmlspecialchars($app['job_title']); ?></p>
                                </div>
                            </div>
                            <span class="application-status <?php echo strtolower($app['status']); ?>">
                                <?php echo htmlspecialchars($app['status']); ?>
                            </span>
                        </div>

                        <div class="application-details">
                            <p class="application-date">
                                Applied on: <?php echo date('F j, Y', strtotime($app['created_at'])); ?>
                            </p>

                            <div class="application-section">
                                <h4>Job Details</h4>
                                <p><strong>Title:</strong> <?php echo htmlspecialchars($app['job_title']); ?></p>
                                <p><strong>Budget:</strong> $<?php echo number_format($app['job_budget'], 2); ?></p>
                                <p><?php echo nl2br(htmlspecialchars($app['job_description'])); ?></p>
                            </div>

                            <div class="application-section">
                                <h4>Freelancer's Proposal</h4>
                                <p><?php echo nl2br(htmlspecialchars($app['cover_letter'])); ?></p>
                            </div>
                        </div>

                        <div class="application-actions">
                            <form method="POST" class="status-form">
                                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                <select name="status" class="status-select">
                                    <option value="pending" <?php echo $app['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="accepted" <?php echo $app['status'] === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                    <option value="rejected" <?php echo $app['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                                <button type="submit" class="btn btn-primary">Update Status</button>
                            </form>
                            <a href="mailto:<?php echo htmlspecialchars($app['applicant_email']); ?>" class="btn-contact">
                                <i class="fas fa-envelope"></i> Contact Applicant
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>

                <?php if ($applications->num_rows === 0): ?>
                    <div class="no-applications">
                        <p>No applications found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> DevHub. All rights reserved.</p>
    </footer>

    <script>
        function closeAlert(alertId) {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 300);
            }
        }
    </script>
</body>

</html>