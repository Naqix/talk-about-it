<?php

require_once __DIR__ . "/../src/auth.php";
require_once __DIR__ . "/../src/layout.php";

$user = require_login($pdo);

function validate_group($post) {
  $errors = [];

  $name = is_string($post["name"] ?? null) ? trim($post["name"]) : "";
  if ($name === "") {
    $errors[] = "Namn måste fyllas i";
  } elseif (mb_strlen($name) > 200) {
    $errors[] = "Namnet får vara högst 200 tecken";
  }

  return ["errors" => $errors, "values" => ["name" => $name]];
}

$errors = [];
$values = ["name" => ""];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  ["errors" => $errors, "values" => $values] = validate_group($_POST);

  if (count($errors) === 0) {
    try {
      $pdo->beginTransaction();

      $statement = $pdo->prepare("insert into interest_groups (name) values (?)");
      $statement->execute([$values["name"]]);
      $group_id = $pdo->lastInsertId();

      $statement = $pdo->prepare("insert into memberships (group_id, user_id, role) values (?, ?, ?)");
      $statement->execute([$group_id, $user["id"], "administrator"]);

      $pdo->commit();

      header("Location: /index.php");
      exit;
    } catch (PDOException $error) {
      $pdo->rollBack();
      $errors[] = "Kunde inte skapa gruppen";
    }
  }
}

page_start("Ny grupp", $user);
?>
      <h1>Ny grupp</h1>
<?php error_list($errors); ?>
      <form class="form" method="post" action="/create_group.php">
        <div class="field">
          <label for="name">Namn</label>
          <input id="name" name="name" value="<?= htmlspecialchars($values["name"]) ?>" maxlength="200" required>
        </div>

        <div class="formActions">
          <button type="submit" class="button">Spara</button>
          <a href="/index.php" class="button secondary">Avbryt</a>
        </div>
      </form>
<?php
page_end();