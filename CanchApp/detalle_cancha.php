<?php
session_start();
require_once 'conexiones/conDB.php';

$id_cancha = $_GET['id'] ?? null;
$id_usuario = $_SESSION['id'] ?? null;

if (!$id_cancha) {
    header("Location: cancha.php");
    exit;
}

//Sacamos los datos de las canchas
try {
    $stmt = $pdo->prepare("
        SELECT c.*, d.nombre as duenio_nombre 
        FROM cancha c 
        LEFT JOIN duenio d ON c.id_duenio = d.id_duenio 
        WHERE c.id_cancha = ?
    ");
    $stmt->execute([$id_cancha]);
    $cancha = $stmt->fetch();
    
    if (!$cancha) {
        header("Location: cancha.php");
        exit;
    }
    
    // Reservas. CURDATE() es current date, osea el día actual, unicamente con eso lo podemos sabes sin complicarnos.
    $stmt2 = $pdo->prepare(" 
        SELECT hora_inicio, hora_final 
        FROM reserva 
        WHERE id_cancha = ? AND fecha = CURDATE()
        ORDER BY hora_inicio
    ");
    $stmt2->execute([$id_cancha]);
    $reservas = $stmt2->fetchAll();
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

//Aca creamos el array de horarios.
//spirntf y %02d es unicamente para q se vea lindo. De forma que generamos que en cualquiera de los arrays de 9 a 21, estos inicien en el horario y finalicen una hora desp exactamente.
//Donde inicio es una variable, y sabiendo que va a durar una hora, fin va a ser inicio mas la hora que toma la clase.
$horarios = [];
for ($hora = 9; $hora <= 21; $hora++) {
    $inicio = sprintf("%02d:00", $hora);
    $fin = sprintf("%02d:00", $hora + 1);
    
    //Chusmea que el espacio no esté ocupado, por ende, esto se fija que ponele...
    //Si vos reservas a las 13 horas, esto dice, bueno, vos vas a reservar a las 13:00
    //Pero el sistema no entiende el 13:00 y quiere segundos, por ende agregamos esos segundos para que matcheen.
    //Quedaría if ($reserva['hora_inicio'] === $inicio . ':00')), if 13:00:00 es igual a 13:00:00, que se ponga en ocupado. Sino serían desiguales.
    //MySQL siempre guarda todo en horas minutos ys egundos.
    $ocupado = false;
    foreach ($reservas as $reserva) {
        if ($reserva['hora_inicio'] === $inicio . ':00') {
            $ocupado = true;
            break;
        }
    }
    
    $horarios[] = [
        'inicio' => $inicio,
        'fin' => $fin,
        'ocupado' => $ocupado
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($cancha['nombre']) ?> - Detalles</title><!--CSS PARA Q SE VEA LINDOA HORA, NO ES FINAL!-->
    <style>
        .horarios { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; } 
        .slot { padding: 10px; text-align: center; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="cancha-header">
        <h1><?= htmlspecialchars($cancha['nombre']) ?></h1>
        <?php if ($cancha['foto']): ?>
        <?php if (!empty($cancha['foto'])): ?>
        <img src="uploads/<?= htmlspecialchars($cancha['foto']) ?>" width="400" height="250" style="border: 1px solid #ccc;">
    <?php else: ?>
        <div style="width: 400px; height: 250px; background: #e9ecef; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc;">
            <span>Sin foto disponible</span>
        </div>
    <?php endif; ?>
        </div>
    <?php endif; ?>
        <p><strong>Ubicación:</strong> <?= htmlspecialchars($cancha['lugar']) ?></p>
        <p><strong>Dueño:</strong> <?= htmlspecialchars($cancha['duenio_nombre']) ?></p>
        <p><strong>Descripción:</strong> <?= htmlspecialchars($cancha['bio']) ?> </p>
        
        <!-- Placeholder para la foto-->
        <div style="width: 300px; height: 200px; background: #e9ecef; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
            <span style="color: #6c757d;">Foto de la cancha</span>
        </div>
    </div>

    <h2>Horarios Disponibles - Hoy</h2>
    <div class="horarios">
        <?php foreach ($horarios as $slot): ?>
            <div class="slot <?= $slot['ocupado'] ? 'ocupado' : 'libre' ?>">
                <strong><?= $slot['inicio'] ?> - <?= $slot['fin'] ?></strong><br>
                <?php if ($slot['ocupado']): ?>
                     Ocupado
                <?php else: ?>
                     Libre
                    <?php if ($id_usuario): ?>
                        <br><button class="reservar-btn" onclick="reservar('<?= $slot['inicio'] ?>')">Reservar</button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$id_usuario): ?>
        <p style="margin-top: 20px; padding: 10px; background: #fff3cd; border-radius: 5px;">
            <a href="login.php">Inicia sesión</a> para poder reservar
        </p>
    <?php endif; ?>

    <p style="margin-top: 20px;">
        <a href="cancha.php">
            Volver a Canchas
        </a>
    </p>

    <script> //POPUP para que confirmes reservar!
        function reservar(hora) {
            if (confirm('¿Confirmar reserva para ' + hora + ' - ' + (parseInt(hora) + 1) + ':00?')) {
                alert('¡Reserva confirmada para ' + hora + '! ');
                
                location.reload(); 
            }
        }
    </script>
</body>
</html>