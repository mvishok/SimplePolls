<?php
function safevar($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function username($data) {
    $pattern = '/^[a-zA-Z0-9_]+$/';
    if (preg_match($pattern, $data)) {
        return true;
    } else {
        return false;
    }
}
?>