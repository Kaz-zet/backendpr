<?php
session_start();
$nombre = $_SESSION['nombre'] ?? null; //Si existe el nombre y rol que lo asigne, sino q no ponga nada. Asi la gente sin iniciar sesion puede entrar.
$rol = $_SESSION['rol'] ?? null;

$reservarmsj = ''; //Se inicia la variable.
$valoracionmsj= '';
$ver='';
$pedir="";
$calendario="";

if ($_SERVER['REQUEST_METHOD'] === 'POST') { //Esto hace q el login sea necesario unicamente cuando se activa algun boton o le pedis algo al servidor, si chusmeas no pasa nada.
    if (!$rol) {
        //Acá chusmea si está logueado, osea si tiene algún rol, sino lo manda al login.
        header("Location: login.php?redirect=" . urlencode($_SERVER['PHP_SELF']));
        exit;
    } 
    
    if (isset($_POST['reservar'])) {
        $reservarmsj = "¡Reserva realizada con éxito!";
    } elseif (isset($_POST['valorar'])) { //Adentro va el nombre del boton, entonces sería, si vos apretas el boton de reservar, te manda un mensaje y en este caso cada uno tiene color.
        $valoracionmsj = "¡Valoración enviada!";
    }
    elseif (isset($_POST['ver'])) { 
    }
    elseif (isset($_POST['pedir'])) {
        $pedir = "¡!";
    }
    elseif (isset($_POST['calendario'])) { 
        $calendario = "¡!";
    }
    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

    <h1>Bienvenido <?= htmlspecialchars($nombre ?? 'Guest') ?>!</h1> <!--Te dice por el nombre o si no tenés loguin "Guest" -->


    <?php if ($nombre): ?> <!--Revisa si el nombre existe. EN caso de existir significa que está logueado, sino es null y no está logueado.-->
        <p>Hola, <strong><?php echo htmlspecialchars($nombre); ?></strong> (rol: <?php echo $rol; ?>)</p> <!--Borrar la parte de rol, es para ver!-->
        <a href="logout.php">Cerrar sesión</a>
    <?php else: ?>
        <p>No has iniciado sesión.</p>
        <a href="login.php">Iniciar sesión</a>
    <?php endif; ?>


<!--Acá va el mensaje cuando reservas ponele-->
<?php if ($reservarmsj) echo "<p style='color:green;'>$reservarmsj</p>"; ?>
<?php if ($valoracionmsj) echo "<p style='color:yellow;'>$valoracionmsj</p>"; ?>
<?php if ($ver) header("Location: cancha.php")?>
<?php if ($pedir) header("Location: peticion.php")?>
<?php if ($calendario) header("Location: calendario.php")?>

<!--visible para todos-->
<form method="post">
    <button type="submit" name="ver">Ver canchas!</button>
</form>
<?php if ($rol === 'usuario'): ?>
<form method="post">
    <button type="submit" name="pedir">Pedir ser Dueño!</button>
</form>
    <p><a href="perfil.php">Ver mi perfil</a></p>
<?php endif; ?>

    <?php if ($rol === 'duenio'): ?>
<!-- Este if, cerrado con endif permite que si y unicamente si el usuario está logueado y tiene rol de dueño pueda ver ese mensaje.-->
<h2>Opciones de dueño</h2>
<ul>
    <li><a href="dueño.php">Agregar nueva cancha</a></li>
    <li><a href="gestion_reservas.php">Gestionar mis reservas</a></li>
</ul>
<?php endif; ?>
</body>
</html>