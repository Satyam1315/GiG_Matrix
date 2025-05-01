<?php
session_start();
include 'db_config.php';

// Check if user is logged in and is a client
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'developer') {
    header("Location: login.html");
    exit();
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_title = trim($_POST['job_title']);
    $job_description = trim($_POST['job_description']);
    $budget = floatval($_POST['budget']);
    $client_id = $_SESSION['user_id'];
    
    // Validate inputs
    if (empty($job_title) || empty($job_description) || $budget <= 0) {
        $error = "Please fill in all fields correctly.";
    } else {
        // Insert job into database
        $query = "INSERT INTO jobs (title, description, budget, developer_id, status, created_at) 
                  VALUES (?, ?, ?, ?, 'open', NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssdi", $job_title, $job_description, $budget, $client_id);
        
        if ($stmt->execute()) {
            $message = "Job posted successfully!";
            // Redirect to the job details page or dashboard
            header("Location: developer-dashboard.php");
            exit();
        } else {
            $error = "Error posting job. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GiG Matrix</title>
    <link rel="stylesheet" href="style.css">
    <!-- <link rel="stylesheet" href="dashboard.css"> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Post Job Page Specific Styles */
        .main-content {
            padding-top: 50px;
        }

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 100px 1.5rem 2rem;
            animation: fadeIn 0.5s ease;
            background: rgba(26, 26, 26, 0.98);
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-container h1 {
            margin-bottom: 30px;
            color: #fff;
            font-size: 28px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .form-container h1:after {
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
        
        .job-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #fff;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .form-group input,
        .form-group textarea {
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: rgba(255, 255, 255, 0.05);
            color: #fff;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }
        
        .btn-submit {
            background: #6c5ce7;
            color: white;
            border: none;
            border-radius: 4px;
            border-color: #6c5ce7;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            align-self: flex-end;
            width: auto;
            min-width: 150px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-submit:hover {
            background: #5649c0;
            border-color: #5649c0;;
        }
        
        .btn-submit:active {
            transform: translateY(1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
                margin: 10px;
            }
            
            .btn-submit {
                width: 100%;
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
                <a href="my-jobs.php" class="nav-link">
                    <i class="fas fa-briefcase"></i>
                    My Jobs
                </a>
                <a href="applications.php" class="nav-link">
                    <i class="fas fa-file-alt"></i>
                    Applications
                </a>
            </div>
            <div class="auth-buttons">
                <a href="logout.php" class="btn btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="form-container">
            <h1>Post a New Job</h1>
            
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

            <form method="POST" class="job-form">
                <div class="form-group">
                    <label for="job_title">Job Title</label>
                    <input type="text" id="job_title" name="job_title" required 
                           placeholder="e.g., Web Developer Needed for E-commerce Site">
                </div>

                <div class="form-group">
                    <label for="job_description">Job Description</label>
                    <textarea id="job_description" name="job_description" rows="6" required
                              placeholder="Describe the job requirements, skills needed, and project details"></textarea>
                </div>

                <div class="form-group">
                    <label for="budget">Budget ($)</label>
                    <input type="number" id="budget" name="budget" min="1" required
                           placeholder="Enter your budget in dollars">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-submit">Post Job</button>
                </div>
            </form>
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