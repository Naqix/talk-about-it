<?php

function page_start($title, $user) {
  ?>
<!doctype html>
<html lang="sv">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Talk about it</title>
    <link rel="stylesheet" href="/style.css">
  </head>
  <body>
    <div class="layout">
      <header class="topbar">
        <a href="/index.php" class="logo">Talk about it</a>
        <nav>
<?php if ($user === null): ?>
          <a href="/login.php">Logga in</a>
          <a href="/register.php">Skapa konto</a>
<?php else: ?>
          <span class="muted"><?= htmlspecialchars($user["first_name"] . " " . $user["last_name"]) ?></span>
          <a href="/logout.php">Logga ut</a>
<?php endif; ?>
        </nav>
      </header>
      <main>
<?php
}

function page_end() {
  ?>
      </main>
    </div>
  </body>
</html>
<?php
}

function error_list($errors) {
  if (count($errors) === 0) {
    return;
  }
  ?>
      <ul class="error">
<?php foreach ($errors as $error): ?>
        <li><?= htmlspecialchars($error) ?></li>
<?php endforeach; ?>
      </ul>
<?php
}