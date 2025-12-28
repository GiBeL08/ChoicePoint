<?php
session_start();

/* Удаляем все данные сессии */
$_SESSION = [];

/* Уничтожаем сессию */
session_destroy();

/* Возвращаем на главную страницу */
header('Location: index.php');
exit;
