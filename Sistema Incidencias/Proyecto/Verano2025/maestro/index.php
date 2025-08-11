<?php
// index.php
session_start();
require '../sesion/auth.php';

// 1) Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../sesion/login.php');
    exit;
}

// 2) Verificar que el rol sea el permitido
$rolPermitido = ['docente'];
if (!in_array($_SESSION['rol'], $rolPermitido)) {
    exit('❌ No tienes permiso para acceder a esta página.');
}

// 3) Preparar datos para mostrar en la plantilla HTML
$nombreUsuario = !empty($_SESSION['nombre'])
    ? htmlspecialchars($_SESSION['nombre'], ENT_QUOTES, 'UTF-8')
    : 'Usuario';
$rolUsuario = !empty($_SESSION['rol'])
    ? htmlspecialchars($_SESSION['rol'], ENT_QUOTES, 'UTF-8')
    : '';

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Sistema de Incidencias</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3a56e4;
            --secondary-color: #2ecc71;
            --accent-color: #f72585;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --light-text: #6c757d;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4ecfb 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .dashboard-container {
            width: 100%;
            max-width: 900px;
            background: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            position: relative;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 30px 40px;
            position: relative;
            overflow: hidden;
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .header-bg {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 40%;
            background: rgba(255, 255, 255, 0.1);
            clip-path: polygon(100% 0, 100% 100%, 0 100%);
        }

        .header-title {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .welcome-message {
            font-size: 18px;
            font-weight: 300;
            opacity: 0.9;
            margin-bottom: 25px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .user-role {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .dashboard-body {
            padding: 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .card {
            background: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-color);
            transform: scaleX(0);
            transform-origin: left;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
            border-color: rgba(67, 97, 238, 0.2);
        }

        .card:hover::before {
            transform: scaleX(1);
        }

        .card-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(67, 97, 238, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: var(--primary-color);
            transition: var(--transition);
        }

        .card:hover .card-icon {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }

        .card-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 15px;
        }

        .card-description {
            color: var(--light-text);
            font-size: 15px;
            line-height: 1.6;
        }

        .card-primary .card-icon {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .card-secondary .card-icon {
            background: rgba(46, 204, 113, 0.1);
            color: var(--secondary-color);
        }

        .card-primary:hover .card-icon {
            background: var(--primary-color);
        }

        .card-secondary:hover .card-icon {
            background: var(--secondary-color);
        }

        .dashboard-footer {
            background: #f8fafc;
            padding: 25px 40px;
            border-top: 1px solid #eef2f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-text {
            color: var(--light-text);
            font-size: 14px;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #e74c3c;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            padding: 10px 20px;
            border-radius: 8px;
            background: rgba(231, 76, 60, 0.08);
        }

        .logout-btn:hover {
            background: rgba(231, 76, 60, 0.15);
            transform: translateY(-2px);
        }

        .logout-btn i {
            transition: var(--transition);
        }

        .logout-btn:hover i {
            transform: translateX(-3px);
        }

        @media (max-width: 768px) {
            .dashboard-header {
                padding: 25px;
            }

            .dashboard-body {
                padding: 25px;
                grid-template-columns: 1fr;
            }

            .dashboard-footer {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="header-bg"></div>
        <div class="header-content">
            <h1 class="header-title">Sistema de Gestión de Incidencias</h1>
            <p class="welcome-message">Gestiona todas las incidencias de manera eficiente y organizada</p>

            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <div class="user-name"><?= $nombreUsuario ?></div>
                    <div class="user-role"><?= strtoupper($rolUsuario) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-body">
        <div class="card card-primary" id="btnCrearIncidencia">
            <div class="card-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <h3 class="card-title">Crear Incidencia</h3>
            <p class="card-description">Reporta un nuevo problema o solicitud para que nuestro equipo pueda resolverlo</p>
        </div>

        <div class="card card-secondary" id="btnConsultarIncidencias">
            <div class="card-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3 class="card-title">Consultar Incidencias</h3>
            <p class="card-description">Revisa el estado de tus incidencias anteriores y su progreso actual</p>
        </div>
    </div>

    <div class="dashboard-footer">
        <p class="footer-text">Sistema desarrollado para la gestión eficiente de incidencias</p>
        <a href="../sesion/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            Cerrar Sesión
        </a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('btnCrearIncidencia')
            .addEventListener('click', () => window.location.href = 'incidencia.php');

        document.getElementById('btnConsultarIncidencias')
            .addEventListener('click', () => window.location.href = 'consulta.php');
    });
</script>
</body>
</html>
