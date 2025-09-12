<?php
session_start();
require_once 'conexiones/conDB.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    die("Solo los usuarios pueden solicitar ser dueños.");
}

$id_usuario = $_SESSION['id'];
$msg = ''; //Creamos variable de msg, que esta va a tomar distintos valores a futuro dependiendo de lo q pase.

try {
    //Se fija si ya existe una peticion.
    $stmt = $pdo->prepare("SELECT * FROM verificacion WHERE id_usuario = ? AND estado = 'pendiente'");
    $stmt->execute([$id_usuario]);
    $solicitud = $stmt->fetch();

    if ($solicitud) {
        $msg = "Ya tenés una solicitud pendiente.";
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        //Sino existe, crea una peticion. El id obviamente depende del id del usuario, va a estar en estado pendiente y "NOW" permite que la fecha sea la misma que se envia en la vida real.
        $stmt2 = $pdo->prepare("INSERT INTO verificacion (id_usuario, estado, fecha) VALUES (?, 'pendiente', NOW())");
        $stmt2->execute([$id_usuario]);
        $msg = "Solicitud enviada. Esperá a que un admin la apruebe.";
    }

} catch (Throwable $e) {
    $msg = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitar ser dueño</title>
</head>
<body>
<h1>Solicitud para ser dueño</h1>
<?php if ($msg) echo "<p>$msg</p>"; ?>

<?php if (!$solicitud): ?>
<form method="post">
    <button type="submit">Solicitar</button>
</form>
<?php endif; ?>

<a href="index.php">Volver</a>
</body>
</html>
