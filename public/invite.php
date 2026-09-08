<?php

require_once __DIR__ . "/../src/auth.php";
require_once __DIR__ . "/../src/layout.php";

$token = $_GET["token"] ?? null;

if (is_string($token) && $token !== "") {
  $_SESSION["invitation"] = hash("sha256", $token);

  header("Location: /invite.php");
  exit;
}

$user = require_login($pdo);

$token_hash = is_string($_SESSION["invitation"] ?? null) ? $_SESSION["invitation"] : "";

if ($token_hash === "") {
  header("Location: /index.php");
  exit;
}

$statement = $pdo->prepare(
  "select i.group_id, g.name from invitations i join interest_groups g on g.id = i.group_id where i.token_hash = ? and i.expires_at > current_timestamp"
);
$statement->execute([$token_hash]);
$invitation = $statement->fetch();

if ($invitation !== false) {
  $statement = $pdo->prepare("select user_id from memberships where group_id = ? and user_id = ?");
  $statement->execute([$invitation["group_id"], $user["id"]]);

  if ($statement->fetch() !== false) {
    unset($_SESSION["invitation"]);

    header("Location: /group.php?group_id=" . $invitation["group_id"]);
    exit;
  }
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && $invitation !== false) {
  try {
    $pdo->beginTransaction();

    $statement = $pdo->prepare("delete from invitations where token_hash = ? and expires_at > current_timestamp");
    $statement->execute([$token_hash]);
    $joined = $statement->rowCount() === 1;

    if ($joined) {
      $statement = $pdo->prepare("delete from membership_requests where group_id = ? and user_id = ?");
      $statement->execute([$invitation["group_id"], $user["id"]]);

      $statement = $pdo->prepare("insert into memberships (group_id, user_id, role) values (?, ?, ?)");
      $statement->execute([$invitation["group_id"], $user["id"], "member"]);
    }

    $pdo->commit();

    if ($joined) {
      unset($_SESSION["invitation"]);

      header("Location: /group.php?group_id=" . $invitation["group_id"]);
      exit;
    }

    $invitation = false;
  } catch (PDOException $error) {
    $pdo->rollBack();
    $errors[] = "Kunde inte gå med i gruppen";
  }
}

if ($invitation === false) {
  unset($_SESSION["invitation"]);
}

page_start("Inbjudan", $user);
?>
      <h1>Inbjudan</h1>
<?php error_list($errors); ?>
<?php if ($invitation === false): ?>
      <p class="muted">Ogiltig inbjudan.</p>
<?php else: ?>
      <p>Du är inbjuden till <?= htmlspecialchars($invitation["name"]) ?>.</p>
      <form method="post" action="/invite.php">
        <button type="submit" class="button">Gå med</button>
      </form>
<?php endif; ?>
<?php
page_end();