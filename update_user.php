<?php
session_start();
require_once "db.php";

// Ensure user is logged in
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: msg.php?msg=You Are Not Logged In&type=error&goto=login.php");
    exit();
}

// Only accept POST from the form
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['update'])) {
    header('Location: panel.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch current user to know the current profile_picture and name
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$errors = [];
$messages = [];

$name = trim($_POST['name'] ?? '');
$password = $_POST['password'] ?? '';
$bio = trim($_POST['bio'] ?? '');

// Handle file upload
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['profile_picture'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error.';
    } else {
        if ($file['size'] > 2 * 1024 * 1024) { // 2 MB
            $errors[] = 'Image is too large (max 2MB).';
        }

        $imgInfo = @getimagesize($file['tmp_name']);
        if ($imgInfo === false) {
            $errors[] = 'Uploaded file is not a valid image.';
        } else {
            $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif'];
            if (!isset($allowed[$imgInfo[2]])) {
                $errors[] = 'Only JPG, PNG and GIF are allowed.';
            } else {
                $ext = $allowed[$imgInfo[2]];
                $targetDir = __DIR__ . '/statics/user_profiles/';
                if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

                $newName = 'user_' . $user_id . '_' . time() . '.' . $ext;
                $targetPath = $targetDir . $newName;

                if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $errors[] = 'Failed to save uploaded file.';
                } else {
                    // remove old custom image (keep default.png)
                    if (!empty($user['profile_picture']) && $user['profile_picture'] !== 'default.png') {
                        $old = $targetDir . $user['profile_picture'];
                        if (file_exists($old)) @unlink($old);
                    }

                    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                    $stmt->bind_param('si', $newName, $user_id);
                    $stmt->execute();
                    $stmt->close();

                    $messages[] = 'Profile picture updated.';
                }
            }
        }
    }
}

// Update name if changed
if ($name !== '' && $name !== ($user['name'] ?? '')) {
    $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
    $stmt->bind_param('si', $name, $user_id);
    $stmt->execute();
    $stmt->close();
    $messages[] = 'Name updated.';
}

// Update bio if changed
if ($bio !== ($user['bio'] ?? '')) {
    $stmt = $conn->prepare("UPDATE users SET bio = ? WHERE id = ?");
    $stmt->bind_param('si', $bio, $user_id);
    $stmt->execute();
    $stmt->close();
    $messages[] = 'Bio updated JIGAR.';
}

// Update password if provided
if (!empty($password)) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param('si', $hash, $user_id);
    $stmt->execute();
    $stmt->close();
    $messages[] = 'Password updated.';
}

// Build flash message
if (!empty($errors)) {
    $_SESSION['flash_message'] = '<div class="error">' . implode('<br>', $errors) . '</div>';
} else {
    if (empty($messages)) {
        $_SESSION['flash_message'] = '<div class="success">No changes made.</div>';
    } else {
        $_SESSION['flash_message'] = '<div class="success">' . implode('<br>', $messages) . '</div>';
    }
}

header('Location: panel.php');
exit();

?>