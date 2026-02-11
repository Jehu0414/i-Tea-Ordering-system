<?php
// includes/auth.php
require_once __DIR__ . '/config.php';

function is_logged_in() {
    return isset($_SESSION['user']);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /public/login.php');
        exit;
    }
}

function require_role($role) {
    $user = current_user();
    if (!$user || $user['role'] !== $role) {
        http_response_code(403);
        echo "Access denied.";
        exit;
    }
}

function refresh_user_session($pdo) {
    if(isset($_SESSION['user']['id'])) {
        $stmt = $pdo->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $u = $stmt->fetch();
        if ($u) $_SESSION['user'] = $u;
    }
}
