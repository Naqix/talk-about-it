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

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $member_id = filter_var($_POST["user_id"] ?? "", FILTER_VALIDATE_INT);

  if ($member_id === false) {
    header("Location: /index.php");
    exit;
  }

  if (isset($_POST["role"])) {
    $role = is_string($_POST["role"]) ? $_POST["role"] : "";

    if (!in_array($role, ["member", "administrator"], true) || $member_id === (int) $user["id"]) {
      header("Location: /index.php");
      exit;
    }

    try {
      $statement = $pdo->prepare("update memberships set role = ? where group_id = ? and user_id = ?");
      $statement->execute([$role, $group_id, $member_id]);

      header("Location: /manage_group.php?group_id=" . $group_id);
      exit;
    } catch (PDOException $error) {
      $errors[] = "Kunde inte spara rollen";
    }
  } else {
    try {
      $pdo->beginTransaction();

      $statement = $pdo->prepare("delete from membership_requests where group_id = ? and user_id = ?");
      $statement->execute([$group_id, $member_id]);

      if ($statement->rowCount() === 1) {
        $statement = $pdo->prepare("insert into memberships (group_id, user_id, role) values (?, ?, ?)");
        $statement->execute([$group_id, $member_id, "member"]);
      }

      $pdo->commit();

      header("Location: /manage_group.php?group_id=" . $group_id);
      exit;
    } catch (PDOException $error) {
      $pdo->rollBack();
      $errors[] = "Godkänning misslyckades";
    }
  }
}

$statement = $pdo->prepare(
  "select r.user_id, u.first_name, u.last_name from membership_requests r join users u on u.id = r.user_id where r.group_id = ?"
);
$statement->execute([$group_id]);
$requests = $statement->fetchAll();

$statement = $pdo->prepare(
  "select m.user_id, m.role, u.first_name, u.last_name from memberships m join users u on u.id = m.user_id where m.group_id = ? and m.user_id <> ?"
);
$statement->execute([$group_id, $user["id"]]);
$members = $statement->fetchAll();

page_start("Hantera grupp", $user);
?>
      <h1>Ansökningar</h1>
<?php error_list($errors); ?>
      <div class="groupSection">
<?php if (count($requests) === 0): ?>
        <p class="muted">Det finns inga ansökningar just nu.</p>
<?php else: ?>
        <ul class="groups">
<?php foreach ($requests as $request): ?>
          <li class="group">
            <span class="groupName"><?= htmlspecialchars($request["first_name"] . " " . $request["last_name"]) ?></span>
            <form method="post" action="/manage_group.php?group_id=<?= (int) $group_id ?>">
              <input type="hidden" name="user_id" value="<?= (int) $request["user_id"] ?>">
              <button type="submit" class="button secondary">Godkänn</button>
            </form>
          </li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
      </div>

      <h2>Medlemmar</h2>
<?php if (count($members) === 0): ?>
      <p class="muted">Det finns inga andra medlemmar just nu.</p>
<?php else: ?>
      <ul class="groups">
<?php foreach ($members as $member): ?>
        <li class="group">
          <span class="groupName"><?= htmlspecialchars($member["first_name"] . " " . $member["last_name"]) ?></span>
          <form method="post" action="/manage_group.php?group_id=<?= (int) $group_id ?>">
            <input type="hidden" name="user_id" value="<?= (int) $member["user_id"] ?>">
            <select name="role">
              <option value="member"<?= $member["role"] === "member" ? " selected" : "" ?>>Medlem</option>
              <option value="administrator"<?= $member["role"] === "administrator" ? " selected" : "" ?>>Administratör</option>
            </select>
            <button type="submit" class="button secondary">Spara</button>
          </form>
        </li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
<?php
page_end();