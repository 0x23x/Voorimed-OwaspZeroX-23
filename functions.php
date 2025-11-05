<?php




function random_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

?>