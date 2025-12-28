<?php
require_once __DIR__ . '/../../config/database.php';

class AuthController {
    public function register($email, $password) {
        global $pdo;
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        return $stmt->execute([$email, $hash]);
    }

    public function login($email, $password) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
        return false;
    }

    public function logout() {
        session_start();
        session_destroy();
    }
}
