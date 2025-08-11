<?php
// inc_gestion_incidencias_jefe.php
global $mysqli;
session_start();
require '../sesion/auth.php';
require '../sesion/conexion.php';

// 1) Verificar sesión y rol
if (!isset($_SESSION['usuario_id'], $_SESSION['rol'])) {
    die("Acceso no autorizado. Por favor, inicia sesión.");
}
$rolesPermitidos = ['jefe_division', 'subdireccion', 'direccion'];
if (!in_array($_SESSION['rol'], $rolesPermitidos)) {
    die("❌ No tienes permiso para ver esta página.");
}

$userId       = (int) $_SESSION['usuario_id'];
$estadoFiltro = isset($_GET['estado']) && $_GET['estado'] !== ''
    ? strtoupper($_GET['estado'])
    : null;

$mysqli->set_charset("utf8");

// 2) Construir consulta: solo las incidencias creadas por este usuario
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
    LEFT JOIN divisiones d ON i.division_id = d.id
    WHERE i.usuario_id = ?
";
$params = [$userId];
$types  = 'i';

if ($estadoFiltro !== null) {
    $sql     .= " AND UPPER(i.estado) = ?";
    $types   .= 's';
    $params[] = $estadoFiltro;
}
$sql .= " ORDER BY i.fecha_creacion DESC";

// 4) Preparar y bindear
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    die("Error al preparar la consulta: " . $mysqli->error);
}
$bindNames   = [&$types];
foreach ($params as $i => &$p) {
    $bindNames[] = &$params[$i];
}
call_user_func_array([$stmt, 'bind_param'], $bindNames);

