<?php
require '../config/database.php';
$stmt = $pdo->query("SELECT 1");
var_dump($stmt->fetch());
