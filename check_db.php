<?php
/**
 * Быстрая проверка структуры БД
 * Откройте: http://localhost/check_db.php
 */

$host = 'localhost';
$db   = 'choicepoint';
$user = 'root';
$pass = '';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass);
    
    // Получить структуру таблицы question_reactions
    $tables = [
        'users',
        'questions', 
        'options',
        'votes',
        'question_reactions'
    ];
    
    echo '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Проверка БД</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        table { background: white; border-collapse: collapse; width: 100%; margin-bottom: 30px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #007bff; color: white; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .code { background: #f0f0f0; padding: 10px; border-radius: 5px; margin: 10px 0; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Проверка структуры БД ChoicePoint</h1>
    ';
    
    foreach ($tables as $table) {
        echo '<h2>' . htmlspecialchars($table) . '</h2>';
        
        try {
            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll();
            
            echo '<table>';
            echo '<tr><th>Поле</th><th>Тип</th><th>Null</th><th>Ключ</th><th>По умолчанию</th></tr>';
            
            foreach ($columns as $col) {
                echo '<tr>';
                echo '<td><code>' . htmlspecialchars($col['Field']) . '</code></td>';
                echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Default'] ?? '-') . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            
            echo '<p class="ok">✓ Таблица существует</p>';
            
        } catch (Exception $e) {
            echo '<p class="error">✕ Ошибка: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
    
    // Проверка индексов для question_reactions
    echo '<h2>Индексы таблицы question_reactions</h2>';
    try {
        $stmt = $pdo->query("SHOW INDEX FROM question_reactions");
        $indexes = $stmt->fetchAll();
        
        echo '<table>';
        echo '<tr><th>Таблица</th><th>Колонка</th><th>Имя индекса</th><th>Уникальный</th></tr>';
        
        foreach ($indexes as $idx) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($idx['Table']) . '</td>';
            echo '<td>' . htmlspecialchars($idx['Column_name']) . '</td>';
            echo '<td><code>' . htmlspecialchars($idx['Key_name']) . '</code></td>';
            echo '<td>' . ($idx['Non_unique'] == 0 ? 'Да' : 'Нет') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    } catch (Exception $e) {
        echo '<p class="error">✕ Ошибка: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    
    echo '</body></html>';
    
} catch (PDOException $e) {
    die("❌ Ошибка подключения к БД: " . htmlspecialchars($e->getMessage()));
}
?>
