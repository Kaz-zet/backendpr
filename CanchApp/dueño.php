<?php
session_start();
require_once 'conexiones/conDB.php';

//Solo dueños.
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'duenio') {
    die("Error: solo los dueños pueden crear canchas.");
}

$id_duenio = $_SESSION['id']; //Agarramos la ID del dueño logueado.
$msg = '';
$easterEggTrigger = null; //Jiji

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $lugar  = trim($_POST['lugar'] ?? '');
    $bio  = trim($_POST['bio'] ?? '');
    $foto = null;
    $uploadError = '';

    //-----------------------------------------------------------FOTO (NO ANDA)--------------------------------------------

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['foto']['type'], $allowedTypes)) {
            $uploadError = 'Solo se permiten archivos JPG, JPEG y PNG.';
        } elseif ($_FILES['foto']['size'] > $maxSize) {
            $uploadError = 'El archivo es muy grande. Máximo 5MB.';
        } else {
            // Create uploads directory if it doesn't exist
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $filename = 'cancha_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $uploadPath = 'Seguridad/' . $filename;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadPath)) {
                $foto = $filename; // This should now work
            } else {
                $uploadError = 'Error al subir la imagen.';
            }
        }
    }

    //---------------------------------------------------------------------------------------------------------------------------------

    if ($nombre === '' || $lugar === '' || $bio === '') {
    $msg = 'Completa todos los campos.';
    } elseif (isset($uploadError) && $uploadError) {  
        $msg = $uploadError;
    } else {
        try {
            //Revisa si la cancha ya existe, unicamente nombre.
            $stmt = $pdo->prepare('SELECT 1 FROM cancha WHERE nombre = ?');
            $stmt->execute([$nombre]);

            if ($stmt->fetch()) {
                $msg = 'Ese nombre ya está registrado :(.';
            } else {
                //Para crear cancha.
                $stmt2 = $pdo->prepare('INSERT INTO cancha (nombre, lugar, bio, foto, id_duenio) VALUES (?, ?, ?, ?, ?)');
                $stmt2->execute([$nombre, $lugar, $bio, $foto, $id_duenio]);
                $msg = 'Cancha creada!!.';

                // EASTER EGGS JIJIJ
                $easterEggs = [
                    'Vegetta|777' => [
                        'color' => '#6a0dad',
                        'textColor' => 'white',
                        'mensaje' => '¡Vegetta777!'
                    ],
                    'Pikachu|025' => [
                        'color' => 'yellow',
                        'textColor' => 'black',
                        'mensaje' => '¡Pikachuuuuuuuuuuuuuuuuuuuu!'
                    ],
                    'Mario|Luigi' => [
                        'color' => 'red',
                        'textColor' => 'white',
                        'mensaje' => '¡IAJUUU!'
                    ]
                ];

                // Revisa si el easter egg coincide y si si se activa.
                $key = $nombre . '|' . $lugar;
                if (isset($easterEggs[$key])) {
                    $easterEggTrigger = $easterEggs[$key];
                }
            }
        } catch (Throwable $e) {
            $msg = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Crear Cancha</title>
</head>
<body>
    <h1>Crear Cancha</h1>

    <?php if (!empty($msg)): ?>
        <p><?= htmlspecialchars($msg) ?></p> <!--mensaje de cancha creada-->
    <?php endif; ?>

    <?php if ($easterEggTrigger): ?>
        <script>
            document.body.style.backgroundColor = '<?= $easterEggTrigger['color'] ?>';  // EASTER EGGS
            document.body.style.color = '<?= $easterEggTrigger['textColor'] ?>';
            alert('<?= $easterEggTrigger['mensaje'] ?>');
        </script>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="nombre" placeholder="Nombre" required><br>
        <input type="text" name="lugar" placeholder="Dirección" required><br>
        <input type="text" name="bio" placeholder="Biografía" required><br>
        <label>Foto de la cancha:</label><br>
        <input type="file" name="foto" accept="image/*"><br><br>
        <button type="submit">Crear Cancha</button>
    </form>

    <p><a href="index.php">Volver</a></p>
</body>
</html>
