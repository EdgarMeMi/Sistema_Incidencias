<?php
// reportes_incidencias.php

session_start();
require '../sesion/auth.php';
require '../sesion/conexion.php';

// 1) Verificar sesión y rol
if (!isset($_SESSION['usuario_id'], $_SESSION['rol'])) {
    die("Acceso no autorizado. Por favor, inicia sesión.");
}
$rolesPermitidos = ['jefe_division', 'subdireccion', 'direccion'];
if (!in_array($_SESSION['rol'], $rolesPermitidos)) {
    die("❌ No tienes permiso para ver este reporte.");
}

// 2) Preparar filtro para jefe de división
$filtroDivision = '';
if ($_SESSION['rol'] === 'jefe_division') {
    // Obtenemos la división asignada al usuario
    $stmt = $mysqli->prepare("SELECT division_id FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['usuario_id']);
    $stmt->execute();
    $stmt->bind_result($divisionUsuario);
    $stmt->fetch();
    $stmt->close();

    // Aplicamos filtro para mostrar solo incidencias de su división
    $filtroDivision = "WHERE u.division_id = " . intval($divisionUsuario);
}

$mysqli->set_charset("utf8");

// 3) Consultar incidencias (se muestra la división del usuario)
$sql = "
    SELECT
        i.id,
        i.tipo_incidencia,
        i.motivo,
        u.nombre        AS nombre_usuario,
        d.nombre        AS division,
        i.fecha_creacion,
        i.estado        AS estado_texto
    FROM incidencias i
    JOIN usuarios    u ON i.usuario_id   = u.id
    LEFT JOIN divisiones d ON u.division_id = d.id
    $filtroDivision
    ORDER BY i.fecha_creacion DESC
";
$result = $mysqli->query($sql);
if (!$result) {
    die("Error en la consulta: " . $mysqli->error);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Incidencias</title>
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
            --danger-color: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --light-text: #6c757d;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4ecfb 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            background: white;
            color: var(--primary-color);
        }
        .dashboard-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: 600;
            color: black;
        }
        .dashboard-title i {
            font-size: 28px;
            color: var(--primary-color);
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
            box-shadow: 0 4px 10px rgba(67,97,238,0.3);
        }
        .btn-back:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(67,97,238,0.4);
        }
        .report-body {
            padding: 30px;
        }
        .table-container {
            overflow-x: auto;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            background: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        th, td {
            padding: 15px;
            text-align: left;
            font-size: 14px;
        }
        thead {
            background: var(--primary-dark);
            color: white;
        }
        th {
            position: sticky;
            top: 0;
        }
        tbody tr {
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: var(--transition);
        }
        tbody tr:hover {
            background: rgba(67,97,238,0.05);
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-pendiente { background: rgba(255,158,27,0.15); color: var(--warning-color); }
        .status-aprobado  { background: rgba(46,204,113,0.15); color: var(--secondary-color); }
        .status-rechazado { background: rgba(231,76,60,0.15);  color: var(--danger-color); }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="dashboard-title">
            <i class="fas fa-file-contract"></i>
            <span>Reporte de Incidencias</span>
        </div>
        <a href="index.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
    <div class="report-body">
        <?php if ($result->num_rows === 0): ?>
            <div class="no-data" style="text-align:center; padding:40px; color:var(--light-text);">
                <i class="fas fa-inbox" style="font-size:48px; margin-bottom:15px;"></i>
                <p>No hay incidencias registradas en el sistema.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>División</th>
                        <th>Tipo</th>
                        <th>Motivo</th>
                        <th>Creación</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        $estado = strtoupper($row['estado_texto']);
                        $cls = $estado === 'PENDIENTE'  ? 'status-pendiente'
                            : ($estado === 'APROBADO'  ? 'status-aprobado'
                                : ($estado === 'RECHAZADO' ? 'status-rechazado' : ''));
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['nombre_usuario'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['division'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['tipo_incidencia'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($row['motivo'],0,50,'…'), ENT_QUOTES,'UTF-8') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['fecha_creacion'])) ?></td>
                            <td><span class="status-badge <?= $cls ?>"><?= ucfirst(strtolower($row['estado_texto'])) ?></span></td>
                            <td>
                                <?php if ($estado === 'RECHAZADO'): ?>
                                    <button class="btn-delete" data-id="<?= $row['id'] ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                <?php else: ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            Swal.fire({
                title: '¿Eliminar incidencia?',
                html: `Esta acción eliminará permanentemente la incidencia #${id}.<br>¿Deseas continuar?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--danger-color)',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash-alt"></i> Eliminar',
                cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                reverseButtons: true
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = `eliminar.php?id=${id}`;
                }
            });
        });
    });
</script>
<?php $mysqli->close(); ?>
