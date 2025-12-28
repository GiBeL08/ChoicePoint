<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/Security.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Ошибка безопасности. Попробуйте еще раз.';
    } else {
        $email = Security::sanitizeString($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validate inputs
        if (empty($email) || !Security::validateEmail($email)) {
            $error = 'Некорректный email';
        } elseif (empty($password) || !Security::validatePassword($password)) {
            $error = 'Пароль должен содержать от 6 до 255 символов';
        } else {
            try {
                // Check if user exists
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $check->execute([$email]);

                if ($check->rowCount() > 0) {
                    $error = 'Пользователь с таким email уже существует';
                } else {
                    // Create user
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare(
                        "INSERT INTO users (email, password, role, created_at) VALUES (?, ?, 'user', NOW())"
                    );
                    
                    if ($stmt->execute([$email, $hash])) {
                        // Auto-login after registration
                        $_SESSION['user_id'] = $pdo->lastInsertId();
                        $_SESSION['role'] = 'user';

                        header('Location: ../index.php');
                        exit;
                    } else {
                        $error = 'Ошибка при создании аккаунта. Попробуйте позже.';
                    }
                }
            } catch (PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                $error = 'Ошибка базы данных. Попробуйте позже.';
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
    <title>Регистрация в ChoicePoint</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .auth-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .auth-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border);
        }

        .auth-card h2 {
            text-align: center;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .auth-card .subtitle {
            text-align: center;
            color: var(--text-muted);
            margin-bottom: 30px;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            margin-bottom: 0;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-group small {
            display: block;
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .auth-card button[type="submit"] {
            width: 100%;
            margin-top: 20px;
        }

        .auth-card .divider {
            text-align: center;
            margin: 25px 0;
            color: var(--text-muted);
        }

        .auth-card .link-group {
            text-align: center;
        }

        .auth-card .link-group a {
            display: block;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .back-link {
            margin-top: 15px;
            text-align: center;
        }

        .back-link a {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .benefits {
            background: #f9fafb;
            border: 1px solid var(--border);
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            font-size: 0.9rem;
            line-height: 1.8;
        }

        .benefits strong {
            color: var(--dark);
        }

        .benefits div {
            margin-bottom: 10px;
        }

        .benefits div:last-child {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2> Присоединяйтесь</h2>
            <p class="subtitle">Создайте аккаунт в ChoicePoint</p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Security::getCSRFToken() ?>">

                <div class="form-group">
                    <label for="email">Email адрес</label>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        placeholder="your@example.com" 
                        required
                        autofocus
                    >
                    <small>Используется для входа и восстановления пароля</small>
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input 
                        type="password" 
                        id="password"
                        name="password" 
                        placeholder="••••••" 
                        required 
                        minlength="6"
                        maxlength="255"
                    >
                    <small>Минимум 6 символов</small>
                </div>

                <button type="submit" class="btn btn-primary">Создать аккаунт</button>
            </form>

            <div class="divider">или</div>

            <div class="link-group">
                <a href="login.php">Уже есть аккаунт? Войти →</a>
                <a href="../index.php">← На главную</a>
            </div>
        </div>

        <div class="benefits">
            <strong>✓ После регистрации вы сможете:</strong>
            <div>📝 Создавать свои дилеммы</div>
            <div>🗳️ Голосовать в опросах</div>
            <div>👍 Оценивать дилеммы</div>
            <div>💬 Участвовать в обсуждениях</div>
        </div>
    </div>
</body>
</html>
