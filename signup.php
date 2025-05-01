<?php
include("db_config.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = $_POST["username"];
  $email = $_POST["email"];
  $password = $_POST["password"];
  $role = $_POST["role"]; // Get the role from the form

  // Check if email already exists
  $check_sql = "SELECT id FROM users WHERE email = ?";
  $check_stmt = $conn->prepare($check_sql);
  $check_stmt->bind_param("s", $email);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();
  
  if ($check_result->num_rows > 0) {
    header("Location: signup.html?error=email_exists");
    exit();
  }

  // Hash the password
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);

  // Insert into DB
  $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

  if ($stmt->execute()) {
    $_SESSION["user_id"] = $stmt->insert_id;
    $_SESSION["username"] = $username;
    $_SESSION["email"] = $email;
    $_SESSION["role"] = $role;
    
    // Redirect based on role
    switch($role) {
      case 'developer':
        header("Location: developer-dashboard.php");
        exit();
      case 'freelancer':
        header("Location: freelancer-dashboard.php");
        exit();
      default:
        // If role is not recognized, redirect to login with error
        header("Location: login.html?error=invalid_role");
        exit();
    }
  } else {
    header("Location: signup.html?error=registration_failed");
    exit();
  }

  $stmt->close();
  $conn->close();
} else {
  echo "Invalid request!";
}
?>
