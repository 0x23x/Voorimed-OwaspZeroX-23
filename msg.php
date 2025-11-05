<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

// Get URL parameters
$msg = $_GET['msg'] ?? 'No message provided.';
$type = $_GET['type'] ?? 'info';
$goto = $_GET['goto'] ?? null;

// Sanitize
$msg = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
$type = strtolower($type);

// Set icon & color
if ($type === 'success') {
    $icon = 'check-circle';
    $color_class = 'success';
} elseif ($type === 'error') {
    $icon = 'exclamation-triangle';
    $color_class = 'error';
} else {
    $icon = 'info-circle';
    $color_class = 'info';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #6C5CE7;
            --primary-light: #A29BFE;
            --danger: #FF7676;
            --success: #00B894;
            --info: #74C0FC;
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
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin: 20px auto;
            text-align: center;
        }

        .msg-header {
            padding: 2.5rem 2rem 1.5rem;
        }

        .msg-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            display: block;
        }

        .msg-success .msg-icon { color: var(--success); }
        .msg-error .msg-icon   { color: var(--danger); }
        .msg-info .msg-icon    { color: var(--info); }

        .msg-title {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .msg-body {
            padding: 0 2rem 2rem;
        }

        .msg-text {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .countdown {
            font-size: 0.95rem;
            color: var(--gray);
            font-weight: 500;
        }

        .countdown strong {
            color: var(--primary);
        }

        .msg-footer {
            padding: 1.5rem 2rem;
            background: #f8f9ff;
            border-top: 1px solid var(--border);
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(108, 92, 231, 0.3);
        }

        .btn-home:hover {
            background: #5a4fcf;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(108, 92, 231, 0.4);
        }

        @media (max-width: 480px) {
            .msg-header {
                padding: 2rem 1.5rem 1rem;
            }
            .msg-body {
                padding: 0 1.5rem 1.5rem;
            }
            .msg-icon {
                font-size: 3rem;
            }
            .msg-title {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>

<div class="container msg-<?= $color_class; ?>">
    <div class="msg-header">
        <i class="fas fa-<?= $icon; ?> msg-icon"></i>
        <div class="msg-title">
            <?= $type === 'success' ? 'Success!' : ($type === 'error' ? 'Oops!' : 'Notice') ?>
        </div>
    </div>

    <div class="msg-body">
        <p class="msg-text"><?= $msg; ?></p>
        
        <?php if ($goto): ?>
            <p class="countdown">
                Redirecting in <strong id="countdown">3</strong> seconds...
            </p>
        <?php endif; ?>
    </div>

    <div class="msg-footer">
        <a href="<?= htmlspecialchars($goto ?? 'panel.php'); ?>" class="btn-home">
            Continue
        </a>
    </div>
</div>

<?php if ($goto): ?>
<script>
    let seconds = 3;
    const countdownEl = document.getElementById('countdown');
    const targetUrl = <?= json_encode($goto); ?>;

    const timer = setInterval(() => {
        seconds--;
        if (countdownEl) {
            countdownEl.textContent = seconds;
        }
        if (seconds <= 0) {
            clearInterval(timer);
            window.location.href = targetUrl;
        }
    }, 1000);

    // Allow instant redirect on click
    document.querySelector('.btn-home').addEventListener('click', (e) => {
        e.preventDefault();
        clearInterval(timer);
        window.location.href = targetUrl;
    });
</script>
<?php endif; ?>

</body>
</html>