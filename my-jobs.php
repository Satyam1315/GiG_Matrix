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

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Handle job deletion
if (isset($_POST['delete_job'])) {
    $job_id = $_POST['job_id'];
    
    // First delete related applications
    $delete_applications = "DELETE FROM job_applications WHERE job_id = ?";
    $stmt = $conn->prepare($delete_applications);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    
    // Then delete the job
    $delete_job = "DELETE FROM jobs WHERE id = ? AND developer_id = ?";
    $stmt = $conn->prepare($delete_job);
    $stmt->bind_param("ii", $job_id, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Job deleted successfully";
    } else {
        $_SESSION['error_message'] = "Error deleting job";
    }
    
    header("Location: my-jobs.php");
    exit();
}

// Fetch all jobs posted by the developer
$query = "SELECT j.*, 
          (SELECT COUNT(*) FROM job_applications a WHERE a.job_id = j.id) as application_count 
          FROM jobs j 
          WHERE j.developer_id = ? 
          ORDER BY j.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs - Gig Matrix</title>
    <link rel="stylesheet" href="style.css">
    <!-- <link rel="stylesheet" href="dashboard.css"> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .jobs-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 100px 1.5rem 2rem;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            color: #fff;
            font-size: 2rem;
            margin: 0;
        }

        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .job-card {
            background: rgba(26, 26, 26, 0.98);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .job-title {
            color: #fff;
            font-size: 1.2rem;
            margin: 0;
            flex: 1;
        }

        .job-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-open {
            background: rgba(46, 213, 115, 0.2);
            color: #2ed573;
            border: 1px solid rgba(46, 213, 115, 0.3);
        }

        .status-closed {
            background: rgba(255, 71, 87, 0.2);
            color: #ff4757;
            border: 1px solid rgba(255, 71, 87, 0.3);
        }

        .job-description {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .job-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        .job-budget {
            color: #00c6ff;
            font-weight: 600;
        }

        .job-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            padding: 0.7rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-edit {
            background: linear-gradient(135deg, #ffc107, #ffa502);
            color: #fff;
            /* box-shadow: 0 4px 15px rgba(255, 193, 7, 0.2); */
        }

        .btn-delete {
            background: #dc3545;
            color: #fff;
            border: 2px solid #dc3545;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }


        .btn-edit:hover {
            border-color: rgba(255, 255, 255, 0.5);
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #e63e4d, #e65a6f);
        }

        .no-jobs {
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            padding: 3rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px dashed rgba(255, 255, 255, 0.1);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alert-success {
            background: rgba(46, 213, 115, 0.2);
            color: #2ed573;
            border: 1px solid rgba(46, 213, 115, 0.3);
        }

        .alert-error {
            background: rgba(255, 71, 87, 0.2);
            color: #ff4757;
            border: 1px solid rgba(255, 71, 87, 0.3);
        }

        .alert-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0;
        }

        @media (max-width: 768px) {
            .jobs-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .job-actions {
                flex-wrap: wrap;
            }

            .btn-action {
                flex: 1;
                justify-content: center;
            }
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
                <a href="developer-dashboard.php">GiG Matrix</a>
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
        </div>
    </header>

    <main class="jobs-container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
                <button class="alert-close">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
                <button class="alert-close">&times;</button>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1 class="page-title">My Jobs</h1>
            <a href="post-job.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i>
                Post New Job
            </a>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="jobs-grid">
                <?php while ($job = $result->fetch_assoc()): ?>
                    <div class="job-card">
                        <div class="job-header">
                            <h2 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h2>
                            <span class="job-status <?php echo $job['status'] === 'open' ? 'status-open' : 'status-closed'; ?>">
                                <?php echo ucfirst($job['status']); ?>
                            </span>
                        </div>
                        <p class="job-description"><?php echo htmlspecialchars($job['description']); ?></p>
                        <div class="job-meta">
                            <span class="job-budget">$<?php echo number_format($job['budget'], 2); ?></span>
                            <span class="job-applications">
                                <i class="fas fa-users"></i>
                                <?php echo $job['application_count']; ?> applications
                            </span>
                        </div>
                        <div class="job-actions">
                            
                            <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn-action btn-primary">
                                <i class="fas fa-edit"></i>
                                Edit
                            </a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this job?');">
                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                <button type="submit" name="delete_job" class="btn-action btn-delete">
                                    <i class="fas fa-trash"></i>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-jobs">
                <i class="fas fa-briefcase" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h2>No Jobs Posted Yet</h2>
                <p>Start by posting your first job to find talented freelancers.</p>
                <a href="post-job.php" class="btn-post-job" style="display: inline-flex; margin-top: 1rem;">
                    <i class="fas fa-plus-circle"></i>
                    Post Your First Job
                </a>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> DevHub. All rights reserved.</p>
    </footer>

    <script>
        // Handle alert dismissal
        document.querySelectorAll('.alert-close').forEach(button => {
            button.addEventListener('click', () => {
                button.parentElement.remove();
            });
        });
    </script>
</body>
</html> 