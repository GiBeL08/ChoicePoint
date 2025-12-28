<?php
session_start();
require_once __DIR__ . '/app/Controllers/QuestionController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['reaction'], $_POST['question_id'])) {
    header('Location: index.php');
    exit;
}

$questionId = (int)($_POST['question_id'] ?? 0);
$reaction = $_POST['reaction'] === 'like' ? 'like' : 'dislike';

$qc = new QuestionController();

// Добавляем реакцию
try {
    $qc->addReaction($_SESSION['user_id'], $questionId, $reaction);
} catch (Exception $e) {
    $_SESSION['reaction_error'] = 'Ошибка при добавлении реакции: ' . $e->getMessage();
}

header('Location: index.php');
exit;
