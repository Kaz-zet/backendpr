<?php
$host = 'localhost';
$db   = 'canchappbd';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset"; //Data Source Name.
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, //Le dice a PDO que si ocurre un error, lance una excepción en vez de solo mostrar un warning, es mas seguro.
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //Permite que al hacer select se devuelva como array asociativo.
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>