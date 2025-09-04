<?php
session_start();
require_once 'conexiones/conDB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrarcancha'])) {
    $id_borrar = (int)$_POST['borrarcancha'];

    // Borrar la cancha SOLO si pertenece a ese dueño
    $stmt = $pdo->prepare("DELETE FROM cancha WHERE id_cancha = ?");
    $stmt->execute([$id_borrar]);

    // Refrescar la página para que desaparezca de la lista
    header("Location: cancha.php");
    exit;
}
?>
