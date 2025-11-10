<?php
session_start();
require_once "db.php";

// Check if ID is provided
if (!isset($_GET['id'])) {
    die("Profile ID is required");
}
$id = $_GET['id'];

// Validate ID is numeric
if (!is_numeric($id)) {
    die("Invalid profile ID");
}

// Get user data from database
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("User not found.");
}
$user = $result->fetch_assoc();

// Profile picture logic
$profile_file = $user['profile_picture'] ?? '';
$profiles_dir = __DIR__ . '/statics/user_profiles/';
$server_path = $profiles_dir . $profile_file;
if (!empty($profile_file) && file_exists($server_path)) {
    $display_path = 'statics/user_profiles/' . $profile_file;
} else {
    $default_path = __DIR__ . '/statics/images/default-avatar.png';
    $display_path = file_exists($default_path) ? 'statics/images/default-avatar.png' : '';
    if (!$display_path) {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">
                  <rect fill="#e0e0e0" width="120" height="120"/>
                  <circle cx="60" cy="44" r="28" fill="#999"/>
                  <path d="M15 100c10-20 40-20 45-20s35 0 45 20" fill="#bbb"/>
                </svg>';
        $display_path = 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

// Try to fetch additional info from API (optional)
$api_data = null;
$api_url = "http://localhost:5000/api/users/" . urlencode($id);
$api_response = @file_get_contents($api_url);
if ($api_response !== false) {
    $api_data = json_decode($api_response, true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($user['name']); ?> (@<?= htmlspecialchars($user['username']); ?>)</title>
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
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .profile-header {
            background: var(--primary);
            color: white;
            padding: 3rem 2rem 2rem;
            text-align: center;
            position: relative;
        }
        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 6px solid white;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            margin-bottom: 1rem;
            transition: var(--transition);
        }
        .profile-pic:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
        }
        .profile-name {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
        }
        .profile-username {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 500;
        }
        .profile-body {
            padding: 2rem;
        }
        .profile-bio {
            background: #f8f9ff;
            padding: 1.5rem;
            border-radius: 14px;
            font-size: 1rem;
            line-height: 1.6;
            color: var(--gray);
            text-align: center;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }
        .no-bio {
            color: #94a3b8;
            font-style: italic;
        }
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark);
            margin: 1.5rem 0 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i {
            color: var(--primary);
        }
        .tweets-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .tweet-card {
            background: #f8f9ff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.25rem;
            transition: var(--transition);
            position: relative;
        }
        .tweet-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
            border-color: var(--primary-light);
        }
        .tweet-content {
            color: var(--dark);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 0.75rem;
            word-break: break-word;
        }
        .tweet-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: var(--gray);
        }
        .tweet-date {
            font-weight: 500;
        }
        .no-tweets {
            text-align: center;
            padding: 2.5rem;
            color: var(--gray);
            font-size: 1rem;
            background: #f8f9ff;
            border-radius: 14px;
            border: 1px dashed var(--border);
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--light);
            color: var(--primary);
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            margin: 1.5rem 2rem;
            border: 1px solid var(--border);
        }
        .back-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 92, 231, 0.3);
        }

        /* === DELETE BUTTON === */
        .delete-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--danger);
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            opacity: 0.8;
        }
        .delete-btn:hover {
            background: #e74c3c;
            opacity: 1;
            transform: scale(1.1);
        }

        @media (max-width: 640px) {
            .container {
                margin: 20px auto;
            }
            .profile-header {
                padding: 2rem 1.5rem 1.5rem;
            }
            .profile-pic {
                width: 100px;
                height: 100px;
            }
            .profile-name {
                font-size: 1.5rem;
            }
            .profile-body {
                padding: 1.5rem;
            }
            .delete-btn {
                width: 28px;
                height: 28px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="profile-header">
        <img src="<?= htmlspecialchars($display_path); ?>" alt="Profile Picture" class="profile-pic">
        <h1 class="profile-name"><?= htmlspecialchars($user['name']); ?></h1>
        <div class="profile-username">@<?= htmlspecialchars($user['username']); ?></div>
    </div>
    <div class="profile-body">
        <?php if (!empty($user['bio'])): ?>
            <div class="profile-bio">
                <?= nl2br(htmlspecialchars($user['bio'])); ?>
            </div>
        <?php else: ?>
            <div class="profile-bio no-bio">
                This user hasn't written a bio yet.
            </div>
        <?php endif; ?>

        <!-- Tweets Section -->
        <div class="section-title">
            Tweets
        </div>
        <?php
        $tweet_stmt = $conn->prepare("SELECT * FROM tweets WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
        $tweet_stmt->bind_param("i", $id);
        $tweet_stmt->execute();
        $tweets = $tweet_stmt->get_result();
        ?>
        <?php if ($tweets->num_rows > 0): ?>
            <div class="tweets-container">
                <?php while ($tweet = $tweets->fetch_assoc()): ?>
                    <div class="tweet-card">
                        <!-- DELETE BUTTON (only for own tweets) -->
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id): ?>
                            <a href="delete.php?id=<?= $tweet['id']; ?>" 
                               class="delete-btn" 
                               onclick="return confirm('Are you sure you want to delete this tweet?');">
                            </a>
                        <?php endif; ?>

                        <div class="tweet-content">
                            <?= nl2br(htmlspecialchars($tweet['content'])); ?>
                        </div>
                        <div class="tweet-meta">
                            <span class="tweet-date">
                                <?= date('M j, Y \a\t g:i A', strtotime($tweet['created_at'])); ?>
                            </span>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id): ?>
                                <small style="color:var(--primary);">Your tweet</small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-tweets">
                No tweets yet
            </div>
        <?php endif; ?>

        <a href="javascript:history.back()" class="back-btn">
            Go Back
        </a>
    </div>
</div>
</body>
</html>