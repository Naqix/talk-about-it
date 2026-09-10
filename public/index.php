<?php

require_once __DIR__ . "/../src/auth.php";
require_once __DIR__ . "/../src/layout.php";

$user = current_user($pdo);

if ($user === null) {
  page_start("Start", null);
  ?>
      <h1>Talk about it</h1>
      <p>Skapa ett konto för att gå med i en grupp.</p>
      <div class="formActions">
        <a href="/register.php" class="button">Skapa konto</a>
        <a href="/login.php" class="button secondary">Logga in</a>
      </div>
<?php
  page_end();
  exit;
}

$statement = $pdo->prepare(
  "select g.id, g.name, m.role from interest_groups g join memberships m on m.group_id = g.id and m.user_id = ? order by g.name"
);
$statement->execute([$user["id"]]);
$my_groups = $statement->fetchAll();

$statement = $pdo->prepare(
  "select g.id, g.name, r.user_id is not null as requested from interest_groups g left join memberships m on m.group_id = g.id and m.user_id = ?
   left join membership_requests r on r.group_id = g.id and r.user_id = ? where m.user_id is null order by g.name"
);
$statement->execute([$user["id"], $user["id"]]);
$other_groups = $statement->fetchAll();

page_start("Start", $user);
?>
      <h1>Mina grupper</h1>
      <div class="groupSection">
<?php if (count($my_groups) === 0): ?>
        <p class="muted">Du är inte med i någon grupp än.</p>
<?php else: ?>
        <ul class="groups">
<?php foreach ($my_groups as $group): ?>
          <li class="group">
            <span class="groupName"><?= htmlspecialchars($group["name"]) ?></span>
            <span class="muted"><?= $group["role"] === "administrator" ? "Administratör" : "Medlem" ?></span>
            <a href="/group.php?group_id=<?= (int) $group["id"] ?>" class="button secondary">Öppna</a>
<?php if ($group["role"] === "administrator"): ?>
            <a href="/manage_group.php?group_id=<?= (int) $group["id"] ?>" class="button secondary">Hantera</a>
<?php endif; ?>
          </li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
        <a href="/create_group.php" class="button">Ny grupp</a>
      </div>

      <h2>Andra grupper</h2>
<?php if (count($other_groups) === 0): ?>
      <p class="muted">Det finns inga andra grupper just nu.</p>
<?php else: ?>
      <ul class="groups">
<?php foreach ($other_groups as $group): ?>
        <li class="group">
          <span class="groupName"><?= htmlspecialchars($group["name"]) ?></span>
<?php if ($group["requested"]): ?>
          <span class="muted">Ansökan skickad</span>
<?php else: ?>
          <form method="post" action="/apply.php">
            <input type="hidden" name="group_id" value="<?= (int) $group["id"] ?>">
            <button type="submit" class="button secondary">Ansök</button>
          </form>
<?php endif; ?>
        </li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
<?php
page_end();