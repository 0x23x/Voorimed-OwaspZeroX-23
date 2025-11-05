<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

$is_logged_in = false;
$current_user = null;
$tweets = [];
$message = '';
$message_type = '';

// === POST TWEET LOGIC ===
if ($is_logged_in === false && isset($_SESSION['login']) && $_SESSION['login'] === true && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT id, username, name, profile_picture, bio FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $is_logged_in = true;
        $current_user = $result->fetch_assoc();
    }
}

// Handle tweet submission
if ($is_logged_in && isset($_POST['tweet_content'])) {
    $content = trim($_POST['tweet_content']);

    if (empty($content)) {
        $message = "Tweet cannot be empty.";
        $message_type = 'error';
    } elseif (strlen($content) > 280) {
        $message = "Tweet must be 280 characters or less.";
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("INSERT INTO tweets (user_id, content) VALUES (?, ?)");
        $stmt->bind_param("is", $current_user['id'], $content);
        if ($stmt->execute()) {
            $message = "Tweet posted!";
            $message_type = 'success';
            // Refresh tweets after post
            header("Location: index.php");
            exit();
        } else {
            $message = "Failed to post tweet.";
            $message_type = 'error';
        }
    }
}

// Fetch all tweets
if ($is_logged_in) {
    $tweet_sql = "
        SELECT t.id, t.content, t.created_at, u.id AS user_id, u.username, u.name, u.profile_picture
        FROM tweets t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
        LIMIT 50
    ";
    $tweets_result = $conn->query($tweet_sql);
    while ($row = $tweets_result->fetch_assoc()) {
        $tweets[] = $row;
    }
}