// 5) Ejecutar y obtener
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Incidencias</title>
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
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
        body{background:linear-gradient(135deg,#f5f7fa 0%,#e4ecfb 100%);min-height:100vh;padding:20px;}
        .dashboard-container{max-width:1200px;margin:0 auto;}
        .dashboard-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;padding:20px;background:white;border-radius:var(--border-radius);box-shadow:var(--box-shadow);}
        .dashboard-title{font-size:28px;font-weight:600;color:var(--dark-text);display:flex;align-items:center;gap:15px;}
        .dashboard-title i{color:var(--primary-color);background:rgba(67,97,238,0.1);width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;}
        .btn-back{display:inline-flex;align-items:center;gap:8px;padding:12px 20px;background:var(--primary-color);color:white;text-decoration:none;border-radius:var(--border-radius);font-weight:500;transition:var(--transition);box-shadow:0 4px 10px rgba(67,97,238,0.3);}
        .btn-back:hover{background:var(--primary-dark);transform:translateY(-3px);box-shadow:0 6px 15px rgba(67,97,238,0.4);}
        .filters-container{background:white;padding:20px;border-radius:var(--border-radius);box-shadow:var(--box-shadow);margin-bottom:30px;display:flex;flex-wrap:wrap;gap:15px;align-items:center;}
        .filter-label{font-weight:500;color:var(--dark-text);}
        .filter-select{padding:10px 15px;border:1px solid #e1e5eb;border-radius:8px;font-size:15px;background-color:#f8fafc;cursor:pointer;transition:var(--transition);}
        .filter-select:focus{outline:none;border-color:var(--primary-color);box-shadow:0 0 0 3px rgba(67,97,238,0.15);}
        .incidencias-container{display:grid;grid-template-columns:repeat(auto-fill,minmax(350px,1fr));gap:25px;}
        .incidencia-card{background:white;border-radius:var(--border-radius);box-shadow:var(--box-shadow);overflow:hidden;transition:var(--transition);position:relative;}
        .incidencia-card:hover{transform:translateY(-8px);box-shadow:0 12px 25px rgba(0,0,0,0.15);}
        .card-header{padding:15px 20px;display:flex;justify-content:space-between;align-items:center;background:#f8fafc;border-bottom:1px solid #eef2f7;}
        .usuario-info{display:flex;flex-direction:column;}
        .usuario-nombre{font-weight:600;font-size:18px;color:var(--dark-text);}
        .usuario-division{font-size:14px;color:var(--light-text);}
        .tipo-incidencia{padding:5px 12px;border-radius:20px;font-size:13px;font-weight:500;background:rgba(67,97,238,0.1);color:var(--primary-color);}
        .card-body{padding:20px;}
        .incidencia-motivo{color:var(--dark-text);line-height:1.6;margin-bottom:20px;font-size:15px;}
        .incidencia-meta{display:flex;justify-content:space-between;font-size:14px;color:var(--light-text);margin-bottom:15px;}
        .incidencia-fecha, .estado-container{display:flex;align-items:center;gap:5px;}
        .estado-badge{padding:5px 12px;border-radius:20px;font-size:13px;font-weight:500;}
        .estado-PENDIENTE{background:rgba(255,158,27,0.15);color:var(--warning-color);}
        .estado-ACEPTADO{background:rgba(46,204,113,0.15);color:var(--success-color);}
        .estado-RECHAZADO{background:rgba(231,76,60,0.15);color:var(--danger-color);}
        .estado-MODIFICAR{background:rgba(52,152,219,0.15);color:var(--info-color);}
        .card-footer{padding:15px 20px;display:flex;justify-content:flex-end;gap:10px;background:#f8fafc;border-top:1px solid #eef2f7;}
        .btn-action{padding:8px 16px;border-radius:8px;font-size:14px;font-weight:500;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:var(--transition);}
        .btn-review{background:var(--primary-color);color:white;}
        .btn-review:hover{background:var(--primary-dark);transform:translateY(-2px);}
        .btn-edit{background:var(--success-color);color:white;}
        .btn-edit:hover{background:#27ae60;transform:translateY(-2px);}
        .no-incidencias{background:white;border-radius:var(--border-radius);box-shadow:var(--box-shadow);padding:50px 30px;text-align:center;grid-column:1/-1;}
        .no-incidencias i{font-size:60px;color:#e1e5eb;margin-bottom:20px;}
        .no-incidencias h3{font-size:22px;color:var(--dark-text);margin-bottom:15px;}
        .no-incidencias p{color:var(--light-text);max-width:500px;margin:0 auto;line-height:1.6;}
        @media(max-width:768px){
            .dashboard-header{flex-direction:column;gap:20px;align-items:flex-start;}
            .incidencias-container{grid-template-columns:1fr;}
            .filters-container{flex-direction:column;align-items:flex-start;}
        }
    </style>
</head>
<body>
<div class="dashboard-container">

    <div class="dashboard-header">
        <h1 class="dashboard-title">
            <i class="fas fa-tasks"></i> Mis Incidencias
        </h1>
        <a href="index.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver al inicio
        </a>
    </div>

    <div class="filters-container">
        <form method="get" action="">
            <span class="filter-label">Filtrar por estado:</span>
            <select name="estado" id="estado" class="filter-select" onchange="this.form.submit()">
                <option value="" <?= $estadoFiltro===null?'selected':'' ?>>Todas</option>
                <option value="PENDIENTE" <?= $estadoFiltro==='PENDIENTE'?'selected':'' ?>>Pendiente</option>
                <option value="ACEPTADO"  <?= $estadoFiltro==='ACEPTADO' ?'selected':'' ?>>Aceptado</option>
                <option value="RECHAZADO" <?= $estadoFiltro==='RECHAZADO'?'selected':'' ?>>Rechazado</option>
                <option value="MODIFICAR" <?= $estadoFiltro==='MODIFICAR'?'selected':'' ?>>A modificar</option>
            </select>
        </form>
    </div>

    <div class="incidencias-container">
        <?php if ($result->num_rows===0): ?>
            <div class="no-incidencias">
                <i class="fas fa-inbox"></i>
                <h3>No se encontraron incidencias</h3>
                <p><?= $estadoFiltro
                        ? "No tienes incidencias en estado '{$estadoFiltro}'."
                        : "No tienes incidencias registradas." ?></p>
            </div>
        <?php else: ?>
            <?php while($row=$result->fetch_assoc()): ?>
                <div class="incidencia-card">
                    <div class="card-header">
                        <div class="usuario-info">
                            <div class="usuario-nombre"><?= htmlspecialchars($row['nombre_usuario'], ENT_QUOTES,'UTF-8') ?></div>
                            <div class="usuario-division"><?= htmlspecialchars($row['division']??'— Sin división —', ENT_QUOTES,'UTF-8') ?></div>
                        </div>
                        <div class="tipo-incidencia"><?= htmlspecialchars($row['tipo_incidencia'], ENT_QUOTES,'UTF-8') ?></div>
                    </div>

                    <div class="card-body">
                        <div class="incidencia-motivo">
                            <?= htmlspecialchars(mb_strimwidth($row['motivo'],0,200,'…'), ENT_QUOTES,'UTF-8') ?>
                        </div>
                        <div class="incidencia-meta">
                            <div class="incidencia-fecha">
                                <i class="far fa-calendar-alt"></i>
                                <span>Creada: <?= date('d/m/Y H:i',strtotime($row['fecha_creacion'])) ?></span>
                            </div>
                            <div class="estado-container">
                                <span>Estado:</span>
                                <div class="estado-badge estado-<?= $row['estado_texto'] ?>">
                                    <?= htmlspecialchars($row['estado_texto'], ENT_QUOTES,'UTF-8') ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="ver_pdf.php?id=<?= $row['id'] ?>" class="btn-action btn-review">
                            <i class="fas fa-eye"></i> Revisar
                        </a>
                        <?php if (strtoupper($row['estado_texto'])==='MODIFICAR'): ?>
                            <a href="modificar.php?id=<?= $row['id'] ?>" class="btn-action btn-edit">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
<?php
$stmt->close();
$mysqli->close();
?>


