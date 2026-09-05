<?php

require_once __DIR__ . "/../src/auth.php";
require_once __DIR__ . "/../src/layout.php";

if (current_user($pdo) !== null) {
  header("Location: /index.php");
  exit;
}

function validate_login($post) {
  $errors = [];

  $email = is_string($post["email"] ?? null) ? trim($post["email"]) : "";
  if ($email === "") {
    $errors[] = "Email måste fyllas i";
  }

  $password = is_string($post["password"] ?? null) ? $post["password"] : "";
  if ($password === "") {
    $errors[] = "Lösenord måste fyllas i";
  }

  return ["errors" => $errors, "values" => ["email" => $email, "password" => $password]];
}

$errors = [];
$values = ["email" => "", "password" => ""];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  ["errors" => $errors, "values" => $values] = validate_login($_POST);

  if (count($errors) === 0) {
    $statement = $pdo->prepare("select id, password_hash from users where email = ?");
    $statement->execute([$values["email"]]);
    $user = $statement->fetch();

    if ($user === false || !password_verify($values["password"], $user["password_hash"])) {
      $errors[] = "Fel email eller lösenord";
    } else {
      login_user($user["id"]);
      header("Location: /index.php");
      exit;
    }
  }
}

page_start("Logga in", null);
?>
      <h1>Logga in</h1>
<?php error_list($errors); ?>
      <form class="form" method="post" action="/login.php">
        <div class="field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" value="<?= htmlspecialchars($values["email"]) ?>" maxlength="255" required>
        </div>

        <div class="field">
          <label for="password">Lösenord</label>
          <input id="password" name="password" type="password" required>
        </div>

        <div class="formActions">
          <button type="submit" class="button">Logga in</button>
        </div>
      </form>
<?php
page_end();