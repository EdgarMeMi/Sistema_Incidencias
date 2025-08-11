<?php
// maestro/auth.php
// Control de sesión y acceso

// 1) Si no hay sesión iniciada, arranca y configura
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// 2) Si no hay usuario logueado, redirige al login con mensaje
if (!isset($_SESSION['usuario_id'])) {
    $msg = urlencode('Por favor, inicia sesión para continuar.');
    header("Location: ../sesion/inicioSesion.html?mensaje={$msg}");
    exit;
}

// 3) Aquí podrías verificar roles si lo deseas
/*
$rolPermitido = ['docente'];
if (!in_array($_SESSION['rol'], $rolPermitido)) {
    exit('❌ No tienes permiso para acceder a esta sección.');
}
*/
