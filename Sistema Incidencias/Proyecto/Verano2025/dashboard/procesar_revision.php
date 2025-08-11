<?php
global $mysqli;
require('../sesion/conexion.php');

if (!isset($_POST['id'], $_POST['accion'])) {
    die("Faltan datos.");
}

$id = (int)$_POST['id'];
$accion = $_POST['accion'];

if (!in_array($accion, ['aceptar', 'rechazar', 'modificar'])) {
    die("Acción no válida.");
}

// Configura la firma del jefe si se acepta
$firma_jefe = null;
$estado = '';

switch ($accion) {
    case 'aceptar':
        session_start(); // si no lo tienes ya
        $firma_jefe = $_SESSION['usuario_id'] ?? null; // El usuario que aceptó // sin .png — lo agregas en generar_pdf.php
        $estado = 'ACEPTADO';
        break;
    case 'rechazar':
        $estado = 'RECHAZADO';
        break;
    case 'modificar':
        $estado = 'MODIFICAR';
        break;
}

// Construye la consulta
if ($accion === 'aceptar') {
    $query = "UPDATE incidencias SET estado = ?, firma_jefe = ? WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        die("Error en prepare: " . $mysqli->error);
    }
    $stmt->bind_param("ssi", $estado, $firma_jefe, $id);
} else {
    $query = "UPDATE incidencias SET estado = ? WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        die("Error en prepare: " . $mysqli->error);
    }
    $stmt->bind_param("si", $estado, $id);
}

if ($stmt->execute()) {
    // Redirigir al visor de PDF directamente
    header("Location: ver_pdf.php?id=" . $id);
    exit;
} else {
    echo "❌ Error al actualizar: " . $stmt->error;
}
