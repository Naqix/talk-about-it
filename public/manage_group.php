<?php

require_once __DIR__ . "/../src/auth.php";
require_once __DIR__ . "/../src/layout.php";

$user = require_login($pdo);

$group_id = filter_var($_GET["group_id"] ?? "", FILTER_VALIDATE_INT);

if ($group_id === false) {
  header("Location: /index.php");
  exit;
}

require_group_administrator($pdo, $group_id, $user["id"]);

$statement = $pdo->prepare(
  "select r.user_id, u.first_name, u.last_name from membership_requests r join users u on u.id = r.user_id where r.group_id = ?"
);
$statement->execute([$group_id]);
$requests = $statement->fetchAll();

page_start("Hantera grupp", $user);
?>
      <h1>Ansökningar</h1>
<?php if (count($requests) === 0): ?>
      <p class="muted">Det finns inga ansökningar.</p>
<?php else: ?>
      <ul class="groups">
<?php foreach ($requests as $request): ?>
        <li class="group">
          <span class="groupName"><?= htmlspecialchars($request["first_name"] . " " . $request["last_name"]) ?></span>
        </li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
<?php
page_end();