// Profile picture fallback
function getProfilePic($pic) {
    $path = "statics/user_profiles/" . htmlspecialchars($pic);
    if ($pic && file_exists(__DIR__ . '/statics/user_profiles/' . $pic)) {
        return $path;
    }
    return "statics/images/default-avatar.png";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOORIMED - Home</title>
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
            color: var(--dark);
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 1.8rem;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 8px 16px;
            border-radius: 12px;
            transition: var(--transition);
        }

        .nav-links a:hover {
            background: #f0f4ff;
            color: var(--primary);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background: #5a4fcf;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 92, 231, 0.3);
        }

        .welcome-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .welcome-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .welcome-text {
            color: var(--gray);
            font-size: 1rem;
        }

        .auth-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        /* === TWEET COMPOSER === */
        .tweet-composer {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .composer-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1rem;
        }

        .composer-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--light);
        }

        .composer-textarea {
            width: 100%;
            min-height: 100px;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            transition: var(--transition);
            background: #f8f9ff;
        }

        .composer-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.15);
            background: white;
        }

        .composer-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .char-count {
            font-size: 0.85rem;
            color: var(--gray);
        }

        .char-count.warning { color: #f39c12; }
        .char-count.danger { color: var(--danger); }

        .btn-tweet {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-tweet:hover:not(:disabled) {
            background: #5a4fcf;
            transform: translateY(-2px);
        }

        .btn-tweet:disabled {
            background: #b2bec3;
            cursor: not-allowed;
            transform: none;
        }

        .tweet-feed {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .tweet-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            transition: var(--transition);
        }

        .tweet-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }

        .tweet-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1rem;
        }

        .tweet-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--light);
        }

        .tweet-user-info h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .tweet-user-info p {
            font-size: 0.85rem;
            color: var(--gray);
            margin: 0;
        }

        .tweet-user-info a {
            color: var(--primary);
            text-decoration: none;
        }

        .tweet-user-info a:hover {
            text-decoration: underline;
        }

        .tweet-content {
            font-size: 1.05rem;
            line-height: 1.6;
            color: var(--dark);
            margin-bottom: 1rem;
            word-break: break-word;
        }

        .tweet-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: var(--gray);
        }

        .no-tweets {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            color: var(--gray);
            font-size: 1.1rem;
        }

        .flash-message {
            padding: 12px 16px;
            margin-bottom: 1rem;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
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

        @media (max-width: 768px) {
            .container { padding: 15px; }
            .header { flex-direction: column; text-align: center; }
            .nav-links { justify-content: center; }
            .composer-footer { flex-direction: column; gap: 0.75rem; }
            .btn-tweet { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Header -->
    <div class="header">
        <a href="index.php" class="logo">
            VOORIMED
        </a>
        <div class="nav-links">
            <?php if ($is_logged_in): ?>
                <a href="profile.php?id=<?= $current_user['id']; ?>">My Profile</a>
                <a href="panel.php">Dashboard</a>
                <a href="logout.php" class="btn-primary">
                    Logout
                </a>
            <?php else: ?>
                <a href="register.php">Register</a>
                <a href="login.php">Login</a>
                <a href="all_users.php">All Users</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <?php if ($is_logged_in): ?>
        
        <!-- TWEET COMPOSER -->
        <div class="tweet-composer">
            <?php if ($message): ?>
                <div class="flash-message flash-<?= $message_type; ?>">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="tweetForm">
                <div class="composer-header">
                    <img src="<?= getProfilePic($current_user['profile_picture']); ?>" 
                         alt="<?= htmlspecialchars($current_user['name']); ?>" 
                         class="composer-avatar">
                    <div>
                        <strong><?= htmlspecialchars($current_user['name']); ?></strong>
                        <small style="color:var(--gray);">@<?= htmlspecialchars($current_user['username']); ?></small>
                    </div>
                </div>

                <textarea 
                    name="tweet_content" 
                    class="composer-textarea" 
                    placeholder="What's happening?" 
                    maxlength="280"
                    required
                ></textarea>

                <div class="composer-footer">
                    <span class="char-count" id="charCount">280</span>
                    <button type="submit" class="btn-tweet">
                        Tweet
                    </button>
                </div>
            </form>
        </div>

        <!-- TWEET FEED -->
        <div class="tweet-feed">
            <?php if (empty($tweets)): ?>
                <div class="no-tweets">
                    No tweets yet. Be the first to post!
                </div>
            <?php else: ?>
                <?php foreach ($tweets as $tweet): ?>
                    <div class="tweet-card">
                        <div class="tweet-header">
                            <img src="<?= getProfilePic($tweet['profile_picture']); ?>" 
                                 alt="<?= htmlspecialchars($tweet['name']); ?>" 
                                 class="tweet-avatar">
                            <div class="tweet-user-info">
                                <h4>
                                    <a href="profile.php?id=<?= $tweet['user_id']; ?>">
                                        <?= htmlspecialchars($tweet['name']); ?>
                                    </a>
                                </h4>
                                <p>@<?= htmlspecialchars($tweet['username']); ?></p>
                            </div>
                        </div>
                        <div class="tweet-content">
                            <?= nl2br(htmlspecialchars($tweet['content'])); ?>
                        </div>
                        <div class="tweet-meta">
                            <span>
                                <?= date('M j, Y \a\t g:i A', strtotime($tweet['created_at'])); ?>
                            </span>
                            <?php if ($tweet['user_id'] == $current_user['id']): ?>
                                <small style="color:var(--primary); font-weight:600;">Your tweet</small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- Not Logged In -->
        <div class="welcome-card">
            <h1 class="welcome-title">Welcome to VOORIMED</h1>
            <p class="welcome-text">Join our community. Share ideas. Connect with people.</p>
            <div class="auth-buttons">
                <a href="register.php" class="btn-primary">Create Account</a>
                <a href="login.php" class="btn-primary" style="background:#5a4fcf;">Login</a>
            </div>
            <p style="margin-top:1.5rem; color:var(--gray);">
                Or <a href="all_users.php" style="color:var(--primary); font-weight:600;">browse all users</a>
            </p>
        </div>
    <?php endif; ?>

</div>

<script>
    const textarea = document.querySelector('.composer-textarea');
    const charCount = document.getElementById('charCount');
    const maxLength = 280;

    textarea?.addEventListener('input', () => {
        const remaining = maxLength - textarea.value.length;
        charCount.textContent = remaining;
        charCount.className = 'char-count';
        if (remaining < 50) charCount.classList.add('warning');
        if (remaining < 20) charCount.classList.add('danger');
        if (remaining < 0) charCount.textContent = '0';
    });
</script>

</body>
</html>