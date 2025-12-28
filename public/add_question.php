<?php
session_start();
require_once __DIR__ . '/../app/Controllers/QuestionController.php';
require_once __DIR__ . '/../app/helpers/Security.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$qc = new QuestionController();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
        $message = 'Ошибка безопасности. Попробуйте еще раз.';
        $messageType = 'error';
    } else {
        $title = Security::sanitizeString($_POST['title'] ?? '');
        $description = Security::sanitizeString($_POST['description'] ?? '');
        $category = Security::sanitizeString($_POST['category'] ?? '');

        // Collect and sanitize options
        $options = array_filter(
            array_map('Security::sanitizeString', $_POST['options'] ?? []),
            fn($o) => !empty($o)
        );

        // Additional validation
        if (empty($title)) {
            $message = 'Название не может быть пустым';
            $messageType = 'error';
        } elseif (empty($options) || count($options) < 2) {
            $message = 'Минимум 2 варианта ответа';
            $messageType = 'error';
        } else {
            $result = $qc->addQuestion(
                $_SESSION['user_id'],
                $title,
                $options,
                $description,
                $category
            );

            $messages = [
                'success' => 'Дилемма отправлена на модерацию',
                'bad_title' => 'Название от 3 до 120 символов',
                'few_options' => 'Минимум 2 варианта',
                'bad_option' => 'Варианты от 3 до 60 символов',
                'similar_options' => 'Варианты слишком похожи'
            ];

            $message = $messages[$result] ?? 'Неизвестная ошибка';
            $messageType = ($result === 'success') ? 'success' : 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать новую дилемму</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .options-list {
            display: grid;
            gap: 12px;
            margin-bottom: 20px;
        }

        .option-input-group {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .option-input-group input {
            flex: 1;
            margin-bottom: 0;
        }

        .btn-remove {
            padding: 10px 14px;
            margin-top: 0;
            background: var(--error);
            color: white;
        }

        .btn-add {
            background: var(--success);
            color: white;
            align-self: flex-start;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
        }

        form > button[type="submit"] {
            margin-top: 20px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary);
        }
    </style>

    <script>
        const maxOptions = 5;
        const minOptions = 2;

        function addOption() {
            const container = document.getElementById('options');
            const count = container.children.length;

            if (count >= maxOptions) {
                alert('Максимум 5 вариантов');
                return;
            }

            const div = document.createElement('div');
            div.className = 'option-input-group';
            div.innerHTML = `
                <input type="text" name="options[]" required placeholder="Вариант ${count + 1}" minlength="3" maxlength="60">
                <button type="button" class="btn btn-remove" onclick="removeOption(this)">❌ Удалить</button>
            `;

            container.appendChild(div);
        }

        function removeOption(btn) {
            const container = document.getElementById('options');
            if (container.children.length <= minOptions) {
                alert('Минимум 2 варианта');
                return;
            }
            btn.parentElement.remove();
        }
    </script>
</head>
<body>
    <header>
        <div class="container">
            <h1>🎯 ChoicePoint</h1>
            <nav style="margin-top: 15px;">
                <a href="/index.php" class="btn btn-outline">← На главную</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="card" style="max-width: 700px; margin: 0 auto;">
            <h2 style="margin-bottom: 10px;">✨ Создайте новую дилемму</h2>
            <p style="color: var(--text-muted); margin-bottom: 30px;">Поделитесь сложным выбором и узнайте, что выбрали бы другие люди</p>

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
                        required 
                        minlength="3" 
                        maxlength="120"
                        placeholder="Например: Ноутбук или велосипед?"
                    >
                    <small style="color: var(--text-muted);">От 3 до 120 символов</small>
                </div>

                <div class="form-group">
                    <label for="description">Описание (опционально)</label>
                    <textarea 
                        id="description"
                        name="description" 
                        placeholder="Добавьте контекст, чтобы помочь людям сделать выбор..."
                    ></textarea>
                    <small style="color: var(--text-muted);">Помогает людям лучше понять суть вопроса</small>
                </div>

                <div class="form-group">
                    <label for="category">Категория (опционально)</label>
                    <input 
                        type="text" 
                        id="category"
                        name="category"
                        placeholder="Например: Образование, Путешествия, Покупки"
                    >
                </div>

                <div class="form-group">
                    <h3 style="font-size: 1.1rem; margin-bottom: 16px;">Варианты ответа *</h3>
                    <p style="color: var(--text-muted); margin-bottom: 16px; font-size: 0.9rem;">Минимум 2, максимум 5 вариантов (от 3 до 60 символов каждый)</p>

                    <div class="options-list" id="options">
                        <div class="option-input-group">
                            <input 
                                type="text" 
                                name="options[]" 
                                required 
                                minlength="3" 
                                maxlength="60"
                                placeholder="Вариант 1"
                            >
                            <button type="button" class="btn btn-remove" onclick="removeOption(this)">❌ Удалить</button>
                        </div>

                        <div class="option-input-group">
                            <input 
                                type="text" 
                                name="options[]" 
                                required 
                                minlength="3" 
                                maxlength="60"
                                placeholder="Вариант 2"
                            >
                            <button type="button" class="btn btn-remove" onclick="removeOption(this)">❌ Удалить</button>
                        </div>
                    </div>

                    <button type="button" class="btn btn-add" onclick="addOption()">➕ Добавить вариант</button>
                </div>

                <button type="submit" class="btn btn-primary">Опубликовать дилемму</button>
            </form>
        </div>
    </div>

    <footer>
        <p style="margin: 0;">&copy; 2025 <strong>ChoicePoint</strong></p>
    </footer>
</body>
</html>
