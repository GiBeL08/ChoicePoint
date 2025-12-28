<?php
require_once __DIR__ . '/../../config/database.php';

class QuestionController {

    /* ===== ПОЛУЧЕНИЕ ===== */

    public function getActiveQuestions() {
        global $pdo;
        return $pdo->query("
            SELECT * FROM questions
            WHERE is_active = 1 AND is_approved = 1
            ORDER BY created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingQuestions() {
        global $pdo;
        return $pdo->query("
            SELECT * FROM questions
            WHERE is_approved = 0
            ORDER BY created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOptions($question_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM options WHERE question_id = ?");
        $stmt->execute([$question_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ===== ГОЛОСОВАНИЕ ===== */

    public function vote($user_id, $question_id, $option_id) {
        global $pdo;

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO votes (user_id, question_id, option_id)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$user_id, $question_id, $option_id]);
    }

    public function getUserVote($user_id, $question_id) {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT option_id FROM votes
            WHERE user_id = ? AND question_id = ?
        ");
        $stmt->execute([$user_id, $question_id]);
        return $stmt->fetchColumn();
    }

    /* ===== РЕЗУЛЬТАТЫ ===== */

    public function getResults($question_id) {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT o.id, o.text, COUNT(v.id) AS votes
            FROM options o
            LEFT JOIN votes v ON o.id = v.option_id
            WHERE o.question_id = ?
            GROUP BY o.id
        ");
        $stmt->execute([$question_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ===== ДОБАВЛЕНИЕ ===== */

    public function addQuestion($user_id, $title, $options, $description, $category) {
        global $pdo;

        if (mb_strlen($title) < 3 || mb_strlen($title) > 120) return 'bad_title';
        if (count($options) < 2 || count($options) > 5) return 'few_options';

        $stmt = $pdo->prepare("
            INSERT INTO questions (user_id, title, description, category, is_active, is_approved)
            VALUES (?, ?, ?, ?, 1, 0)
        ");
        $stmt->execute([$user_id, $title, $description, $category]);
        $qid = $pdo->lastInsertId();

        $opt = $pdo->prepare("INSERT INTO options (question_id, text) VALUES (?, ?)");
        foreach ($options as $o) {
            $opt->execute([$qid, $o]);
        }

        return 'success';
    }

    /* ===== АДМИН ===== */

    public function deleteQuestion($id) {
        global $pdo;
        $pdo->prepare("DELETE FROM question_reactions WHERE question_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM votes WHERE question_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM options WHERE question_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM questions WHERE id=?")->execute([$id]);
        return true;
    }

    public function updateQuestion($question_id, $title, $description, $options) {
        global $pdo;

        $stmt = $pdo->prepare("
            UPDATE questions SET title=?, description=? WHERE id=?
        ");
        $stmt->execute([$title, $description, $question_id]);

        foreach ($options as $id => $text) {
            $stmt = $pdo->prepare("UPDATE options SET text=? WHERE id=?");
            $stmt->execute([$text, $id]);
        }

        return true;
    }

    public function approveQuestion($id) {
        global $pdo;
        $pdo->prepare("UPDATE questions SET is_approved=1 WHERE id=?")->execute([$id]);
    }

    /* ===== ЛАЙКИ / ДИЗЛАЙКИ ===== */

public function getReaction($userId, $questionId) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT reaction FROM question_reactions
        WHERE user_id = ? AND question_id = ?
    ");
    $stmt->execute([$userId, $questionId]);

    return $stmt->fetchColumn();
}

public function addReaction($userId, $questionId, $reaction) {
    global $pdo;

    if (!in_array($reaction, ['like', 'dislike'])) {
        return false;
    }

    $pdo->beginTransaction();

    $current = $this->getReaction($userId, $questionId);

    if (!$current) {
        // новая реакция
        $stmt = $pdo->prepare("
            INSERT INTO question_reactions (user_id, question_id, reaction)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $questionId, $reaction]);

    } elseif ($current !== $reaction) {
        // смена реакции
        $stmt = $pdo->prepare("
            UPDATE question_reactions
            SET reaction = ?
            WHERE user_id = ? AND question_id = ?
        ");
        $stmt->execute([$reaction, $userId, $questionId]);

    } else {
        // повторный клик — ничего не делаем
        $pdo->commit();
        return true;
    }

    // пересчёт лайков / дизлайков
    $stmt = $pdo->prepare("
        SELECT
            SUM(reaction = 'like') AS likes,
            SUM(reaction = 'dislike') AS dislikes
        FROM question_reactions
        WHERE question_id = ?
    ");
    $stmt->execute([$questionId]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->prepare("
        UPDATE questions
        SET likes = ?, dislikes = ?
        WHERE id = ?
    ")->execute([
        (int)$counts['likes'],
        (int)$counts['dislikes'],
        $questionId
    ]);

    $pdo->commit();

    // проверка автоудаления
    $this->checkAutoDelete($questionId);

    return true;
}


/* ===== АВТО-УДАЛЕНИЕ ПО ДИЗЛАЙКАМ ===== */

private function checkAutoDelete($questionId) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT likes, dislikes, is_active
        FROM questions
        WHERE id = ?
    ");
    $stmt->execute([$questionId]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$r || (int)$r['is_active'] === 0) return;

    $likes = (int)$r['likes'];
    $dislikes = (int)$r['dislikes'];
    $total = $likes + $dislikes;

    /* ===============================
       DEMO MODE (ДЛЯ ЭКЗАМЕНА)
       В продакшене: $total < 10
    =============================== */
    if ($total < 3) return;

    if (($dislikes / $total) >= 0.7) {
        $this->softDeleteQuestion(
            $questionId,
            'Удалено автоматически: более 70% дизлайков'
        );
    }
}


/* ===== МЯГКОЕ УДАЛЕНИЕ (SOFT DELETE) ===== */

public function softDeleteQuestion($questionId, $reason) {
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE questions
        SET 
            is_active = 0,
            removed_reason = ?,
            removed_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$reason, $questionId]);
}

    /* ===== КОММЕНТАРИИ ===== */

public function addComment($userId, $questionId, $text) {
    global $pdo;

    if (mb_strlen($text) < 2 || mb_strlen($text) > 500) {
        return false;
    }

    $stmt = $pdo->prepare("
        INSERT INTO comments (user_id, question_id, text)
        VALUES (?, ?, ?)
    ");
    return $stmt->execute([$userId, $questionId, $text]);
}

public function getComments($questionId) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT c.*, u.email
        FROM comments c
        JOIN users u ON u.id = c.user_id
        WHERE c.question_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$questionId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

}
