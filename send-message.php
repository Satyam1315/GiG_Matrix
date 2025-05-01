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

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
$current_role = $_SESSION['role'];

// Check if appropriate ID is provided based on role
if ($current_role === 'developer' && !isset($_GET['freelancer_id'])) {
    header("Location: messages.php");
    exit();
} elseif ($current_role === 'freelancer' && !isset($_GET['developer_id'])) {
    header("Location: messages.php");
    exit();
}

// Set the other user's ID based on role
$other_user_id = $current_role === 'developer' ? $_GET['freelancer_id'] : $_GET['developer_id'];

// Fetch other user's details with role check
$query = "SELECT username, email, role FROM users WHERE id = ? AND role = ?";
$stmt = $conn->prepare($query);
$expected_role = $current_role === 'developer' ? 'freelancer' : 'developer';
$stmt->bind_param("is", $other_user_id, $expected_role);
$stmt->execute();
$other_user = $stmt->get_result()->fetch_assoc();

if (!$other_user) {
    header("Location: messages.php");
    exit();
}

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $query = "INSERT INTO messages (sender_id, receiver_id, message, created_at) 
                 VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iis", $current_user_id, $other_user_id, $message);
        $stmt->execute();
    }
    // Redirect to prevent form resubmission
    $param_name = $current_role === 'developer' ? 'freelancer_id' : 'developer_id';
    header("Location: send-message.php?{$param_name}=" . $other_user_id);
    exit();
}

// Fetch message history
$query = "SELECT m.*, u.username as sender_name 
          FROM messages m 
          JOIN users u ON m.sender_id = u.id 
          WHERE (sender_id = ? AND receiver_id = ?) 
             OR (sender_id = ? AND receiver_id = ?) 
          ORDER BY created_at ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $current_user_id, $other_user_id, $other_user_id, $current_user_id);
$stmt->execute();
$messages = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Message <?php echo htmlspecialchars($other_user['username']); ?> - GiG Matrix</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            margin-top: 60px;
        }

        .chat-container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(26, 26, 26, 0.95);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            height: calc(100vh - 200px);
        }

        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-title {
            color: #fff;
            font-size: 1.5rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-role {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            max-width: 70%;
            padding: 1rem;
            border-radius: 12px;
            position: relative;
        }

        .message-sent {
            background: #6c5ce7;
            color: #fff;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }

        .message-received {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }

        .message-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }

        .message-sender {
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
        }

        .message-time {
            color: rgba(255, 255, 255, 0.6);
        }

        .message-content {
            line-height: 1.5;
            word-break: break-word;
        }

        .chat-input {
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .message-form {
            display: flex;
            gap: 1rem;
        }

        .message-textarea {
            flex: 1;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
            color: #fff;
            font-size: 1rem;
            resize: none;
            min-height: 60px;
            max-height: 150px;
        }

        .message-textarea:focus {
            outline: none;
            border-color: #6c5ce7;
        }

        .btn-primary {
            padding: 0 1.5rem;
            border: none;
            background: #6c5ce7;
            color: #fff;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: #5a4bd1;
            transform: translateY(-2px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            padding: 0 1.5rem;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .chat-container {
                height: calc(100vh - 160px);
            }

            .message {
                max-width: 85%;
            }

            .chat-header {
                padding: 1rem;
            }

            .chat-messages {
                padding: 1rem;
            }

            .chat-input {
                padding: 1rem;
            }
        }

        /* Custom Scrollbar */
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: rgba(108, 92, 231, 0.5);
            border-radius: 4px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: rgba(108, 92, 231, 0.7);
        }
    </style>
</head>

<body>
    <header class="main-header">
        <nav class="nav-container">
            <div class="logo">
                <?php if ($current_role === 'freelancer'): ?>
                    <a href="freelancer-dashboard.php">GiG Matrix</a>
                <?php else: ?>
                    <a href="developer-dashboard.php">GiG Matrix</a>
                <?php endif; ?>
            </div>
            <div class="auth-buttons">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($current_username); ?></span>
                </div>
                <a href="logout.php" class="btn btn-logout">Logout</a>
            </div>
        </nav>
    </header>

    <main class="main-content">
        <div class="chat-container">
            <div class="chat-header">
                <h2 class="chat-title">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($other_user['username']); ?>
                    <span class="user-role">(<?php echo htmlspecialchars($other_user['role']); ?>)</span>
                </h2>
                <a href="messages.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Messages
                </a>
            </div>

            <div class="chat-messages">
                <?php while ($msg = $messages->fetch_assoc()): ?>
                    <div class="message <?php echo $msg['sender_id'] == $current_user_id ? 'message-sent' : 'message-received'; ?>">
                        <div class="message-meta">
                            <span class="message-sender"><?php echo htmlspecialchars($msg['sender_name']); ?></span>
                            <span class="message-time"><?php echo date('M j, Y g:i A', strtotime($msg['created_at'])); ?></span>
                        </div>
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="chat-input">
                <form method="POST" class="message-form" autocomplete="off">
                    <textarea name="message" class="message-textarea" placeholder="Type your message..." required></textarea>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Send
                    </button>
                </form>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> GiG Matrix. All rights reserved.</p>
    </footer>

    <script>
        // Clear form data when page loads (including when using back button)
        window.addEventListener('pageshow', function(event) {
            // Clear the form if the page is loaded from cache (back button)
            if (event.persisted) {
                document.querySelector('.message-form').reset();
            }
        });

        // Clear textarea on page load
        window.addEventListener('load', function() {
            document.querySelector('.message-textarea').value = '';
        });

        // Clear form before unload
        window.addEventListener('unload', function() {
            document.querySelector('.message-form').reset();
        });
    </script>
</body>

</html>