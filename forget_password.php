<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

$message = '';
$message_type = 'error';

if (isset($_POST['submit'])) {
    $username = $_POST['username'] ?? '';

    if (empty($username)) {
        $message = 'Please enter your username.';
    } else {
        // Check if user exists
        $check_sql = "SELECT email FROM users WHERE username = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $message = 'Username does not exist.';
        } else {
            $user = $result->fetch_assoc(); // Define $user here
            $token = random_token();

            // Update token
            $update_sql = "UPDATE users SET reset_token = ? WHERE username = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ss", $token, $username);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $reset_url = "https://scpzzz.ir/voorimed/reset_password.php?token=" . $token;
                $to = $user['email'];
                $subject = "Password Reset Request";
                $email_body = "Click the link below to reset your password:\n\n$reset_url";
                $headers = "From: no-reply@VOORIMED";

                // MAIL FUNCTION EXACTLY AS YOU WROTE IT — NO CHANGE
                mail($to, $subject, $email_body, $headers);

                $message = 'A password reset link has been sent to your registered email address.';
                $message_type = 'success';
                echo "<script>setTimeout(() => { window.location.href = 'login.php'; }, 5000);</script>";
            } else {
                $message = 'Failed to generate reset token. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #6C5CE7;
            --primary-light: #A29BFE;
            --danger: #FF7676;
            --success: #00B894;
            --gray: #636E72;
            --light: #F8F9FA;
            --dark: #2D3436;
            --border: #DFE6E9;
            --radius: 16px;
            --shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 460px;
            width: 100%;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin: 20px auto;
        }

        .auth-header {
            background: var(--primary);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .auth-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .auth-header h1 i {
            font-size: 1.6rem;
        }

        .auth-body {
            padding: 2rem;
        }

        .flash-message {
            padding: 14px 18px;
            margin-bottom: 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            text-align: center;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .flash-error {
            background: rgba(255, 118, 118, 0.15);
            color: var(--danger);
            border: 1px solid rgba(255, 118, 118, 0.3);
        }

        .flash-success {
            background: rgba(0, 184, 148, 0.15);
            color: var(--success);
            border: 1px solid rgba(0, 184, 148, 0.3);
        }

        .field {
            margin-bottom: 1.5rem;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .field input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
            background: white;
        }

        .field input::placeholder {
            color: #b2bec3;
        }

        .field input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.15);
        }

        button[type="submit"] {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px 28px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            width: 100%;
            margin-top: 1rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        button[type="submit"]:hover {
            background: #5a4fcf;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(108, 92, 231, 0.3);
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.95rem;
        }

        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .back-link a:hover {
            color: #5a4fcf;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .auth-header h1 {
                font-size: 1.5rem;
            }
            .auth-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="auth-header">
        <h1>Forgot Password</h1>
    </div>

    <div class="auth-body">
        <?php if ($message): ?>
            <div class="flash-message flash-<?= $message_type; ?>">
                <?= htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <div class="field">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    name="username" 
                    id="username" 
                    placeholder="Enter your username" 
                    value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" 
                    required 
                />
            </div>

            <button type="submit" name="submit">
                Send Reset Link
            </button>
        </form>

        <div class="back-link">
            <p>Remembered your password? <a href="login.php">Back to Login</a></p>
        </div>
    </div>
</div>

</body>
</html>