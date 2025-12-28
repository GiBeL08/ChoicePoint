<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Controllers/QuestionController.php';

$qc = new QuestionController();
$isAuth = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$userEmail = null;

if ($isAuth) {
    try {
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id=? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $userEmail = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching user email: ".$e->getMessage());
    }
}

$questions = $qc->getActiveQuestions() ?: [];

// Сообщения
$vote_msg = $_SESSION['vote_msg'] ?? null;
$vote_error = $_SESSION['vote_error'] ?? null;
$reaction_error = $_SESSION['reaction_error'] ?? null;
unset($_SESSION['vote_msg'], $_SESSION['vote_error'], $_SESSION['reaction_error']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ChoicePoint — Платформа для дилемм</title>
<link rel="stylesheet" href="public/style.css">
</head>
<body>

<header>
<div class="container">
    <h1>ChoicePoint</h1>
    <p style="color: rgba(255,255,255,0.9); margin:8px 0 0 0; font-size:1rem;">Помогите другим выбрать — поделитесь дилеммой</p>
    <nav>
        <?php if (!$isAuth): ?>
            <a href="public/login.php" class="btn btn-outline">Войти</a>
            <a href="public/register.php" class="btn btn-outline">Регистрация</a>
        <?php else: ?>
            <div class="user-info">👤 <?= htmlspecialchars($userEmail ?? 'пользователь') ?></div>
            <a href="public/add_question.php" class="btn btn-outline">➕ Новая дилемма</a>
            <?php if ($isAdmin): ?>
                <a href="public/admin/index.php" class="btn btn-outline">⚙️ Админ-панель</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-outline">Выход</a>
        <?php endif; ?>
    </nav>
</div>
</header>

<div class="container">

<?php if ($vote_msg): ?>
    <div class="alert alert-success">✓ <?= htmlspecialchars($vote_msg) ?></div>
<?php endif; ?>
<?php if ($vote_error): ?>
    <div class="alert alert-error">✕ <?= htmlspecialchars($vote_error) ?></div>
<?php endif; ?>
<?php if ($reaction_error): ?>
    <div class="alert alert-error">✕ <?= htmlspecialchars($reaction_error) ?></div>
<?php endif; ?>

<?php if (empty($questions)): ?>
    <div style="text-align:center; padding:60px 20px;">
        <h3 style="font-size:1.5rem; color:var(--text-muted); margin-bottom:10px;">🤔 Пока здесь тихо</h3>
        <p style="color:var(--text-muted); margin-bottom:30px;">Будьте первым, кто создаст дилемму!</p>
        <?php if ($isAuth): ?>
            <a href="public/add_question.php" class="btn btn-primary">➕ Создать первую дилемму</a>
        <?php else: ?>
            <a href="public/register.php" class="btn btn-primary">Присоединиться</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php foreach ($questions as $q): ?>
<div class="question-card" id="q<?= (int)$q['id'] ?>">
    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:16px;">
        <div>
            <h3><?= htmlspecialchars($q['title']) ?></h3>
            <?php if (!empty($q['category'])): ?>
                <span class="category"><?= htmlspecialchars($q['category']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($q['description'])): ?>
        <p class="description"><?= nl2br(htmlspecialchars($q['description'])) ?></p>
    <?php endif; ?>

    <?php
    $options = $qc->getOptions($q['id']);
    $userVote = $isAuth ? $qc->getUserVote($_SESSION['user_id'], $q['id']) : null;
    ?>

    <div class="options-container">
    <?php if ($isAuth && $userVote): ?>
        <div class="voted-banner">Вы уже проголосовали</div>
        <?php foreach ($options as $opt): ?>
            <div class="option-item <?= ($opt['id'] == $userVote) ? 'chosen' : '' ?>">
                <label><?= htmlspecialchars($opt['text']) ?><?php if ($opt['id'] == $userVote) echo " <span style='margin-left:10px; color:var(--success);'>← ваш выбор</span>"; ?></label>
            </div>
        <?php endforeach; ?>
    <?php elseif ($isAuth): ?>
        <form method="post" action="vote.php">
            <input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>">
            <?php foreach ($options as $opt): ?>
                <label class="option-item">
                    <input type="radio" name="option" value="<?= (int)$opt['id'] ?>" required>
                    <span><?= htmlspecialchars($opt['text']) ?></span>
                </label>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary" style="margin-top:16px;">Проголосовать</button>
        </form>
    <?php else: ?>
        <?php foreach ($options as $opt): ?>
            <div class="option-item" style="cursor:default; background:#f9fafb;">
                <label style="margin:0; color:var(--text);"><?= htmlspecialchars($opt['text']) ?></label>
            </div>
        <?php endforeach; ?>
        <div class="login-prompt"><a href="public/login.php">Войдите</a> или <a href="public/register.php">зарегистрируйтесь</a>, чтобы проголосовать</div>
    <?php endif; ?>
    </div>

    <div class="results-section">
        <h4>📊 Результаты голосования</h4>
        <?php
        $results = $qc->getResults($q['id']);
        $totalVotes = array_sum(array_map(fn($x) => (int)$x['votes'], $results));
        ?>
        <?php foreach ($results as $r): ?>
            <?php $perc = $totalVotes>0 ? round((int)$r['votes']/$totalVotes*100) : 0; ?>
            <div class="result-item">
                <div class="result-item-label">
                    <span><?= htmlspecialchars($r['text']) ?></span>
                    <span><?= (int)$r['votes'] ?> голос<?= (int)$r['votes']%10==1 && (int)$r['votes']!=11 ? '' : ((int)$r['votes']%10==0 || (int)$r['votes']%10>=5 ? 'ов' : 'а') ?> • <?= $perc ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?= $perc ?>%;"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($isAuth): 
        $reaction = $qc->getReaction($_SESSION['user_id'], $q['id']);
    ?>
        <form method="post" action="reaction.php" style="margin-top:20px; padding-top:20px; border-top:1px solid var(--border); display:flex; gap:12px;">
            <input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>">
            <button type="submit" name="reaction" value="like" class="btn btn-outline" style="background:<?= $reaction==='like'?'var(--primary)':'white' ?>; color:<?= $reaction==='like'?'white':'var(--primary)' ?>; border-color:var(--primary);">
                👍 <?= (int)($q['likes'] ?? 0) ?>
            </button>
            <button type="submit" name="reaction" value="dislike" class="btn btn-outline" style="background:<?= $reaction==='dislike'?'var(--error)':'white' ?>; color:<?= $reaction==='dislike'?'white':'var(--error)' ?>; border-color:var(--error);">
                👎 <?= (int)($q['dislikes'] ?? 0) ?>
            </button>
        </form>
    <?php else: ?>
        <div style="margin-top:20px; padding-top:20px; border-top:1px solid var(--border); display:flex; gap:12px;">
            <button disabled class="btn" style="background:#f9fafb; color:var(--text-muted); border:1px solid var(--border); cursor:not-allowed;">
                👍 <?= (int)($q['likes'] ?? 0) ?>
            </button>
            <button disabled class="btn" style="background:#f9fafb; color:var(--text-muted); border:1px solid var(--border); cursor:not-allowed;">
                👎 <?= (int)($q['dislikes'] ?? 0) ?>
            </button>
        </div>
    <?php endif; ?>
 
    <?php
$comments = $qc->getComments($q['id']);
?>

<div style="margin-top:20px; border-top:1px solid var(--border); padding-top:16px;">
    <h4>💬 Обсуждение</h4>

    <?php if (empty($comments)): ?>
        <p style="color:var(--text-muted);">Пока нет комментариев</p>
    <?php endif; ?>

    <div style="margin-top:20px; border-top:1px solid var(--border); padding-top:16px;">
    <h4>💬 Обсуждение</h4>

    <div style="
        max-height:220px;
        overflow-y:auto;
        padding-right:6px;
        margin-bottom:12px;
    ">
        <?php if (empty($comments)): ?>
            <p style="color:var(--text-muted);">Пока нет комментариев</p>
        <?php endif; ?>

        <?php foreach ($comments as $c): ?>
            <div style="
                margin-bottom:10px;
                padding:10px;
                background:#f9fafb;
                border-radius:8px;
            ">
                <strong><?= htmlspecialchars($c['email']) ?></strong><br>
                <span><?= nl2br(htmlspecialchars($c['text'])) ?></span>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">
                    <?= $c['created_at'] ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($isAuth): ?>
        <form method="post" action="comment.php">
            <input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>">
            <textarea name="text"
                      required
                      maxlength="500"
                      placeholder="Написать комментарий..."
                      style="width:100%; resize:none; height:70px;"></textarea>
            <button class="btn btn-primary" style="margin-top:8px;">
                Отправить
            </button>
        </form>
    <?php else: ?>
        <p style="margin-top:8px;">
            <a href="public/login.php">Войдите</a>, чтобы участвовать в обсуждении
        </p>
    <?php endif; ?>
</div>


    
</div>

</div>
<?php endforeach; ?>

</div>

<footer>
    <p style="margin:0;">&copy; 2025 <strong>ChoicePoint</strong> — Платформа для сложных выборов</p>
    <p style="margin:8px 0 0 0; color:rgba(255,255,255,0.7); font-size:0.9rem;">Помогайте друг другу принимать решения</p>
</footer>

<script src="public/script.js"></script>
</body>
</html>
