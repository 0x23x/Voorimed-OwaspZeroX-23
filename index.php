<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

$is_logged_in = false;
$current_user = null;

if (isset($_SESSION['login']) && $_SESSION['login'] === true && isset($_SESSION['user_id'])) {
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
        }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }

        /* === HEADER (BOTH STATES) === */
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
        .logo i { font-size: 1.8rem; }
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

        /* === WELCOME CARD (NOT LOGGED IN) === */
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
            margin-bottom: 1.5rem;
        }
        .auth-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        /* === TWEET COMPOSER (LOGGED IN) === */
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
        .btn-tweet:hover:not(:disabled) { background: #5a4fcf; transform: translateY(-2px); }
        .btn-tweet:disabled { background: #b2bec3; cursor: not-allowed; transform: none; }

        /* === TWEET FEED === */
        .tweet-feed { display: flex; flex-direction: column; gap: 1.5rem; }
        .tweet-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            transition: var(--transition);
        }
        .tweet-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12); }
        .tweet-header { display: flex; align-items: center; gap: 12px; margin-bottom: 1rem; }
        .tweet-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--light);
        }
        .tweet-user-info h4 { font-size: 1rem; font-weight: 600; color: var(--dark); margin: 0; }
        .tweet-user-info p { font-size: 0.85rem; color: var(--gray); margin: 0; }
        .tweet-user-info a { color: var(--primary); text-decoration: none; }
        .tweet-user-info a:hover { text-decoration: underline; }
        .tweet-content { font-size: 1.05rem; line-height: 1.6; color: var(--dark); margin-bottom: 1rem; word-break: break-word; }
        .tweet-meta { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: var(--gray); }
        .no-tweets {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            color: var(--gray);
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .container { padding: 15px; }
            .header { flex-direction: column; text-align: center; }
            .nav-links { justify-content: center; }
            .composer-footer { flex-direction: column; gap: 0.75rem; }
            .btn-tweet { width: 100%; justify-content: center; }
            .auth-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <a href="index.php" class="logo">VOORIMED</a>
        <div class="nav-links">
            <?php if ($is_logged_in): ?>
                <a href="profile.php?id=<?= $current_user['id']; ?>">My Profile</a>
                <a href="panel.php">Dashboard</a>
                <a href="logout.php" class="btn-primary">Logout</a>
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
            <form id="tweetForm">
                <div class="composer-header">
                    <img src="<?= getProfilePic($current_user['profile_picture']); ?>"
                         alt="<?= htmlspecialchars($current_user['name']); ?>"
                         class="composer-avatar">
                    <div>
                        <strong><?= htmlspecialchars($current_user['name']); ?></strong>
                        <small style="color:var(--gray);">@<?= htmlspecialchars($current_user['username']); ?></small>
                    </div>
                </div>
                <textarea name="tweet_content" class="composer-textarea"
                          placeholder="What's happening?" maxlength="280" required></textarea>
                <div class="composer-footer">
                    <span class="char-count" id="charCount">280</span>
                    <button type="submit" class="btn-tweet">Tweet</button>
                </div>
            </form>
        </div>

        <!-- TWEET FEED -->
        <div class="tweet-feed" id="tweetFeed">
            <div class="no-tweets">Loading tweets...</div>
        </div>

    <?php else: ?>
        <!-- WELCOME CARD (NOT LOGGED IN) -->
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

document.addEventListener("DOMContentLoaded", () => {
    window.addEventListener("message", (event) => {
        if (event.data.success) {
            alert(event.data.success);
        } else {
            alert(event.data.error);
        }
    });
});
// === 1. CHARACTER COUNTER ===
const textarea = document.querySelector('.composer-textarea');
const charCount = document.getElementById('charCount');
const maxLength = 280;

textarea?.addEventListener('input', () => {
    const left = maxLength - textarea.value.length;
    charCount.textContent = left > 0 ? left : '0';
    charCount.className = 'char-count';
    if (left < 50) charCount.classList.add('warning');
    if (left < 20) charCount.classList.add('danger');
});

// === 2. SUBMIT TWEET (ONLY ALERT) ===
document.getElementById('tweetForm')?.addEventListener('submit', function (e) {
    e.preventDefault();

    const btn = this.querySelector('.btn-tweet');
    btn.disabled = true;
    btn.innerHTML = 'Submitting...';

    const content = textarea.value.trim();
    if (!content) {
        alert('Tweet cannot be empty!');
        btn.disabled = false;
        btn.innerHTML = 'Tweet';
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'tweets.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;

        let res = {};
        try { res = JSON.parse(xhr.responseText); } catch (e) {}

        if (xhr.status === 200 && res.success) {
            textarea.value = '';
            charCount.textContent = '280';
            charCount.className = 'char-count';
            loadTweets();

            // SUCCESS ALERT
            window.postMessage({ success: "success", message: 'post successfully' }, '*');

            // Reset button
            btn.remove();
            const footer = document.querySelector('.composer-footer');
            const newBtn = document.createElement('button');
            newBtn.type = 'submit';
            newBtn.className = 'btn-tweet';
            newBtn.innerHTML = 'Tweet';
            footer.appendChild(newBtn);
        } else {
            // ERROR ALERT
            window.postMessage({ error: "failed", message: 'Failed to post tweet' }, '*');
            btn.disabled = false;
            btn.innerHTML = 'Tweet';
        }
    };

    xhr.send(JSON.stringify({ tweet_content: content }));
});

// === 3. LOAD TWEETS ===
function loadTweets() {
    const feed = document.getElementById('tweetFeed');
    if (!feed) return;
    feed.innerHTML = '<div class="no-tweets">Loading tweets...</div>';

    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'tweets.php', true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                const tweets = JSON.parse(xhr.responseText);
                renderTweets(tweets);
            } catch (e) {
                feed.innerHTML = '<div class="no-tweets">Error loading tweets.</div>';
            }
        }
    };
    xhr.send();
}

// === 4. RENDER TWEETS ===
function renderTweets(tweets) {
    const feed = document.getElementById('tweetFeed');
    if (!tweets || tweets.length === 0) {
        feed.innerHTML = '<div class="no-tweets">No tweets yet. Be the first!</div>';
        return;
    }

    const currentUserId = <?= $current_user['id'] ?? 'null' ?>;

    let html = '';
    tweets.forEach(t => {
        const isOwn = t.user_id == currentUserId;
        html += `
            <div class="tweet-card">
                <div class="tweet-header">
                    <img src="${t.profile_picture}" alt="${t.name}" class="tweet-avatar"
                         onerror="this.src='statics/images/default-avatar.png'">
                    <div class="tweet-user-info">
                        <h4><a href="profile.php?id=${t.user_id}">${t.name}</a></h4>
                        <p>@${t.username}</p>
                    </div>
                </div>
                <div class="tweet-content">${t.content.replace(/\n/g, '<br>')}</div>
                <div class="tweet-meta">
                    <span>${t.created_human}</span>
                    ${isOwn ? '<small style="color:var(--primary);font-weight:600;">Your tweet</small>' : ''}
                </div>
            </div>`;
    });
    feed.innerHTML = html;
}

// === 5. AUTO-REFRESH ===
<?php if ($is_logged_in): ?>
loadTweets();
setInterval(loadTweets, 100000000000000000);
<?php endif; ?>
</script>
</body>
</html> 