<?php
require_once 'conexiones/conDB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') { //Pedimos estos 3 datos
  $nombre = trim($_POST['nombre'] ?? '');
  $email  = trim($_POST['email'] ?? '');
  $contrasena  = $_POST['contrasena'] ?? '';

  if ($nombre === '' || $email === '' || $contrasena === '') { //Si están vacios.
    $msg = 'Completa todos los campos.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { //Si el email no es valido.
    $msg = 'Email inválido.';
  } elseif (strlen($contrasena) < 3) { //Si la contraseña tiene menos carcateres que losq se piden.
    $msg = 'La contraseña debe contener al menos 3 caracteres.';
  } else {
    try {
      //
      // ¿Existe el email? 
      $stmt = $pdo->prepare('SELECT 1 FROM usuario WHERE email = ?'); //Se pide si existe al menos 1 usuario que tenga en la fila email el mismo email que se intenta registrar.
      $stmt->execute([$email]);
      if ($stmt->fetch()) {
        $msg = 'Ese email ya está registrado :(.';
      } else {
        $stmt2 = $pdo->prepare('INSERT INTO usuario (nombre, email, contrasena) VALUES (?, ?, ?)');
        $stmt2->execute([$nombre, $email, $contrasena]);
        $msg = 'Registro exitoso!! Ya podés iniciar sesión.';
      }
    } catch (Throwable $e) {
      $msg = 'Error: ' . $e->getMessage();
    }
  }
}
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Registro</title></head>
<body>
  <h1>Registro</h1>
  <?php if (!empty($msg)) echo "<p>$msg</p>"; ?>
  <form method="post">
    <input type="text" name="nombre" placeholder="Nombre" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="contrasena" placeholder="Contraseña" required><br>
    <button type="submit">Registrarme</button>
  </form>
  <p><a href="login.php">Ir a Login</a></p>
</body>
</html>