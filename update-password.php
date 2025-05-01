<?php
session_start();
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = $_POST['otp'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify OTP and check expiry
    if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['otp_expiry']) || 
        !isset($_SESSION['reset_email'])) {
        showMessage("Invalid request. Please try the password reset process again.", "error");
        exit();
    }
    
    if (time() > $_SESSION['otp_expiry']) {
        showMessage("OTP has expired. Please request a new one.", "error");
        exit();
    }
    
    if ($otp != $_SESSION['reset_otp']) {
        showMessage("Invalid OTP. Please try again.", "error");
        exit();
    }
    
    if ($new_password !== $confirm_password) {
        showMessage("Passwords do not match.", "error");
        exit();
    }
    
    // Update password in database
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $email = $_SESSION['reset_email'];
    
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);
    
    if ($stmt->execute()) {
        // Clear session variables
        unset($_SESSION['reset_otp']);
        unset($_SESSION['otp_expiry']);
        unset($_SESSION['reset_email']);
        
        showMessage("Password has been reset successfully. <a href='login.html'>Login</a> with your new password.", "success");
    } else {
        showMessage("Failed to reset password. Please try again.", "error");
    }
    
    $stmt->close();
    $conn->close();
}

function showMessage($message, $type) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Password Reset</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            .message-container {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: #f5f5f5;
            }
            .message-box {
                background: white;
                padding: 2rem;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 400px;
                width: 90%;
            }
            .message-box.success {
                border-left: 4px solid #4CAF50;
            }
            .message-box.error {
                border-left: 4px solid #f44336;
            }
            .message-box h2 {
                margin-bottom: 1rem;
                color: #333;
            }
            .message-box p {
                color: #666;
                margin-bottom: 1.5rem;
            }
            .btn-submit {
                background-color: #4CAF50;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
            }
            .btn-submit:hover {
                background-color: #45a049;
            }
        </style>
    </head>
    <body>
        <div class="message-container">
            <div class="message-box ' . $type . '">
                <h2>' . ($type === 'success' ? 'Success!' : 'Error') . '</h2>
                <p>' . $message . '</p>
                <a href="login.html" class="btn-submit">Back to Login</a>
            </div>
        </div>
    </body>
    </html>';
}
?> 