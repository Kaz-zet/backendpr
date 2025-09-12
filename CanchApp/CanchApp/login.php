<?php
session_start();
require_once 'conexiones/conDB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if ($nombre === '' || $email === '' || $contrasena === '') {
        $msg = 'Completá nombre, email y contraseña.';
    } else {
        try {
          //STMT significa statement y es una consulta preparada en PDO y permite ejecurtar QUERY/consultas/etc de forma segura.
            //Para admin
            $stmt = $pdo->prepare("SELECT id_admin AS id, nombre, contrasena 
                                   FROM admin WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(); //En STMT se guarda lo que consultamos y fetch permite que lo podamos ver ordenadamente, y el $user permite que se vean los datos de la fila en concreto.

            $rol = null; //Se crea rol, que sirve para diferenciar los roles al iniciar sesion.

            if ($user && $user['contrasena'] === $contrasena && mb_strtolower($user['nombre']) === mb_strtolower($nombre)) { //No importa si es miniscula o mayuscula
                $rol = "admin";
            } else {

                // Para dueño
                $stmt = $pdo->prepare("SELECT id_duenio AS id, nombre, contrasena
                                       FROM duenio WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && $user['contrasena'] === $contrasena && mb_strtolower($user['nombre']) === mb_strtolower($nombre)) {
                    $rol = "duenio";
                } else {

                    // Usuario
                    $stmt = $pdo->prepare("SELECT id_usuario AS id, nombre, contrasena 
                                           FROM usuario WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    if ($user && $user['contrasena'] === $contrasena && mb_strtolower($user['nombre']) === mb_strtolower($nombre)) {
                        $rol = "usuario";
                    }
                }
            }

            if ($rol) {
                // Guardar en sesión
                $_SESSION['id'] = $user['id'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['rol'] = $rol;

                // Redirigir según rol
                if ($rol === "admin") {
                    header("Location: admin.php");
                } elseif ($rol === "duenio") {
                    header("Location: index.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $msg = "Credenciales incorrectas.";
            }

        } catch (Throwable $e) {
            $msg = "Error del servidor: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Login</title></head>
<body>
  <h1>Login</h1>
  <?php if (!empty($msg)) echo "<p>$msg</p>"; ?>
  <form method="post">
    <input type="text" name="nombre" placeholder="Nombre" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="contrasena" placeholder="Contraseña" required><br>
    <button type="submit">Ingresar</button>
  </form>
  <p><a href="registrarse.php">Crear cuenta</a></p>
</body>
</html>