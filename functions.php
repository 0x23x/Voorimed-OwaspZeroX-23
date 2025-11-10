<?php
function random_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

if (!function_exists('getProfilePic')) {
    function getProfilePic($pic) {
        $pic = trim($pic ?? '');

        // If already full path (e.g., from default), return it
        if (strpos($pic, 'statics/') === 0) {
            return $pic;
        }

        // Empty → default
        if (empty($pic)) {
            return 'statics/images/default-avatar.png';
        }

        // Build path from filename
        $filename = basename($pic);
        $path = "statics/user_profiles/" . $filename;

        if (file_exists(__DIR__ . '/' . $path)) {
            return $path;
        }

        return 'statics/images/default-avatar.png';
    }
}
?>