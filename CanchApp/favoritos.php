<?php
//PHP PARA AGREGAR LAS CANCHAS A FAVORITOS (NO ANDA)
session_start();
require_once 'conexiones/conDB.php';

if (!isset($_SESSION['id'])) {
    die("Tenés que iniciar sesión.");
}

$id_usuario = $_SESSION['id'];
$id_cancha = $_POST['id_cancha'] ?? null;
$accion = $_POST['accion'] ?? null;

if (!$id_cancha || !$accion) {
    die("Datos incompletos.");
}

try {
    if ($accion === 'agregar') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO favoritos (id_usuario, id_cancha) VALUES (?, ?)");
        $stmt->execute([$id_usuario, $id_cancha]);
        echo "Cancha agregada a favoritos.";
    } elseif ($accion === 'eliminar') {
        $stmt = $pdo->prepare("DELETE FROM favoritos WHERE id_usuario = ? AND id_cancha = ?");
        $stmt->execute([$id_usuario, $id_cancha]);
        echo "Cancha sacada de favoritos.";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage();
}
