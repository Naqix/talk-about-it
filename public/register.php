<?php

require_once __DIR__ . "/../src/auth.php";
require_once __DIR__ . "/../src/layout.php";

if (current_user($pdo) !== null) {
  header("Location: /index.php");
  exit;
}

function validate_registration($post, $pdo) {
  $errors = [];

  $first_name = is_string($post["first_name"] ?? null) ? trim($post["first_name"]) : "";
  if ($first_name === "") {
    $errors[] = "Förnamn måste fyllas i";
  } elseif (mb_strlen($first_name) > 100) {
    $errors[] = "Förnamnet får vara högst 100 tecken";
  }

  $last_name = is_string($post["last_name"] ?? null) ? trim($post["last_name"]) : "";
  if ($last_name === "") {
    $errors[] = "Efternamn måste fyllas i";
  } elseif (mb_strlen($last_name) > 100) {
    $errors[] = "Efternamnet får vara högst 100 tecken";
  }

  $email = is_string($post["email"] ?? null) ? trim($post["email"]) : "";
  if ($email === "") {
    $errors[] = "Email måste fyllas i";
  } elseif (!str_contains($email, "@")) {
    $errors[] = "Emailen måste innehålla ett @";
  } elseif (mb_strlen($email) > 255) {
    $errors[] = "Emailen får vara högst 255 tecken";
  } else {
    $statement = $pdo->prepare("select id from users where email = ?");
    $statement->execute([$email]);

    if ($statement->fetch() !== false) {
      $errors[] = "Emailen används redan";
    }
  }

  $password = is_string($post["password"] ?? null) ? $post["password"] : "";
  if ($password === "") {
    $errors[] = "Lösenord måste fyllas i";
  } elseif (str_contains($password, "\0")) {
    $errors[] = "Lösenordet innehåller ett otillåtet tecken";
  }

  return [
    "errors" => $errors,
    "values" => ["first_name" => $first_name, "last_name" => $last_name, "email" => $email, "password" => $password],
  ];
}

$errors = [];
$values = ["first_name" => "", "last_name" => "", "email" => "", "password" => ""];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  ["errors" => $errors, "values" => $values] = validate_registration($_POST, $pdo);

  if (count($errors) === 0) {
    try {
      $statement = $pdo->prepare("insert into users (first_name, last_name, email, password_hash) values (?, ?, ?, ?)");
      $statement->execute([
        $values["first_name"],
        $values["last_name"],
        $values["email"],
        password_hash($values["password"], PASSWORD_DEFAULT),
      ]);

      login_user($pdo->lastInsertId());
      header("Location: /index.php");
      exit;
    } catch (PDOException $error) {
      if ($error->errorInfo[1] !== 1062) {
        throw $error;
      }

      $errors[] = "Emailen används redan";
    }
  }
}

page_start("Skapa konto", null);
?>
      <h1>Skapa konto</h1>
<?php error_list($errors); ?>
      <form class="form" method="post" action="/register.php">
        <div class="field">
          <label for="first_name">Förnamn</label>
          <input id="first_name" name="first_name" value="<?= htmlspecialchars($values["first_name"]) ?>" maxlength="100" required>
        </div>

        <div class="field">
          <label for="last_name">Efternamn</label>
          <input id="last_name" name="last_name" value="<?= htmlspecialchars($values["last_name"]) ?>" maxlength="100" required>
        </div>

        <div class="field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" value="<?= htmlspecialchars($values["email"]) ?>" maxlength="255" required>
        </div>

        <div class="field">
          <label for="password">Lösenord</label>
          <input id="password" name="password" type="password" required>
        </div>

        <div class="formActions">
          <button type="submit" class="button">Skapa konto</button>
        </div>
      </form>
<?php
page_end();