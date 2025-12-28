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
$text = trim($_POST['text'] ?? '');

if ($questionId <= 0 || $text === '') {
    header('Location: index.php');
    exit;
}

$qc = new QuestionController();
$qc->addComment($_SESSION['user_id'], $questionId, $text);

header('Location: index.php#q' . $questionId);
exit;
