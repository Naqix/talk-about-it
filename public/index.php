<?php

require __DIR__ . '/../src/db.php';

$group_count = $pdo->query('select count(*) from interest_groups')->fetchColumn();

?>
<!doctype html>
<html lang="sv">
  <head>
    <meta charset="utf-8">
    <title>Talk about it</title>
    <link rel="stylesheet" href="/style.css">
  </head>
  <body>
    <div class="layout">
      <h1>Talk about it</h1>
      <p>test</p>
      <p class="muted"><?= $group_count ?> st</p>
    </div>
  </body>
</html>
