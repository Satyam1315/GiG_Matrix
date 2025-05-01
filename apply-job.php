<?php
session_start();
include 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to apply for jobs']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$jobId = isset($data['job_id']) ? intval($data['job_id']) : 0;

if ($jobId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid job ID']);
    exit();
}

// Check if user has already applied for this job
$checkQuery = "SELECT id FROM job_applications WHERE job_id = ? AND freelancer_id = ?";
$checkStmt = mysqli_prepare($conn, $checkQuery);
mysqli_stmt_bind_param($checkStmt, "ii", $jobId, $_SESSION['user_id']);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);

if (mysqli_num_rows($checkResult) > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already applied for this job']);
    exit();
}

// Get job details to check if it's still open and get user details
$jobQuery = "SELECT j.status, j.budget, u.email, u.username 
             FROM jobs j 
             JOIN users u ON u.id = ? 
             WHERE j.id = ?";
$jobStmt = mysqli_prepare($conn, $jobQuery);
mysqli_stmt_bind_param($jobStmt, "ii", $_SESSION['user_id'], $jobId);
mysqli_stmt_execute($jobStmt);
$jobResult = mysqli_stmt_get_result($jobStmt);
$jobData = mysqli_fetch_assoc($jobResult);

if (!$jobData || $jobData['status'] !== 'open') {
    echo json_encode(['success' => false, 'message' => 'This job is no longer available']);
    exit();
}

// Insert application into database
$insertQuery = "INSERT INTO job_applications (job_id, freelancer_id, full_name, email, phone, resume_path, cover_letter, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
$insertStmt = mysqli_prepare($conn, $insertQuery);

// Default values for the application
$fullName = $jobData['username'];
$email = $jobData['email'];
$phone = "Not provided"; // Default phone number
$resumePath = ""; // Empty resume path for now
$coverLetter = "I am interested in this position and believe my skills match the requirements.";

mysqli_stmt_bind_param($insertStmt, "iisssss", 
    $jobId, 
    $_SESSION['user_id'], 
    $fullName,
    $email,
    $phone,
    $resumePath,
    $coverLetter
);

if (mysqli_stmt_execute($insertStmt)) {
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit application. Please try again.']);
}

mysqli_stmt_close($insertStmt);
mysqli_close($conn);
?>