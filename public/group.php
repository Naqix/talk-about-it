<?php

require_once __DIR__ . "/../src/auth.php";
require_once __DIR__ . "/../src/layout.php";

$user = require_login($pdo);

$group_id = filter_var($_GET["group_id"] ?? "", FILTER_VALIDATE_INT);

if ($group_id === false) {
  header("Location: /index.php");
  exit;
}

require_group_membership($pdo, $group_id, $user["id"]);

$statement = $pdo->prepare("select name from interest_groups where id = ?");
$statement->execute([$group_id]);
$group = $statement->fetch();

function validate_discussion($post) {
  $errors = [];

  $subject = is_string($post["subject"] ?? null) ? trim($post["subject"]) : "";
  if ($subject === "") {
    $errors[] = "Ämne måste fyllas i";
  } elseif (mb_strlen($subject) > 200) {
    $errors[] = "Ämnet får vara högst 200 tecken";
  }

  $body = is_string($post["body"] ?? null) ? trim($post["body"]) : "";
  if ($body === "") {
    $errors[] = "Inlägg måste fyllas i";
  }

  return ["errors" => $errors, "values" => ["subject" => $subject, "body" => $body]];
}

$errors = [];
$values = ["subject" => "", "body" => ""];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  ["errors" => $errors, "values" => $values] = validate_discussion($_POST);

  if (count($errors) === 0) {
    try {
      $pdo->beginTransaction();

      $statement = $pdo->prepare("insert into discussions (group_id, subject) values (?, ?)");
      $statement->execute([$group_id, $values["subject"]]);
      $discussion_id = $pdo->lastInsertId();

      $statement = $pdo->prepare("insert into posts (discussion_id, group_id, user_id, body) values (?, ?, ?, ?)");
      $statement->execute([$discussion_id, $group_id, $user["id"], $values["body"]]);

      $pdo->commit();

      header("Location: /discussion.php?discussion_id=" . $discussion_id);
      exit;
    } catch (PDOException $error) {
      $pdo->rollBack();
      $errors[] = "Kunde inte starta diskussionen";
    }
  }
}

$statement = $pdo->prepare("select id, subject from discussions where group_id = ?");
$statement->execute([$group_id]);
$discussions = $statement->fetchAll();

page_start($group["name"], $user);
?>
      <h1><?= htmlspecialchars($group["name"]) ?></h1>
<?php error_list($errors); ?>
      <h2>Diskussioner</h2>
      <div class="groupSection">
<?php if (count($discussions) === 0): ?>
        <p class="muted">Det finns inga diskussioner just nu.</p>
<?php else: ?>
        <ul class="groups">
<?php foreach ($discussions as $discussion): ?>
          <li class="group">
            <span class="groupName"><?= htmlspecialchars($discussion["subject"]) ?></span>
            <a href="/discussion.php?discussion_id=<?= (int) $discussion["id"] ?>" class="button secondary">Öppna</a>
          </li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
      </div>

      <h2>Ny diskussion</h2>
      <form class="form" method="post" action="/group.php?group_id=<?= (int) $group_id ?>">
        <div class="field">
          <label for="subject">Ämne</label>
          <input id="subject" name="subject" value="<?= htmlspecialchars($values["subject"]) ?>" maxlength="200" required>
        </div>

        <div class="field">
          <label for="body">Inlägg</label>
          <textarea id="body" name="body" rows="4" required><?= htmlspecialchars($values["body"]) ?></textarea>
        </div>

        <div class="formActions">
          <button type="submit" class="button">Starta</button>
        </div>
      </form>
<?php
page_end();
