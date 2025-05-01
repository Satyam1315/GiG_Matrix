<?php
session_start();
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            switch($user['role']) {
                case 'developer':
                    header("Location: developer-dashboard.php");
                    exit();
                case 'freelancer':
                    header("Location: freelancer-dashboard.php");
                    exit();
                default:
                    header("Location: login.html?error=invalid_role");
                    exit();
            }
        } else {
            header("Location: login.html?error=invalid_password");
            exit();
        }
    } else {
        header("Location: login.html?error=email_not_found");
        exit();
    }
}
?>
