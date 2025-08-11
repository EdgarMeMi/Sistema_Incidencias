<?php
// sesion/login.php
global $mysqli;
session_start();

// 1) Incluimos la conexión
require 'conexion.php';
if (!($mysqli instanceof mysqli)) {
    die("Error al inicializar la conexión.");
}

// 2) Procesamos el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario   = trim($_POST['usuario']   ?? '');
    $contrasena= $_POST['contrasena']     ?? '';

    // 3) Validaciones básicas
    if ($usuario === '' || $contrasena === '') {
        $msg = urlencode('Todos los campos son obligatorios.');
        header("Location: inicioSesion.html?mensaje={$msg}");
        exit;
    }

    // 4) Buscamos el usuario
    $sql = "SELECT id, nombre, rol, contrasena FROM usuarios WHERE clave_unica = ?";
    if (! $stmt = $mysqli->prepare($sql)) {
        die("Error en la consulta: " . $mysqli->error);
    }
    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // 5) Verificamos contraseña (ajusta si usas password_hash)
        if ($contrasena === $row['contrasena']) {
            // Éxito: guardamos sesión y redirigimos
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['rol']        = $row['rol'];
            $_SESSION['nombre']     = $row['nombre'];

            switch ($row['rol']) {
                case 'docente':
                    header('Location: ../maestro/index.php');
                    break;
                case 'administrativo':
                    header('Location: dashboard/administrativo.php');
                    break;
                case 'jefe_division':
                    header('Location: ../jefe/index.php');
                    break;
                case 'subdireccion':
                    header('Location: ../jefe/index.php');
                    break;
                case 'direccion':
                    header('Location: ../jefe/index.php');
                    break;
                default:
                    header('Location: inicioSesion.html?mensaje='
                        . urlencode('Rol no reconocido.'));
            }
            exit;
        } else {
            $msg = urlencode('Contraseña incorrecta.');
            header("Location: inicioSesion.html?mensaje={$msg}");
            exit;
        }
    } else {
        $msg = urlencode('Usuario no encontrado.');
        header("Location: inicioSesion.html?mensaje={$msg}");
        exit;
    }
}
