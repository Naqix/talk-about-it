<?php

require_once __DIR__ . "/db.php";

session_set_cookie_params([
  "httponly" => true,
  "samesite" => "Lax",
]);

session_start();

function current_user($pdo) {
  if (!isset($_SESSION["user_id"])) {
    return null;
  }

  $statement = $pdo->prepare("select id, first_name, last_name, email from users where id = ?");
  $statement->execute([$_SESSION["user_id"]]);
  $user = $statement->fetch();

  if ($user === false) {
    return null;
  }

  return $user;
}

function require_login($pdo) {
  $user = current_user($pdo);

  if ($user === null) {
    header("Location: /login.php");
    exit;
  }

  return $user;
}

function login_user($user_id) {
  session_regenerate_id(true);
  $_SESSION["user_id"] = (int) $user_id;
}

function logout_user() {
  $_SESSION = [];
  session_destroy();
}