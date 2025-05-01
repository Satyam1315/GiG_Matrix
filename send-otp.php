<?php
session_start();
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Check if email exists in database
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        // Generate 6-digit OTP
        $otp = rand(100000, 999999);

        // Store OTP in session
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['otp_expiry'] = time() + (5 * 60); // OTP valid for 5 minutes

        // Send email with OTP
        $to = $email;
        $subject = "Password Reset Request - OTP Verification";

        $message = "Dear User,\n\n";
        $message .= "We have received a request to reset your password. Please use the One-Time Password (OTP) provided below to proceed:\n\n";
        $message .= "OTP: " . $otp . "\n\n";
        $message .= "Note: This OTP is valid for only 5 minutes.\n\n";
        $message .= "If you did not request this password reset, please ignore this email or contact our support team.\n\n";
        $message .= "Regards,\n";
        $message .= "Support Team\n";
        $message .= "GiG Matrix";

        $headers = "From: GiG Matrix <satyamkumarsingh1315@gmail.com>\r\n";
        $headers .= "Reply-To: satyamkumarsingh1315@gmail.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";


        if (mail($to, $subject, $message, $headers)) {
            header("Location: reset-password.html");
            exit();
        } else {
            echo "Failed to send OTP. Please try again.";
        }
    } else {
        echo "Email not found in our records.";
    }

    $stmt->close();
    $conn->close();
}
