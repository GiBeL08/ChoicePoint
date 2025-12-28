<?php
// Проверка что пользователь - админ
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>
<header style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; padding: 20px 0; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); margin-bottom: 40px;">
    <div class="container">
        <h1 style="font-size: 2.5rem; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 8px;">🎯 ChoicePoint</h1>
        <p style="color: rgba(255, 255, 255, 0.9); margin: 0 0 15px 0; font-size: 0.9rem;">Панель администратора</p>
        <nav style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
            <a href="/index.php" class="btn btn-outline">На главную</a>
            <a href="/public/admin/index.php" class="btn btn-outline">📊 Все дилеммы</a>
            <a href="/public/admin/moderation.php" class="btn btn-outline">📋 Модерация</a>
            <a href="/logout.php" class="btn btn-outline">Выход</a>
        </nav>
    </div>
</header>
