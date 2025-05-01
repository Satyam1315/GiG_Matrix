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

// Check if job ID is provided
if (!isset($_GET['id'])) {
    header("Location: my-jobs.php");
    exit();
}

$job_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $budget = floatval($_POST['budget']);
    $status = $_POST['status'];
    $category = trim($_POST['category']);
    
    // Validate input
    $errors = [];
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    if ($budget <= 0) {
        $errors[] = "Budget must be greater than 0";
    }
    if (!in_array($status, ['open', 'closed'])) {
        $errors[] = "Invalid status";
    }
    if (empty($category)) {
        $errors[] = "Category is required";
    }
    
    if (empty($errors)) {
        // Update job
        $query = "UPDATE jobs SET title = ?, description = ?, budget = ?, status = ?, category = ? WHERE id = ? AND developer_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssdssii", $title, $description, $budget, $status, $category, $job_id, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Job updated successfully";
            header("Location: view-job.php?id=" . $job_id);
            exit();
        } else {
            $errors[] = "Error updating job";
        }
    }
}

// Fetch job details
$query = "SELECT * FROM jobs WHERE id = ? AND developer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $job_id, $user_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

// If job not found or doesn't belong to user, redirect
if (!$job) {
    header("Location: my-jobs.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job - Gig Matrix</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .edit-job-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 100px 1.5rem 2rem;
            animation: fadeIn 0.5s ease;
        }

        .edit-job-form {
            background: rgba(26, 26, 26, 0.98);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: #fff;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #6c5ce7;
            background: rgba(255, 255, 255, 0.1);
        }

        .form-group textarea {
            min-height: 200px;
            resize: vertical;
        }

        /* Select styling */
        .form-group select {
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.2em;
            padding-right: 2.5rem;
        }

        .form-group select:focus {
            box-shadow: 0 0 0 2px rgba(108, 92, 231, 0.2);
        }

        .form-group select option {
            background-color: #1a1a1a;
            color: #fff;
            padding: 0.8rem;
        }

        .form-group select option:hover {
            background-color: #2d2d2d;
        }

        .form-group select:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .form-group select.error {
            border-color: #ff4757;
        }

        .form-group select.error:focus {
            box-shadow: 0 0 0 2px rgba(255, 71, 87, 0.2);
        }

        .error-message {
            color: #ff4757;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #6c5ce7;
            color: #fff;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #fff;
            text-decoration: none;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            color: #6c5ce7;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            .form-actions {
                flex-direction: column;
            }

            .btn {
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
                <a href="index.html">GiG Matrix</a>
            </div>
            <div class="auth-buttons">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
                <a href="logout.php" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </header>

    <main class="edit-job-container">
        <a href="view-job.php?id=<?php echo $job_id; ?>" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Job Details
        </a>

        <div class="edit-job-form">
            <h1 class="page-title">Edit Job</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                    <button class="alert-close">&times;</button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Job Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($job['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Job Description</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($job['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="budget">Budget ($)</label>
                    <input type="number" id="budget" name="budget" step="0.01" min="0" value="<?php echo htmlspecialchars($job['budget']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select a category</option>
                        <option value="web" <?php echo $job['category'] === 'web' ? 'selected' : ''; ?>>Web Development</option>
                        <option value="mobile" <?php echo $job['category'] === 'mobile' ? 'selected' : ''; ?>>Mobile Development</option>
                        <option value="design" <?php echo $job['category'] === 'design' ? 'selected' : ''; ?>>Design</option>
                        <option value="marketing" <?php echo $job['category'] === 'marketing' ? 'selected' : ''; ?>>Marketing</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="open" <?php echo $job['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="closed" <?php echo $job['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                    <a href="view-job.php?id=<?php echo $job_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
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