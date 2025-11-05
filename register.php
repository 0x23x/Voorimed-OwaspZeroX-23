<?php
require_once "db.php";
$message = "";

if (isset($_POST['submit'])) {
    if (
        empty($_POST['fullname']) ||
        empty($_POST['username']) ||
        empty($_POST['email']) ||
        empty($_POST['password']) ||
        empty($_POST['invitation_code'])
    ) {
        $message = "<div class='flash-message flash-error'>All fields are required!</div>";
    } else {
        $invitation_code = $_POST['invitation_code'];
        $stmt = $conn->prepare("SELECT * FROM invitation_codes WHERE invitation_code = ? AND used = 0");
        $stmt->bind_param("s", $invitation_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $message = "<div class='flash-message flash-error'>Invalid or already used invitation code.</div>";
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $message = "<div class='flash-message flash-error'>Invalid email format.</div>";
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $_POST['email']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $message = "<div class='flash-message flash-error'>Email already registered!</div>";
            } else {
                $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->bind_param("s", $_POST['username']);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $message = "<div class='flash-message flash-error'>Username already in use!</div>";
                } else {
                    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $insert = $conn->prepare("INSERT INTO users (name, username, email, password, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                    $insert->bind_param("ssss", $_POST['fullname'], $_POST['username'], $_POST['email'], $hashed_password);
                    $insert->execute();

                    if ($insert->affected_rows > 0) {
                        $stmt = $conn->prepare("UPDATE invitation_codes SET used = 1 WHERE invitation_code = ?");
                        $stmt->bind_param("s", $invitation_code);
                        $stmt->execute();

                        $message = "<div class='flash-message flash-success'>Registration successful! Redirecting...</div>
                                    <script>setTimeout(() => window.location.href='login.php', 2500);</script>";
                    } else {
                        $message = "<div class='flash-message flash-error'>Registration failed! Try again.</div>";
                    }
                }
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
    <title>Create Account</title>
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
            max-width: 480px;
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

        .flash-success {
            background: rgba(0, 184, 148, 0.15);
            color: var(--success);
            border: 1px solid rgba(0, 184, 148, 0.3);
        }

        .flash-error {
            background: rgba(255, 118, 118, 0.15);
            color: var(--danger);
            border: 1px solid rgba(255, 118, 118, 0.3);
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

        .links {
            text-align: center;
            margin-top: 1.5rem;
        }

        .links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .links a:hover {
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
        <h1>Create Account</h1>
    </div>

    <div class="auth-body">
        <?= $message; ?>

        <form method="POST" action="" autocomplete="on" novalidate>
            <div class="field">
                <label for="fullname">Full Name</label>
                <input id="fullname" type="text" name="fullname" placeholder="Full Name" required>
            </div>

            <div class="field">
                <label for="username">Username</label>
                <input id="username" type="text" name="username" placeholder="Username" required>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" placeholder="Email" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" placeholder="Password" required>
            </div>

            <div class="field">
                <label for="invitation_code">Invitation Code</label>
                <input id="invitation_code" type="text" name="invitation_code" placeholder="Invitation Code" required>
            </div>

            <button type="submit" name="submit">
                Register
            </button>
        </form>

        <div class="links">
            <a href="login.php">Already have an account? Log in</a>
        </div>
    </div>
</div>

</body>
</html>