<?php
session_start();
require_once __DIR__ . '/app/Controllers/QuestionController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$questionId = (int)($_POST['question_id'] ?? 0);
$optionId   = (int)($_POST['option'] ?? 0);

if ($questionId <= 0 || $optionId <= 0) {
    $_SESSION['vote_error'] = 'Некорректные данные';
    header('Location: index.php');
    exit;
}

$qc = new QuestionController();

// ❗ ВАЖНО: проверяем, голосовал ли уже
$alreadyVoted = $qc->getUserVote($_SESSION['user_id'], $questionId);

if ($alreadyVoted) {
    $_SESSION['vote_error'] = 'Вы уже голосовали в этой дилемме';
    header('Location: index.php');
    exit;
}

$success = $qc->vote($_SESSION['user_id'], $questionId, $optionId);

if ($success) {
    $_SESSION['vote_msg'] = 'Ваш голос принят!';
} else {
    $_SESSION['vote_error'] = 'Ошибка при голосовании';
}

header('Location: index.php');
exit;
