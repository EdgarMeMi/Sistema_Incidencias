<?php
// editar_incidencia.php
global $mysqli;
session_start();
require '../sesion/auth.php';
require '../sesion/conexion.php';
$mysqli->set_charset('utf8mb4');

// Verificar sesión y rol (solo docentes)
if (!isset($_SESSION['usuario_id'], $_SESSION['rol'])) {
    die('Acceso no autorizado.');
}

$userId = (int)$_SESSION['usuario_id'];

// Obtener ID de la incidencia
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
if ($id <= 0) {
    die('ID de incidencia inválido.');
}

// Errores y éxito
$errors = [];
$success = '';

// Procesar POST: solo motivo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo = mb_strtoupper(trim($_POST['motivo'] ?? ''), 'UTF-8');
    if (empty($motivo)) {
        $errors[] = 'La exposición de motivos no puede estar vacía.';
    }

    // Verificar que el usuario es el autor
    if (empty($errors)) {
        $chk = $mysqli->prepare('SELECT usuario_id FROM incidencias WHERE id = ?');
        $chk->bind_param('i', $id);
        $chk->execute();
        $chk->bind_result($autor);
        if (!($chk->fetch() && $autor === $userId)) {
            die('❌ No tienes permiso para editar esta incidencia.');
        }
        $chk->close();
    }

    // Actualizar motivo y reset estado
    if (empty($errors)) {
        $upd = $mysqli->prepare("UPDATE incidencias SET motivo = ?, estado = 'PENDIENTE' WHERE id = ?");
        $upd->bind_param('si', $motivo, $id);
        if ($upd->execute()) {
            $success = 'Motivo actualizado correctamente.';
        } else {
            $errors[] = 'Error al actualizar: ' . $upd->error;
        }
        $upd->close();
    }
}

// Prefill: obtener datos actuales (solo para mostrar)
$q = $mysqli->prepare('SELECT tipo_incidencia, fecha_solicitada, hora_inicio, hora_fin, motivo FROM incidencias WHERE id = ?');
$q->bind_param('i', $id);
$q->execute();
$q->bind_result($row_tipo, $row_fechas, $row_hi, $row_hf, $row_motivo);
if (!$q->fetch()) {
    die('Incidencia no encontrada.');
}
$q->close();
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Incidencia #<?= htmlspecialchars($id, ENT_QUOTES) ?></title>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- FontAwesome & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3a56e4;
            --dark-text: #212529;
            --light-bg: #f8fafc;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
        body { background:linear-gradient(135deg,#f5f7fa 0%,#e4ecfb 100%); min-height:100vh; display:flex; justify-content:center; padding:30px; }
        .container { background:white; border-radius:var(--border-radius); box-shadow:var(--box-shadow); width:100%; max-width:600px; overflow:hidden; }
        .header { background:linear-gradient(135deg,var(--primary-color),var(--primary-dark)); color:white; padding:20px 30px; display:flex; justify-content:space-between; align-items:center; }
        .header h1 { font-size:24px; font-weight:600; }
        .btn-back { display:inline-flex; align-items:center; gap:8px; padding:8px 16px; background:rgba(255,255,255,0.2); color:white; text-decoration:none; border-radius:30px; transition:var(--transition); backdrop-filter:blur(5px); }
        .btn-back:hover { background:rgba(255,255,255,0.3); transform:translateY(-2px); }
        .body { padding:30px; }
        .field { margin-bottom:20px; }
        .field label { font-weight:500; color:var(--dark-text); margin-bottom:5px; display:block; }
        .readonly { padding:14px; border:1px solid #e1e5eb; background:var(--light-bg); border-radius:8px; }
        textarea { width:100%; padding:14px; border:1px solid #e1e5eb; border-radius:8px; background:var(--light-bg); transition:var(--transition); min-height:120px; resize:vertical; }
        textarea:focus { outline:none; border-color:var(--primary-color); box-shadow:0 0 0 3px rgba(67,97,238,0.15); }
        .error-list, .success-alert { margin-bottom:20px; color:var(--dark-text); }
        .error-list ul { list-style:disc; margin-left:20px; }
        button.submit-btn { width:100%; padding:16px; background:linear-gradient(to right,var(--primary-color),var(--primary-dark)); color:white; border:none; border-radius:8px; font-size:17px; font-weight:600; cursor:pointer; transition:var(--transition); box-shadow:0 4px 15px rgba(67,97,238,0.3); display:flex; justify-content:center; align-items:center; gap:10px; }
        button.submit-btn:hover { transform:translateY(-3px); box-shadow:0 6px 20px rgba(67,97,238,0.4); }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-edit"></i> Editar Incidencia #<?= htmlspecialchars($id, ENT_QUOTES) ?></h1>
        <a href="consulta_personal.php" class="btn-back"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    <div class="body">
        <?php if ($errors): ?>
            <div class="error-list"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" id="editForm">
            <div class="field">
                <label>Tipo de Incidencia:</label>
                <div class="readonly"><?= htmlspecialchars($row_tipo, ENT_QUOTES) ?></div>
            </div>
            <div class="field">
                <label>Fechas Solicitadas:</label>
                <div class="readonly"><?= htmlspecialchars($row_fechas, ENT_QUOTES) ?></div>
            </div>
            <div class="field">
                <label>Horario:</label>
                <div class="readonly"><?= htmlspecialchars($row_hi, ENT_QUOTES) ?> - <?= htmlspecialchars($row_hf, ENT_QUOTES) ?></div>
            </div>
            <div class="field">
                <label for="motivo">Exposición de motivos:</label>
                <textarea id="motivo" name="motivo" required><?= htmlspecialchars($row_motivo, ENT_QUOTES) ?></textarea>
            </div>
            <button type="submit" class="submit-btn"><i class="fas fa-save"></i> Actualizar Motivo</button>
        </form>
    </div>
</div>

<?php if ($success): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: '¡Actualizado!',
            text: '<?= htmlspecialchars($success, ENT_QUOTES) ?>',
            confirmButtonText: 'OK'
        });
    </script>
<?php endif; ?>
</body>
</html>