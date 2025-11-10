<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['login'])) {
    header("Location: msg.php?msg=You Are Not Logged In&type=error&goto=login.php");
    exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid tweet ID");
}

$tweet_id = $_GET['id'];

// Check if user owns the tweet
$stmt = $conn->prepare("SELECT user_id FROM tweets WHERE id = ?");
$stmt->bind_param("i", $tweet_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Tweet not found");
}

$tweet = $result->fetch_assoc();

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $tweet['user_id']) {
    die("You can only delete your own tweets");
}

// Delete the tweet
$delete_stmt = $conn->prepare("DELETE FROM tweets WHERE id = ?");
$delete_stmt->bind_param("i", $tweet_id);
$delete_stmt->execute();

header("Location: profile.php?id=" . $_SESSION['user_id']);
exit();
?>