<?php
// consulta_incidencias.php
// Consulta jerárquica + división según rol

global $mysqli;
session_start();
require '../sesion/auth.php';
require '../sesion/conexion.php';

// 1) Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    die("❌ Usuario no autenticado.");
}
$usuario_id = $_SESSION['usuario_id'];

// 2) Obtener rol y division_id del usuario
$stmt = $mysqli->prepare("SELECT rol, division_id FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$stmt->bind_result($rol, $mi_division);
$stmt->fetch();
$stmt->close();

// 3) Definir roles permitidos y qué roles puede ver cada uno
$mapa_visibilidad = [
    'jefe'           => ['docente'],
    'jefe_division'  => ['docente'],
    'subdireccion'   => ['jefe_division', 'docente'],
    'direccion'      => ['subdireccion', 'jefe_division', 'docente'],
    'administrativo' => []  // si quieres permitirle ver todo, pon aquí ['subdireccion','jefe_division','docente','jefe',...]
];

if (!isset($mapa_visibilidad[$rol])) {
    die("🚫 No tienes permiso para ver incidencias.");
}

$roles_visibles = $mapa_visibilidad[$rol];

// 4) Construir la consulta de usuarios que verá
$where_users = [];
$params     = [];
$types      = "";

// Filtrar por rol
if (!empty($roles_visibles)) {
    $in_roles = implode(',', array_fill(0, count($roles_visibles), '?'));
    $where_users[] = "u.rol IN ($in_roles)";
    $types .= str_repeat('s', count($roles_visibles));
    $params = array_merge($params, $roles_visibles);
} else {
    // Si no ve a nadie
    $where_users[] = "0=1";
}

// Para jefe y jefe_division, además filtrar por su propia división
if ($rol === 'jefe' || $rol === 'jefe_division') {
    $where_users[] = "u.division_id = ?";
    $types       .= "i";
    $params[]     = $mi_division;
    // excluir al propio jefe/jefe_division
    $where_users[] = "u.id <> ?";
    $types       .= "i";
    $params[]     = $usuario_id;
}

// 5) Obtener IDs de esos usuarios
$sql_users = "SELECT u.id FROM usuarios u WHERE " . implode(" AND ", $where_users);
$stmt = $mysqli->prepare($sql_users);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$user_ids = array_column($res->fetch_all(MYSQLI_ASSOC), 'id');
$stmt->close();

// 6) Si no hay usuarios a ver, vaciamos resultados
if (empty($user_ids)) {
    $inc_rows = [];
} else {
    // 7) Consultar incidencias pendientes de esos usuarios
    $pl = implode(',', array_fill(0, count($user_ids), '?'));
    $sql = "
        SELECT 
            i.id,
            i.usuario_id,
            i.tipo_incidencia,
            i.tipo_permiso,
            i.motivo,
            i.fecha_creacion,
            u.nombre AS nombre_usuario,
            d.nombre AS division
        FROM incidencias i
        JOIN usuarios u ON i.usuario_id = u.id
        LEFT JOIN divisiones d ON u.division_id = d.id
        WHERE i.estado = 'pendiente'
          AND i.usuario_id IN ($pl)
        ORDER BY i.fecha_creacion DESC
    ";
    $stmt = $mysqli->prepare($sql);
    // bind de todos los user_ids
    $stmt->bind_param(str_repeat('i', count($user_ids)), ...$user_ids);
    $stmt->execute();
    $inc_rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Incidencias Pendientes</title>
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary-color: #4361ee;
                --primary-dark: #3a56e4;
                --success-color: #2ecc71;
                --warning-color: #ff9e1b;
                --danger-color: #e74c3c;
                --info-color: #3498db;
                --light-bg: #f8f9fa;
                --dark-text: #212529;
                --light-text: #6c757d;
                --border-radius: 12px;
                --box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
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
                padding: 20px;
            }

            .dashboard-container {
                max-width: 1200px;
                margin: 0 auto;
            }

            .dashboard-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 30px;
                padding: 20px;
                background: white;
                border-radius: var(--border-radius);
                box-shadow: var(--box-shadow);
            }

            .dashboard-title {
                font-size: 28px;
                font-weight: 600;
                color: var(--dark-text);
                display: flex;
                align-items: center;
                gap: 15px;
            }

            .dashboard-title i {
                color: var(--primary-color);
                background: rgba(67, 97, 238, 0.1);
                width: 50px;
                height: 50px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .btn-back {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 12px 20px;
                background: var(--primary-color);
                color: white;
                text-decoration: none;
                border-radius: var(--border-radius);
                font-weight: 500;
                transition: var(--transition);
                box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
            }

            .btn-back:hover {
                background: var(--primary-dark);
                transform: translateY(-3px);
                box-shadow: 0 6px 15px rgba(67, 97, 238, 0.4);
            }

            .incidencias-container {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 25px;
            }

            .incidencia-card {
                background: white;
                border-radius: var(--border-radius);
                box-shadow: var(--box-shadow);
                overflow: hidden;
                transition: var(--transition);
                position: relative;
            }

            .incidencia-card:hover {
                transform: translateY(-8px);
                box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
            }

            .card-header {
                padding: 15px 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #f8fafc;
                border-bottom: 1px solid #eef2f7;
            }

            .usuario-info {
                display: flex;
                flex-direction: column;
            }

            .usuario-nombre {
                font-weight: 600;
                font-size: 18px;
                color: var(--dark-text);
            }

            .usuario-division {
                font-size: 14px;
                color: var(--light-text);
            }

            .tipo-incidencia {
                padding: 5px 12px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 500;
                background: rgba(67, 97, 238, 0.1);
                color: var(--primary-color);
            }

            .card-body {
                padding: 20px;
            }

            .incidencia-motivo {
                color: var(--dark-text);
                line-height: 1.6;
                margin-bottom: 20px;
                font-size: 15px;
            }

            .incidencia-meta {
                display: flex;
                justify-content: space-between;
                font-size: 14px;
                color: var(--light-text);
                margin-bottom: 15px;
            }

            .incidencia-fecha {
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .estado-container {
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .estado-badge {
                padding: 5px 12px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 500;
            }

            .estado-PENDIENTE {
                background: rgba(255, 158, 27, 0.15);
                color: var(--warning-color);
            }

            .card-footer {
                padding: 15px 20px;
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                background: #f8fafc;
                border-top: 1px solid #eef2f7;
            }

            .btn-action {
                padding: 8px 16px;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 500;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: var(--transition);
            }

            .btn-review {
                background: var(--primary-color);
                color: white;
            }

            .btn-review:hover {
                background: var(--primary-dark);
                transform: translateY(-2px);
            }

            .no-incidencias {
                background: white;
                border-radius: var(--border-radius);
                box-shadow: var(--box-shadow);
                padding: 50px 30px;
                text-align: center;
                grid-column: 1 / -1;
            }

            .no-incidencias i {
                font-size: 60px;
                color: #e1e5eb;
                margin-bottom: 20px;
            }

            .no-incidencias h3 {
                font-size: 22px;
                color: var(--dark-text);
                margin-bottom: 15px;
            }

            .no-incidencias p {
                color: var(--light-text);
                max-width: 500px;
                margin: 0 auto;
                line-height: 1.6;
            }

            @media (max-width: 768px) {
                .dashboard-header {
                    flex-direction: column;
                    gap: 20px;
                    align-items: flex-start;
                }

                .incidencias-container {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">
                <i class="fas fa-tasks"></i> Incidencias Pendientes
            </h1>
            <a href="../jefe/index.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="incidencias-container">
            <?php if (empty($inc_rows)): ?>
                <div class="no-incidencias">
                    <i class="fas fa-inbox"></i>
                    <h3>No hay incidencias para revisar</h3>
                    <p>Actualmente no existen incidencias pendientes bajo tu supervisión.</p>
                </div>
            <?php else: ?>
                <?php foreach ($inc_rows as $row): ?>
                    <div class="incidencia-card">
                        <div class="card-header">
                            <div class="usuario-info">
                                <div class="usuario-nombre">
                                    <?= htmlspecialchars($row['nombre_usuario'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="usuario-division">
                                    <?= htmlspecialchars($row['division'] ?? '— Sin división —', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                            <div class="tipo-incidencia">
                                <?= htmlspecialchars($row['tipo_incidencia'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="incidencia-motivo">
                                <strong><?= ucfirst(strtolower($row['tipo_permiso'])) ?>:</strong>
                                <?= htmlspecialchars($row['motivo'], ENT_QUOTES, 'UTF-8') ?>
                            </div>

                            <div class="incidencia-meta">
                                <div class="incidencia-fecha">
                                    <i class="far fa-calendar-alt"></i>
                                    <span>Creada: <?= date('d/m/Y H:i', strtotime($row['fecha_creacion'])) ?></span>
                                </div>

                                <div class="estado-container">
                                    <span>Estado:</span>
                                    <div class="estado-badge estado-PENDIENTE">
                                        PENDIENTE
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <a href="ver_pdf.php?id=<?= $row['id'] ?>" class="btn-action btn-review">
                                <i class="fas fa-eye"></i> Revisar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    </body>
    </html>
<?php
// Cerrar conexión si está abierta
if (isset($mysqli) && $mysqli->ping()) {
    $mysqli->close();
}
?>