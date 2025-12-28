<?php
session_start();
require_once __DIR__ . '/../../app/Controllers/QuestionController.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Доступ запрещён');
}

$controller = new QuestionController();
$questions = $controller->getActiveQuestions();

if (!$questions) {
    $questions = [];
}

// Handle delete request
if (isset($_GET['delete'])) {
    $delete_id = Security::getSafeInt($_GET['delete']);
    if ($delete_id !== null) {
        $controller->deleteQuestion($delete_id);
        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Администратор — ChoicePoint</title>
    <link rel="stylesheet" href="../../public/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../../app/Views/admin_nav.php'; ?>

    <div class="container">
        <div style="margin-bottom: 40px;">
            <h2 style="margin-bottom: 10px; font-size: 2rem;">📊 Все дилеммы</h2>
            <p style="color: var(--text-muted);">Управление всеми дилеммами в системе</p>
        </div>

        <?php if (empty($questions)): ?>
            <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; border: 2px dashed var(--border);">
                <h3 style="color: var(--text-muted); margin-bottom: 10px; font-size: 1.5rem;">🤔 Дилемм не найдено</h3>
                <p style="color: var(--text-muted);">На платформе пока нет дилемм</p>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 20px;">
                <?php foreach ($questions as $q): ?>
                    <div class="question-card">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
                            <div style="flex: 1;">
                                <h3 style="margin-bottom: 8px;"><?= htmlspecialchars($q['title']) ?></h3>
                                <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">
                                    👤 ID: <?= (int)$q['user_id'] ?> | 📅 <?= date('d.m.Y H:i', strtotime($q['created_at'])) ?>
                                </p>
                            </div>
                            <span style="background: <?= $q['is_approved'] ? 'var(--success)' : 'var(--warning)' ?>; color: white; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; white-space: nowrap; margin-left: 16px;">
                                <?= $q['is_approved'] ? '✓ Активна' : '⏳ Ожидание' ?>
                            </span>
                        </div>

                        <?php if (!empty($q['description'])): ?>
                            <p class="description"><?= nl2br(htmlspecialchars($q['description'])) ?></p>
                        <?php endif; ?>

                        <div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                            <p style="font-size: 0.9rem; font-weight: 600; color: var(--dark); margin-bottom: 10px;">Варианты ответа:</p>
                            <ul style="margin: 0; padding-left: 20px; list-style: disc;">
                                <?php 
                                $options = $controller->getOptions($q['id']);
                                foreach ($options as $opt): 
                                ?>
                                    <li style="padding: 6px 0; color: var(--text);">
                                        <?= htmlspecialchars($opt['text']) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <a href="/public/admin/edit_question.php?id=<?= (int)$q['id'] ?>" class="btn btn-primary" style="text-align: center;">
                                ✏️ Редактировать
                            </a>
                            <a href="/public/admin/delete_question.php?id=<?= (int)$q['id'] ?>" class="btn btn-danger" style="text-align: center;">
                                🗑 Удалить
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p style="margin: 0;">&copy; 2025 <strong>ChoicePoint</strong> — Платформа для сложных выборов</p>
        <p style="margin: 8px 0 0 0; color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">Помогайте друг другу принимать решения</p>
    </footer>

    <script src="../../public/script.js"></script>
</body>
</html>

