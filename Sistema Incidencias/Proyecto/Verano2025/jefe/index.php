<?php
// index.php (unificado)

global $mysqli;
session_start();
require '../sesion/auth.php';
require '../sesion/conexion.php';  // Asegúrate de que aquí defines $mysqli

// 1) Verificar que haya un usuario logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../sesion/login.php');
    exit;
}

// 2) Verificar que el rol sea permitido
$rolesPermitidos = ['jefe_division','subdireccion','direccion'];
if (!in_array($_SESSION['rol'], $rolesPermitidos)) {
    exit('❌ No tienes permiso para acceder a esta página.');
}

// 3) Leer datos básicos de la sesión
$usuarioId    = (int) $_SESSION['usuario_id'];
$nombreUsuario = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
$rolBase       = $_SESSION['rol'];

// 4) Consultar la división del jefe desde la BD
$divisionId = null;
$stmt = $mysqli->prepare("SELECT division_id FROM usuarios WHERE id = ?");
$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$stmt->bind_result($divisionId);
$stmt->fetch();
$stmt->close();

// 5) Construir la etiqueta legible del rol
if ($rolBase === 'jefe_division') {
    // Si division_id es NULL o cero, pondremos un fallback
    $divisionNum = $divisionId > 0 ? $divisionId : '?';
    $rolUsuario  = "Jefe de División {$divisionNum}";
} else {
    // Mapear otros roles a un texto más amigable
    $map = [
        'subdireccion' => 'Subdirección',
        'direccion'    => 'Dirección'
    ];
    $rolUsuario = $map[$rolBase] ?? ucfirst($rolBase);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Sistema de Gestión de Incidencias</title>
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
            --warning-color: #ff9e1b;
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
            max-width: 1100px;
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
            display: flex;
            flex-direction: column;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header-title {
            font-size: 28px;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 15px;
            border-radius: 30px;
            backdrop-filter: blur(5px);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-size: 16px;
            font-weight: 500;
        }

        .user-role {
            font-size: 13px;
            opacity: 0.9;
        }

        .welcome-section {
            text-align: center;
            padding: 15px 0 25px;
            max-width: 700px;
            margin: 0 auto;
        }

        .welcome-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .welcome-subtitle {
            font-size: 18px;
            font-weight: 300;
            opacity: 0.9;
            line-height: 1.6;
        }

        .dashboard-body {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .action-card {
            background: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px 25px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
        }

        .action-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            transform: scaleX(0);
            transform-origin: left;
            transition: var(--transition);
        }

        .action-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
            border-color: rgba(67, 97, 238, 0.15);
        }

        .action-card:hover::before {
            transform: scaleX(1);
        }

        .card-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 30px;
            transition: var(--transition);
        }

        .action-card:hover .card-icon {
            transform: scale(1.1);
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 12px;
        }

        .card-description {
            color: var(--light-text);
            font-size: 14px;
            line-height: 1.6;
            margin-top: auto;
        }

        .card-primary .card-icon {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .card-primary::before {
            background: var(--primary-color);
        }

        .card-secondary .card-icon {
            background: rgba(46, 204, 113, 0.1);
            color: var(--secondary-color);
        }

        .card-secondary::before {
            background: var(--secondary-color);
        }

        .card-tertiary .card-icon {
            background: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
        }

        .card-tertiary::before {
            background: #9b59b6;
        }

        .card-warning .card-icon {
            background: rgba(255, 158, 27, 0.1);
            color: var(--warning-color);
        }

        .card-warning::before {
            background: var(--warning-color);
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

        /* Decoraciones */
        .header-decor {
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            top: -80px;
            right: -80px;
        }

        .header-decor:nth-child(2) {
            top: auto;
            bottom: -100px;
            right: 20px;
            width: 150px;
            height: 150px;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                padding: 25px 20px;
            }

            .header-top {
                flex-direction: column;
                gap: 15px;
            }

            .dashboard-body {
                padding: 25px 20px;
                grid-template-columns: 1fr;
            }

            .dashboard-footer {
                flex-direction: column;
                text-align: center;
                gap: 15px;
                padding: 20px;
            }

            .welcome-section {
                padding: 10px 0 20px;
            }

            .welcome-title {
                font-size: 26px;
            }

            .welcome-subtitle {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="header-decor"></div>
        <div class="header-decor"></div>

        <div class="header-top">
            <div class="header-title">Sistema de Gestión de Incidencias</div>
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

        <div class="welcome-section">
            <h2 class="welcome-title">Bienvenido al Panel de Control</h2>
            <p class="welcome-subtitle">Gestiona todas las incidencias de manera eficiente y organizada</p>
        </div>
    </div>

    <div class="dashboard-body">
        <div class="action-card card-primary" onclick="location.href='incidencia.php'">
            <div class="card-icon">
                <i class="fas fa-file-medical"></i>
            </div>
            <h3 class="card-title">Crear Incidencia</h3>
            <p class="card-description">Reporta un nuevo problema o solicitud para que nuestro equipo pueda resolverlo</p>
        </div>

        <div class="action-card card-tertiary" onclick="location.href='consulta_personal.php'">
            <div class="card-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <h3 class="card-title">Incidencias Personales</h3>
            <p class="card-description">Revisa todas las incidencias que has reportado y su estado actual</p>
        </div>

        <div class="action-card card-secondary" onclick="location.href='../dashboard/index.php'">
            <div class="card-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3 class="card-title">Consultar Incidencias</h3>
            <p class="card-description">Busca y revisa todas las incidencias reportadas en el sistema</p>
        </div>

        <div class="action-card card-warning" onclick="location.href='reporte.php'">
            <div class="card-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <h3 class="card-title">Reportes</h3>
            <p class="card-description">Genera reportes detallados y análisis de las incidencias registradas</p>
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
</body>
</html>