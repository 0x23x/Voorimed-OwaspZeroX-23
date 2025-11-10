<?php
session_start();
require_once "db.php";
require_once "functions.php"; // ← NOW INCLUDES getProfilePic()

// === GET: Return tweets with FULL image path ===
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tweets = [];
    $sql = "SELECT
                t.id, t.content, t.created_at,
                u.id AS user_id, u.username, u.name, u.profile_picture
            FROM tweets t
            JOIN users u ON t.user_id = u.id
            ORDER BY t.created_at DESC
            LIMIT 50";

    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Use getProfilePic() to get full correct path
            $row['profile_picture'] = getProfilePic($row['profile_picture']);

            $row['created_human'] = !empty($row['created_at'])
                ? (new DateTime($row['created_at']))->format('M j, Y \a\t H:i')
                : '';

            $tweets[] = $row;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($tweets);
    exit;
}

// === POST: Save new tweet ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $content = trim($input['tweet_content'] ?? '');

    if (empty($content)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tweet content is required']);
        exit;
    }

    if (strlen($content) > 280) {
        http_response_code(400);
        echo json_encode(['error' => 'Tweet too long']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO tweets (user_id, content) VALUES (?, ?)");
    $stmt->bind_param("is", $_SESSION['user_id'], $content);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Tweet posted']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    $stmt->close();
    exit;
}
?>