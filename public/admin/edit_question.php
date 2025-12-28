<?php
session_start();
require_once __DIR__ . '/../../app/Controllers/QuestionController.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$controller = new QuestionController();
$question_id = Security::getSafeInt($_GET['id'] ?? null);

if ($question_id === null || $question_id <= 0) {
    die('Вопрос не найден');
}

// Get question from either pending or active
$question = null;
foreach ($controller->getPendingQuestions() as $q) {
    if ((int)$q['id'] === $question_id) {
        $question = $q;
        break;
    }
}

if (!$question) {
    foreach ($controller->getActiveQuestions() as $q) {
        if ((int)$q['id'] === $question_id) {
            $question = $q;
            break;
        }
    }
}

if (!$question) {
    die('Дилемма не найдена');
}

$options = $controller->getOptions($question_id);
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
        $message = 'Ошибка безопасности';
        $messageType = 'error';
    } else {
        $title = Security::sanitizeString($_POST['title'] ?? '');
        $description = Security::sanitizeString($_POST['description'] ?? '');

        if (empty($title)) {
            $message = 'Название не может быть пустым';
            $messageType = 'error';
        } else {
            $opt_texts = [];
            foreach ($_POST['options'] ?? [] as $optId => $text) {
                $id = Security::getSafeInt($optId);
                if ($id === null) {
                    $message = 'Ошибка при обработке вариантов';
                    $messageType = 'error';
                    break;
                }
                $opt_texts[$id] = Security::sanitizeString($text);
            }

            if ($messageType === 'success') {
                if ($controller->updateQuestion($question_id, $title, $description, $opt_texts)) {
                    $message = 'Дилемма успешно обновлена';
                    $messageType = 'success';
                    $options = $controller->getOptions($question_id);
                    $question['title'] = $title;
                    $question['description'] = $description;
                } else {
                    $message = 'Ошибка при обновлении дилеммы';
                    $messageType = 'error';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование дилеммы — ChoicePoint</title>
    <link rel="stylesheet" href="../../public/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../../app/Views/admin_nav.php'; ?>

    <div class="container">
        <div style="max-width: 700px; margin: 0 auto;">
            <div style="background: white; padding: 32px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="margin-bottom: 10px; font-size: 2rem;">✏️ Редактировать дилемму</h2>
                <p style="color: var(--text-muted); margin-bottom: 30px;">Измените название, описание или варианты ответа</p>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'error' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= Security::getCSRFToken() ?>">

                    <div class="form-group">
                        <label for="title">Название дилеммы *</label>
                        <input 
                            type="text" 
                            id="title"
                            name="title" 
                            value="<?= htmlspecialchars($question['title']) ?>" 
                            required 
                            minlength="3" 
                            maxlength="120"
                        >
                    </div>

                    <div class="form-group">
                        <label for="description">Описание</label>
                        <textarea 
                            id="description"
                            name="description" 
                            rows="5"
                        ><?= htmlspecialchars($question['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <h3 style="font-size: 1.1rem; margin-bottom: 16px;">Варианты ответа</h3>
                        <div style="display: grid; gap: 16px;">
                            <?php foreach ($options as $idx => $opt): ?>
                                <div style="padding: 16px; background: #f9fafb; border-radius: 8px; border: 1px solid var(--border);">
                                    <label for="option_<?= (int)$opt['id'] ?>" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark);">
                                        Вариант <?= $idx + 1 ?>
                                    </label>
                                    <input 
                                        type="text"
                                        id="option_<?= (int)$opt['id'] ?>"
                                        name="options[<?= (int)$opt['id'] ?>]"
                                        value="<?= htmlspecialchars($opt['text']) ?>"
                                        required 
                                        minlength="3" 
                                        maxlength="60"
                                    >
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; text-align: center; margin-top: 20px;">
                        💾 Сохранить изменения
                    </button>
                </form>

                <a href="index.php" style="display: inline-block; margin-top: 20px; color: var(--primary); font-weight: 500;">← Вернуться</a>
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
