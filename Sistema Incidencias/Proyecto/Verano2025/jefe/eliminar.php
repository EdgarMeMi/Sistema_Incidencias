<?php
// eliminar_incidencia.php
session_start();
require '../sesion/auth.php';
require('../sesion/conexion.php');

// 1) Verificar sesión y rol
if (!isset($_SESSION['usuario_id'], $_SESSION['rol'])) {
    die("Acceso no autorizado. Por favor, inicia sesión.");
}
$rolesPermitidos = ['jefe_division', 'subdireccion', 'direccion'];
if (!in_array($_SESSION['rol'], $rolesPermitidos)) {
    die("❌ No tienes permiso para eliminar incidencias.");
}

// 2) Obtener y validar ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die("ID inválido.");
}
$id = (int) $_GET['id'];

// 3) Comprobar que la incidencia existe y su estado es RECHAZADO
$stmt = $mysqli->prepare("
    SELECT estado 
    FROM incidencias 
    WHERE id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($estado);
if (!$stmt->fetch()) {
    $stmt->close();
    die("Incidencia no encontrada.");
}
$stmt->close();

if (strtoupper($estado) !== 'RECHAZADO') {
    die("Sólo se pueden eliminar incidencias rechazadas.");
}

// 4) Ejecutar DELETE
$del = $mysqli->prepare("DELETE FROM incidencias WHERE id = ?");
$del->bind_param('i', $id);
if (!$del->execute()) {
    $del->close();
    die("Error al eliminar: " . $mysqli->error);
}
$del->close();

// 5) Redirigir de vuelta al reporte
header('Location: reporte.php?deleted=1');
exit;
?>

