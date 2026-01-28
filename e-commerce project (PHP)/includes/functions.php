<?php
function init_session() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

function is_logged_in() {
    init_session();
    return isset($_SESSION['user_id']);
}

function is_admin() {
    init_session();
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        header("Location: ../index.php");
        exit();
    }
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function format_price($price) {
    return '$' . number_format($price, 2);
}

function redirect($url) {
    header("Location: $url");
    exit();
}
?>