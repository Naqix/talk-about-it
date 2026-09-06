<?php

require_once __DIR__ . "/../src/auth.php";
require_once __DIR__ . "/../src/layout.php";

$user = require_login($pdo);

$discussion_id = filter_var($_GET["discussion_id"] ?? "", FILTER_VALIDATE_INT);

if ($discussion_id === false) {
  header("Location: /index.php");
  exit;
}

$statement = $pdo->prepare("select id, group_id, subject from discussions where id = ?");
$statement->execute([$discussion_id]);
$discussion = $statement->fetch();

if ($discussion === false) {
  header("Location: /index.php");
  exit;
}

require_group_membership($pdo, $discussion["group_id"], $user["id"]);

$errors = [];
$values = ["body" => ""];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $values["body"] = is_string($_POST["body"] ?? null) ? trim($_POST["body"]) : "";

  if ($values["body"] === "") {
    $errors[] = "Inlägg måste fyllas i";
  } else {
    try {
      $statement = $pdo->prepare("insert into posts (discussion_id, group_id, user_id, body) values (?, ?, ?, ?)");
      $statement->execute([$discussion["id"], $discussion["group_id"], $user["id"], $values["body"]]);

      header("Location: /discussion.php?discussion_id=" . $discussion["id"]);
      exit;
    } catch (PDOException $error) {
      $errors[] = "Kunde inte svara";
    }
  }
}

$statement = $pdo->prepare(
  "select p.body, u.first_name, u.last_name from posts p join users u on u.id = p.user_id where p.discussion_id = ? order by p.id"
);
$statement->execute([$discussion["id"]]);
$posts = $statement->fetchAll();

page_start($discussion["subject"], $user);
?>
      <h1><?= htmlspecialchars($discussion["subject"]) ?></h1>
<?php error_list($errors); ?>
      <ul class="groups">
<?php foreach ($posts as $post): ?>
        <li class="post">
          <span class="muted"><?= htmlspecialchars($post["first_name"] . " " . $post["last_name"]) ?></span>
          <p><?= htmlspecialchars($post["body"]) ?></p>
        </li>
<?php endforeach; ?>
      </ul>

      <h2>Svara</h2>
      <form class="form" method="post" action="/discussion.php?discussion_id=<?= (int) $discussion["id"] ?>">
        <div class="field">
          <label for="body">Inlägg</label>
          <textarea id="body" name="body" rows="4" required><?= htmlspecialchars($values["body"]) ?></textarea>
        </div>

        <div class="formActions">
          <button type="submit" class="button">Svara</button>
        </div>
      </form>
<?php
page_end();
