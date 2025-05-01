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

// Get user information from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_picture']['type'];
        $max_file_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($file_type, $allowed_types)) {
            $error_message = 'Only JPG, PNG, and GIF files are allowed.';
        } elseif ($_FILES['profile_picture']['size'] > $max_file_size) {
            $error_message = 'File size must be less than 2MB.';
        } else {
            $upload_dir = 'uploads/profile_pictures/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Delete old profile picture if exists
            $stmt = $conn->prepare("SELECT profile_picture FROM freelancer_profiles WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $old_profile = $result->fetch_assoc();
            
            if ($old_profile && $old_profile['profile_picture']) {
                $old_picture_path = $upload_dir . $old_profile['profile_picture'];
                if (file_exists($old_picture_path)) {
                    unlink($old_picture_path);
                }
            }
            
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Update profile picture in database
                $stmt = $conn->prepare("UPDATE freelancer_profiles SET profile_picture = ? WHERE user_id = ?");
                $stmt->bind_param("si", $new_filename, $user_id);
                $stmt->execute();
                $success_message = 'Profile picture updated successfully!';
            } else {
                $error_message = 'Failed to upload profile picture.';
            }
        }
    }
    
    // Handle profile picture removal
    if (isset($_POST['remove_picture'])) {
        $stmt = $conn->prepare("SELECT profile_picture FROM freelancer_profiles WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();
        
        if ($profile && $profile['profile_picture']) {
            $picture_path = 'uploads/profile_pictures/' . $profile['profile_picture'];
            if (file_exists($picture_path)) {
                unlink($picture_path);
            }
            
            $stmt = $conn->prepare("UPDATE freelancer_profiles SET profile_picture = NULL WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $success_message = 'Profile picture removed successfully!';
        }
    }
    
    // Handle other profile updates
    if (isset($_POST['update_profile'])) {
        $full_name = htmlspecialchars(trim($_POST['full_name']));
        $bio = htmlspecialchars(trim($_POST['bio']));
        $skills = htmlspecialchars(trim($_POST['skills']));
        $experience = htmlspecialchars(trim($_POST['experience']));
        $education = htmlspecialchars(trim($_POST['education']));
        $portfolio_url = filter_var(trim($_POST['portfolio_url']), FILTER_SANITIZE_URL);
        $linkedin_url = filter_var(trim($_POST['linkedin_url']), FILTER_SANITIZE_URL);
        $github_url = filter_var(trim($_POST['github_url']), FILTER_SANITIZE_URL);
        
        $stmt = $conn->prepare("UPDATE freelancer_profiles SET 
            full_name = ?, 
            bio = ?, 
            skills = ?, 
            experience = ?, 
            education = ?, 
            portfolio_url = ?, 
            linkedin_url = ?, 
            github_url = ? 
            WHERE user_id = ?");
        
        $stmt->bind_param("ssssssssi", 
            $full_name, 
            $bio, 
            $skills, 
            $experience, 
            $education, 
            $portfolio_url, 
            $linkedin_url, 
            $github_url, 
            $user_id
        );
        
        if ($stmt->execute()) {
            $success_message = 'Profile updated successfully!';
        } else {
            $error_message = 'Error updating profile: ' . $conn->error;
        }
    }
}

// Get current profile data
$stmt = $conn->prepare("SELECT * FROM freelancer_profiles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

// If no profile exists, create one
if (!$profile) {
    $stmt = $conn->prepare("INSERT INTO freelancer_profiles (user_id) VALUES (?)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile = ['full_name' => '', 'bio' => '', 'skills' => '', 'experience' => '', 
                'education' => '', 'portfolio_url' => '', 'linkedin_url' => '', 'github_url' => ''];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - GiG Matrix</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6c5ce7;
            --primary-hover: #5649c0;
            --bg-dark: #1a1a1a;
            --bg-light: #2d2d2d;
            --text-light: #ffffff;
            --text-muted: #b3b3b3;
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        body {
            background: var(--bg-dark);
            color: var(--text-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        .btn-logout:hover {
            background: #d44637;
            transform: translateY(-2px);
        }

        .btn-logout:active {
            transform: translateY(0);
        }

        .profile-container {
            max-width: 1200px;
            margin: 100px auto;
            padding: 2rem;
            background: rgba(26, 26, 26, 0.95);
            border-radius: var(--border-radius);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-header h1 {
            color: var(--text-light);
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .profile-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .profile-form {
            display: grid;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            color: var(--text-light);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-control {
            padding: 0.8rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-light);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(108, 92, 231, 0.2);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .btn-save {
            background: var(--primary-color);
            color: var(--text-light);
            padding: 1rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
        }

        .btn-save:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            text-align: center;
            animation: fadeIn 0.3s ease;
            position: relative;
        }

        .alert .close-btn {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: inherit;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0.7;
            transition: var(--transition);
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .alert .close-btn:hover {
            opacity: 1;
        }

        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-20px); }
        }

        .alert.fade-out {
            animation: fadeOut 0.5s ease forwards;
        }

        .alert-success {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .form-group.error input,
        .form-group.error textarea {
            border-color: #e74c3c;
            background: rgba(231, 76, 60, 0.05);
        }

        .error-message {
            color: #e74c3c;
            font-size: 0.85rem;
            margin-top: 0.25rem;
            display: none;
        }

        .form-group.error .error-message {
            display: block;
        }

        .profile-picture-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
            border-radius: 50%;
            background: var(--bg-light);
            padding: 4px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .profile-picture {
            width: 180px;
            height: 180px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--primary-color);
            transition: var(--transition);
            background: var(--bg-dark);
        }

        .profile-picture-actions {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary-color);
            padding: 8px 16px;
            border-radius: 20px;
            display: flex;
            gap: 15px;
            opacity: 0;
            transition: var(--transition);
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            margin-bottom: -15px;
        }

        .profile-picture-container:hover .profile-picture-actions {
            opacity: 1;
            transform: translateX(-50%) translateY(5px);
        }

        .profile-picture-actions a {
            color: white;
            text-decoration: none;
            font-size: 1.1rem;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            transition: var(--transition);
        }

        .profile-picture-actions a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .profile-picture-placeholder {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: var(--bg-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--text-muted);
        }

        .modal-content {
            background: var(--bg-light);
            color: var(--text-light);
            border-radius: var(--border-radius);
        }

        .modal-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .modal-footer {
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .btn-close {
            filter: invert(1);
        }

        .image-preview {
            max-width: 300px;
            max-height: 300px;
            margin: 1.5rem auto;
            border-radius: var(--border-radius);
            display: none;
            object-fit: contain;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .preview-container {
            text-align: center;
            /* margin: 1.5rem 0; */
            /* padding: 1rem; */
            /* background: var(--bg-dark); */
            border-radius: var(--border-radius);
            display: none;
        }

        .loading {
            display: none;
            text-align: center;
            margin: 1rem 0;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255,255,255,0.1);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .skills-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .skill-tag {
            background: var(--primary-color);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .skill-tag .remove-skill {
            cursor: pointer;
            font-size: 1rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-link {
            color: var(--text-muted);
            font-size: 1.5rem;
            transition: var(--transition);
        }

        .social-link:hover {
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .text-muted {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        .modal-content .text-muted {
            color: rgba(255, 255, 255, 0.7) !important;
            font-size: 0.9rem;
        }

        .card-title {
            color: var(--text-light) !important;
            font-weight: 600;
            margin-top: 1rem;
            font-size: 1.25rem;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
            opacity: 1;
        }

        .form-control::-webkit-input-placeholder {
            color: rgba(255, 255, 255, 0.5);
            opacity: 1;
        }

        .form-control::-moz-placeholder {
            color: rgba(255, 255, 255, 0.5);
            opacity: 1;
        }

        .form-control:-ms-input-placeholder {
            color: rgba(255, 255, 255, 0.5);
            opacity: 1;
        }
    </style>
</head>
<body>
    <header class="main-header">
        <nav class="nav-container">
            <div class="logo">
                <?php if ($_SESSION['role'] === 'freelancer'): ?>
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
                    Logout
                </a>
            </div>
        </nav>
    </header>

    <main class="main-content">
        <div class="profile-container">
            <div class="profile-header">
                <h1>My Profile</h1>
                <p>Update your profile information to attract potential clients</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success" id="successAlert">
                    <?php echo $success_message; ?>
                    <button type="button" class="close-btn" onclick="closeAlert(this.parentElement)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="card bg-transparent border-0">
                        <div class="card-body text-center">
                            <div class="profile-picture-container">
                                <?php if ($profile['profile_picture']): ?>
                                    <img src="uploads/profile_pictures/<?php echo htmlspecialchars($profile['profile_picture']); ?>" 
                                         alt="Profile Picture" class="profile-picture">
                                <?php else: ?>
                                    <div class="profile-picture-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="profile-picture-actions">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#uploadPictureModal" title="Upload new picture">
                                        <i class="fas fa-camera"></i>
                                    </a>
                                    <?php if ($profile['profile_picture']): ?>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#removePictureModal" title="Remove picture">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($profile['full_name']): ?>
                                <h5 class="card-title mt-3"><?php echo htmlspecialchars($profile['full_name']); ?></h5>
                            <?php else: ?>
                                <h5 class="card-title mt-3"><?php echo htmlspecialchars($username); ?></h5>
                            <?php endif; ?>
                            <p class="card-text text-muted">Freelancer</p>
                            
                            <div class="social-links">
                                <?php if ($profile['portfolio_url']): ?>
                                    <a href="<?php echo htmlspecialchars($profile['portfolio_url']); ?>" target="_blank" class="social-link">
                                        <i class="fas fa-globe"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($profile['linkedin_url']): ?>
                                    <a href="<?php echo htmlspecialchars($profile['linkedin_url']); ?>" target="_blank" class="social-link">
                                        <i class="fab fa-linkedin"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($profile['github_url']): ?>
                                    <a href="<?php echo htmlspecialchars($profile['github_url']); ?>" target="_blank" class="social-link">
                                        <i class="fab fa-github"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <form class="profile-form" method="POST" action="" id="profileForm" novalidate>
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($profile['full_name']); ?>" required
                                   minlength="2" maxlength="255">
                            <div class="error-message">Please enter your full name (minimum 2 characters)</div>
                        </div>

                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio" class="form-control" required
                                    minlength="10"><?php echo htmlspecialchars($profile['bio']); ?></textarea>
                            <div class="error-message">Please enter your bio (minimum 10 characters)</div>
                        </div>

                        <div class="form-group">
                            <label for="skills">Skills</label>
                            <input type="text" id="skills" name="skills" class="form-control" 
                                   value="<?php echo htmlspecialchars($profile['skills']); ?>" 
                                   placeholder="Add skills separated by commas" required>
                            <div class="error-message">Please enter at least one skill</div>
                            <div class="skills-tags" id="skillsTags"></div>
                        </div>

                        <div class="form-group">
                            <label for="experience">Experience</label>
                            <textarea id="experience" name="experience" class="form-control" required
                                    minlength="20"><?php echo htmlspecialchars($profile['experience']); ?></textarea>
                            <div class="error-message">Please describe your experience (minimum 20 characters)</div>
                        </div>

                        <div class="form-group">
                            <label for="education">Education</label>
                            <textarea id="education" name="education" class="form-control" required
                                    minlength="10"><?php echo htmlspecialchars($profile['education']); ?></textarea>
                            <div class="error-message">Please enter your education details (minimum 10 characters)</div>
                        </div>

                        <div class="form-group">
                            <label for="portfolio_url">Portfolio URL</label>
                            <input type="url" id="portfolio_url" name="portfolio_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($profile['portfolio_url']); ?>"
                                   >
                        </div>

                        <div class="form-group">
                            <label for="linkedin_url">LinkedIn URL</label>
                            <input type="url" id="linkedin_url" name="linkedin_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($profile['linkedin_url']); ?>"
                                   >
                        </div>

                        <div class="form-group">
                            <label for="github_url">GitHub URL</label>
                            <input type="url" id="github_url" name="github_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($profile['github_url']); ?>"
                                   >
                        </div>

                        <button type="submit" name="update_profile" class="btn-save">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Upload Picture Modal -->
    <div class="modal fade" id="uploadPictureModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Select Image</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" 
                                   accept="image/*" required onchange="previewImage(this)">
                            <small class="text-muted">Max file size: 2MB. Supported formats: JPG, PNG, GIF</small>
                        </div>
                        <div class="preview-container" id="previewContainer">
                            <img id="imagePreview" class="image-preview" alt="Preview">
                        </div>
                        <div class="loading">
                            <div class="loading-spinner"></div>
                            <p>Uploading...</p>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-upload me-2"></i>Upload
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Remove Picture Modal -->
    <div class="modal fade" id="removePictureModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Remove Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to remove your profile picture?</p>
                    <form method="POST" action="">
                        <button type="submit" name="remove_picture" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Yes, Remove
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const previewContainer = document.getElementById('previewContainer');
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        }

        // Skills tags
        const skillsInput = document.getElementById('skills');
        const skillsTags = document.getElementById('skillsTags');

        function updateSkillsTags() {
            const skills = skillsInput.value.split(',').map(skill => skill.trim()).filter(skill => skill);
            skillsTags.innerHTML = skills.map(skill => `
                <div class="skill-tag">
                    ${skill}
                    <span class="remove-skill" onclick="removeSkill('${skill}')">&times;</span>
                </div>
            `).join('');
        }

        function removeSkill(skill) {
            const skills = skillsInput.value.split(',').map(s => s.trim());
            const index = skills.indexOf(skill);
            if (index > -1) {
                skills.splice(index, 1);
                skillsInput.value = skills.join(', ');
                updateSkillsTags();
            }
        }

        skillsInput.addEventListener('input', updateSkillsTags);
        updateSkillsTags();

        // Form submission loading state
        document.getElementById('uploadForm').addEventListener('submit', function() {
            this.querySelector('.loading').style.display = 'block';
            this.querySelector('button[type="submit"]').disabled = true;
        });

        // Alert auto-dismiss and close functionality
        function closeAlert(alert) {
            alert.classList.add('fade-out');
            setTimeout(() => {
                alert.remove();
            }, 500);
        }

        // Auto-dismiss success alert after 10 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.getElementById('successAlert');
            if (successAlert) {
                setTimeout(() => {
                    closeAlert(successAlert);
                }, 10000);
            }
        });

        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            let isValid = true;
            const formGroups = this.querySelectorAll('.form-group');
            
            // Remove all existing error states
            formGroups.forEach(group => {
                group.classList.remove('error');
            });

            // Validate each required field
            this.querySelectorAll('[required]').forEach(field => {
                const formGroup = field.closest('.form-group');
                let fieldIsValid = true;

                // Check if empty
                if (!field.value.trim()) {
                    fieldIsValid = false;
                }
                // Check minlength
                else if (field.hasAttribute('minlength') && 
                         field.value.trim().length < parseInt(field.getAttribute('minlength'))) {
                    fieldIsValid = false;
                }
                // Special validation for skills
                else if (field.id === 'skills') {
                    const skills = field.value.split(',').map(skill => skill.trim()).filter(skill => skill);
                    if (skills.length === 0) {
                        fieldIsValid = false;
                    }
                }

                if (!fieldIsValid) {
                    formGroup.classList.add('error');
                    isValid = false;
                }
            });

            // Optional URL validations
            ['portfolio_url', 'linkedin_url', 'github_url'].forEach(urlId => {
                const urlField = this.querySelector(`#${urlId}`);
                if (urlField.value.trim()) {
                    try {
                        new URL(urlField.value);
                    } catch (e) {
                        const formGroup = urlField.closest('.form-group');
                        formGroup.classList.add('error');
                        isValid = false;
                    }
                }
            });

            if (!isValid) {
                e.preventDefault();
                // Show error alert
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-error';
                errorAlert.id = 'validationAlert';
                errorAlert.innerHTML = `
                    Please fill in all required fields correctly
                    <button type="button" class="close-btn" onclick="closeAlert(this.parentElement)">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                
                // Remove existing alert if any
                const existingAlert = document.getElementById('validationAlert');
                if (existingAlert) {
                    existingAlert.remove();
                }
                
                // Insert alert at the top of the form
                this.insertBefore(errorAlert, this.firstChild);
                
                // Scroll to first error
                const firstError = this.querySelector('.form-group.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });

        // Real-time validation on input
        document.querySelectorAll('.form-control').forEach(field => {
            field.addEventListener('input', function() {
                const formGroup = this.closest('.form-group');
                if (this.hasAttribute('required') || this.value.trim()) {
                    let isValid = true;
                    
                    if (this.hasAttribute('required') && !this.value.trim()) {
                        isValid = false;
                    }
                    else if (this.hasAttribute('minlength') && 
                             this.value.trim().length < parseInt(this.getAttribute('minlength'))) {
                        isValid = false;
                    }
                    else if (this.id === 'skills') {
                        const skills = this.value.split(',').map(skill => skill.trim()).filter(skill => skill);
                        if (skills.length === 0) {
                            isValid = false;
                        }
                    }
                    
                    formGroup.classList.toggle('error', !isValid);
                }
            });
        });
    </script>
</body>
</html> 