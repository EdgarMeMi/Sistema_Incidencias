<?php
// sesion/conexion.php
$host     = '127.0.0.1';  // o 'localhost'
$port     = 3307;
$user     = 'root';
$password = '';           // si no tienes contraseña, déjalo vacío
$dbname   = 'base_incidencias';

// Crear conexión
$mysqli = new mysqli($host, $user, $password, $dbname, $port);
if ($mysqli->connect_errno) {
    die("Error de conexión ({$mysqli->connect_errno}): " . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');