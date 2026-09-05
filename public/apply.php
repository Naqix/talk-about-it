<?php

require_once __DIR__ . "/../src/auth.php";

$user = require_login($pdo);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: /index.php");
  exit;
}

$group_id = filter_var($_POST["group_id"] ?? "", FILTER_VALIDATE_INT);

if ($group_id === false) {
  header("Location: /index.php");
  exit;
}

$statement = $pdo->prepare("select id from interest_groups where id = ?");
$statement->execute([$group_id]);

if ($statement->fetch() === false) {
  header("Location: /index.php");
  exit;
}

$statement = $pdo->prepare("select user_id from memberships where group_id = ? and user_id = ?");
$statement->execute([$group_id, $user["id"]]);

if ($statement->fetch() !== false) {
  header("Location: /index.php");
  exit;
}

$statement = $pdo->prepare("insert ignore into membership_requests (group_id, user_id) values (?, ?)");
$statement->execute([$group_id, $user["id"]]);

header("Location: /index.php");
exit;