<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="css/registrarse.css">
</head>
<body>
    <form method="post" action="index.php">
        <h2>Iniciar sesión</h2>

        <div class="contenedor input">
            <input type="text" name="name" placeholder="Nombre" required>
        </div>

        <div class="contenedor input">
            <input type="email" name="email" placeholder="Email" required>
        </div>

        <div class="contenedor input">
            <input type="password" name="password" placeholder="Contraseña" required>
        </div>

        <input type="submit" name="login" value="Entrar" class="btn">

    <a href="registrarse.php" class="yatengo">Registrarme</a>
    </form>
    <?php if (isset($mensaje)) { echo "<p class='error'>$mensaje</p>"; } ?>
</body>
</html>