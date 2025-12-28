<?php
session_start();
require_once __DIR__ . '/../../app/Controllers/QuestionController.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Доступ запрещён');
}

$qc = new QuestionController();
$question_id = Security::getSafeInt($_GET['id'] ?? null);

if ($question_id === null || $question_id <= 0) {
    die('Вопрос не найден');
}

// Get question
$question = null;
foreach ($qc->getActiveQuestions() as $q) {
    if ((int)$q['id'] === $question_id) {
        $question = $q;
        break;
    }
}

if (!$question) {
    die('Дилемма не найдена');
}

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
        die('Ошибка безопасности');
    }
    
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        if ($qc->deleteQuestion($question_id)) {
            header('Location: index.php');
            exit;
        } else {
            die('Ошибка при удалении дилеммы');
        }
    } else {
        header('Location: index.php');
        exit;
    }
}

$options = $qc->getOptions($question_id);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Удаление дилеммы — ChoicePoint</title>
    <link rel="stylesheet" href="../../public/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../../app/Views/admin_nav.php'; ?>

    <div class="container">
        <div style="max-width: 600px; margin: 0 auto;">
            <div class="question-card" style="border-left: 4px solid var(--error);">
                <h2 style="color: var(--error); margin-bottom: 10px; font-size: 2rem;">⚠️ Подтверждение удаления</h2>
                <p style="color: var(--text-muted); margin-bottom: 24px;">Это действие невозможно отменить</p>

                <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--warning);">
                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">
                        <?= htmlspecialchars($question['title']) ?>
                    </h3>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                        ID: <?= (int)$question_id ?>
                    </p>
                    
                    <?php if (!empty($question['description'])): ?>
                        <p style="margin: 12px 0 0 0; color: var(--text); font-size: 0.95rem;">
                            <?= htmlspecialchars(mb_substr($question['description'], 0, 150)) ?><?= mb_strlen($question['description']) > 150 ? '...' : '' ?>
                        </p>
                    <?php endif; ?>

                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border);">
                        <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Варианты:</p>
                        <ul style="margin: 8px 0 0 0; padding-left: 20px; list-style: disc;">
                            <?php foreach ($options as $opt): ?>
                                <li style="font-size: 0.9rem; color: var(--text);"><?= htmlspecialchars($opt['text']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div style="background: #fef2f2; border: 1px solid #fee2e2; border-radius: 8px; padding: 14px 16px; margin-bottom: 24px;">
                    <p style="margin: 0; color: #7f1d1d; font-size: 0.9rem; font-weight: 500;">
                        <strong>⚠️ Внимание:</strong> Будут удалены все голосы и реакции пользователей
                    </p>
                </div>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= Security::getCSRFToken() ?>">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <button type="submit" name="confirm" value="yes" class="btn btn-danger">
                            🗑️ Да, удалить
                        </button>
                        <a href="index.php" class="btn btn-outline" style="text-align: center; padding: 12px 24px;">
                            ← Отмена
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer>
        <p style="margin: 0;">&copy; 2025 <strong>ChoicePoint</strong> — Платформа для сложных выборов</p>
        <p style="margin: 8px 0 0 0; color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">Помогайте друг другу принимать решения</p>
    </footer>

    <script src="../../public/script.js"></script>
</body>
</html>
