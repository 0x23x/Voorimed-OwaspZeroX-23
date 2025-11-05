<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: msg.php?msg=You Are Not Logged In&type=error&goto=login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Profile picture logic
$profile_file = $user['profile_picture'] ?? '';
$profiles_dir = __DIR__ . '/statics/user_profiles/';
$server_path = $profiles_dir . $profile_file;

if (!empty($profile_file) && file_exists($server_path)) {
    $display_path = 'statics/user_profiles/' . $profile_file;
} else {
    $default_path = __DIR__ . '/statics/images/default.png';
    $display_path = file_exists($default_path) ? 'statics/images/default.png' : '';
    if (!$display_path) {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">
                  <rect fill="#e0e0e0" width="120" height="120"/>
                  <circle cx="60" cy="44" r="28" fill="#999"/>
                  <path d="M15 100c10-20 40-20 45-20s35 0 45 20" fill="#bbb"/>
                </svg>';
        $display_path = 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
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

        .dashboard-header {
            background: var(--primary);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }

        .dashboard-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .dashboard-header h1 i {
            font-size: 1.8rem;
        }

        .flash-message {
            padding: 12px 20px;
            margin: 20px;
            border-radius: 12px;
            font-weight: 500;
            text-align: center;
            font-size: 0.95rem;
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

        .profile-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            gap: 1.5rem;
            background: #f9f9ff;
            border-bottom: 1px solid var(--border);
        }

        @media (min-width: 640px) {
            .profile-card {
                flex-direction: row;
                align-items: flex-start;
            }
        }

        .profile-pic {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 6px 20px rgba(108, 92, 231, 0.3);
            transition: var(--transition);
        }

        .profile-pic:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 30px rgba(108, 92, 231, 0.4);
        }

        .profile-info {
            flex: 1;
            min-width: 0;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 8px;
        }

        .profile-username {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary);
        }

        .btn-secondary {
            background: var(--light);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--primary-light);
            color: white;
            transform: translateY(-2px);
        }

        .profile-bio {
            color: var(--gray);
            line-height: 1.6;
            margin-top: 8px;
            font-size: 0.95rem;
        }

        .update-form {
            padding: 2rem;
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

        .field input,
        .field textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
            background: white;
        }

        .field input:focus,
        .field textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.15);
        }

        .field input[readonly] {
            background: #f8f9fa;
            color: var(--gray);
            cursor: not-allowed;
        }

        .field textarea {
            resize: vertical;
            min-height: 100px;
        }

        .note {
            display: block;
            margin-top: 6px;
            font-size: 0.85rem;
            color: var(--gray);
        }

        .file-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 16px;
            background: white;
            border: 2px dashed var(--border);
            border-radius: 14px;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        .file-label:hover {
            border-color: var(--primary);
            background: #f8f8ff;
        }

        .file-label-text {
            font-weight: 600;
            color: var(--primary);
            font-size: 1rem;
        }

        .file-label input {
            display: none;
        }

        .file-name {
            font-size: 0.9rem;
            color: var(--gray);
            font-style: italic;
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

        .logout-btn {
            display: block;
            text-align: center;
            margin: 2rem auto 1rem;
            color: var(--danger);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .logout-btn:hover {
            color: #e74c3c;
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            .dashboard-header h1 {
                font-size: 1.6rem;
            }
            .profile-card {
                padding: 1.5rem;
            }
            .profile-pic {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>

<div class="container dashboard">
    <div class="dashboard-header">
        <h1><i class="fas fa-rainbow"></i> Welcome, <?= htmlspecialchars($user['name']); ?>!</h1>
    </div>

    <!-- Flash Message -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="flash-message flash-<?= $_SESSION['flash_type'] ?? 'success'; ?>">
            <?= $_SESSION['flash_message']; ?>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <form method="post" action="update_user.php" class="update-form" enctype="multipart/form-data">
        <div class="profile-card">
            <img src="<?= htmlspecialchars($display_path); ?>" alt="Profile Picture" class="profile-pic" id="profilePreview">
            <div class="profile-info">
                <div class="profile-header">
                    <div class="profile-username">@<?= htmlspecialchars($user['username']); ?></div>
                    <a href="profile.php?id=<?= urlencode($user['id']); ?>" target="_blank" rel="noopener" class="btn-secondary">
                        View Profile
                    </a>
                </div>
                <p class="profile-bio"><?= nl2br(htmlspecialchars($user['bio'] ?? 'no more fake friends;)dd')); ?></p>
            </div>
        </div>

        <label class="file-label">
            <span class="file-label-text">Change Profile Picture</span>
            <input type="file" name="profile_picture" accept="image/*" id="profileInput">
            <span class="file-name">No file selected</span>
        </label>

        <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']); ?>">

        <div class="field">
            <label>Full Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
        </div>

        <div class="field">
            <label>Email</label>
            <input type="email" value="<?= htmlspecialchars($user['email']); ?>" readonly>
        </div>

        <div class="field">
            <label>Bio</label>
            <textarea name="bio" rows="3" placeholder="Tell others about yourself (optional)"><?= htmlspecialchars($user['bio'] ?? ''); ?></textarea>
        </div>

        <div class="field">
            <label>New Password</label>
            <input type="password" name="password" placeholder="Leave blank to keep current password">
            <small class="note">Leave blank to keep your current password</small>
        </div>

        <button type="submit" name="update">
            Save Changes
        </button>
    </form>

    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const fileInput = document.getElementById("profileInput");
        const preview = document.getElementById("profilePreview");
        const fileName = document.querySelector(".file-name");

        fileInput.addEventListener("change", (e) => {
            const file = e.target.files[0];
            if (!file) {
                fileName.textContent = "No file selected";
                return;
            }

            fileName.textContent = file.name;

            if (file.type.startsWith("image/")) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    preview.src = ev.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    });
</script>

</body>
</html>