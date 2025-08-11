<?php
// incidencia.php
global $mysqli;
session_start();
require '../sesion/auth.php';            // bloquea acceso si no hay sesión o rol
require '../sesion/conexion.php';
$mysqli->set_charset('utf8mb4');

// Si es petición AJAX (POST), procesamos y devolvemos JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $response = ['success' => false, 'errors' => [], 'message' => ''];
    $usuario_id      = (int) $_SESSION['usuario_id'];
    $division_id     = isset($_SESSION['division_id']) ? (int) $_SESSION['division_id'] : 1;
    $tipo_incidencia = mb_strtoupper(trim($_POST['tipo_incidencia'] ?? ''), 'UTF-8');
    $fechas_str      = $_POST['fecha_solicitada'] ?? '';
    $hora_inicio     = $_POST['hora_inicio'] ?: null;
    $hora_fin        = $_POST['hora_fin']    ?: null;
    $tipo_permiso    = mb_strtoupper(trim($_POST['tipo_permiso'] ?? ''), 'UTF-8');
    $motivo          = mb_strtoupper(trim($_POST['motivo'] ?? ''), 'UTF-8');

    // Validaciones
    $errors = [];

    // 1. Tipo de incidencia
    $tiposPermitidos = ['PERMISO','JUSTIFICANTE','PASE DE SALIDA','CAMBIO DE HORARIO','OMISIÓN DE CHECADA'];
    if (!in_array($tipo_incidencia, $tiposPermitidos)) {
        $errors[] = 'Tipo de incidencia no válido.';
    }

    // 2. Fechas
    $fechas = array_filter(array_map('trim', explode(',', $fechas_str)));
    if (empty($fechas)) {
        $errors[] = 'Debe seleccionar al menos una fecha.';
    } elseif (count($fechas) > 3) {
        $errors[] = 'Máximo 3 fechas permitidas.';
    } else {
        foreach ($fechas as $fecha) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                $errors[] = "Formato de fecha inválido: $fecha";
                break;
            }
        }
    }

    // 3. Tipo de permiso
    if (!in_array($tipo_permiso, ['PERSONAL','INSTITUCIONAL'])) {
        $errors[] = 'Debes elegir PERSONAL o INSTITUCIONAL.';
    }

    // 4. Horario: rango entre 07:00 y 21:00, y orden correcto
    $minHora = '07:00';
    $maxHora = '21:00';
    if ($hora_inicio === null || $hora_fin === null) {
        $errors[] = 'Debes indicar hora de inicio y hora de fin.';
    } elseif ($hora_inicio < $minHora || $hora_inicio > $maxHora
        || $hora_fin    < $minHora || $hora_fin    > $maxHora) {
        $errors[] = "El horario debe estar entre $minHora y $maxHora.";
    } elseif ($hora_fin <= $hora_inicio) {
        $errors[] = 'La hora fin debe ser posterior a la hora inicio.';
    }

    // Si hay errores, devolvemos JSON
    if (!empty($errors)) {
        $response['errors'] = $errors;
        echo json_encode($response);
        exit;
    }

    // Guardar en BD
    $fecha_solicitada = implode(',', $fechas);
    $sql = "INSERT INTO incidencias
            (usuario_id, division_id, fecha_solicitada, hora_inicio, hora_fin, 
             tipo_incidencia, tipo_permiso, motivo, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDIENTE')";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param(
            'iissssss',
            $usuario_id,
            $division_id,
            $fecha_solicitada,
            $hora_inicio,
            $hora_fin,
            $tipo_incidencia,
            $tipo_permiso,
            $motivo
        );
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Tu incidencia ha sido registrada correctamente.';
        } else {
            $response['errors'][] = 'Error al guardar: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['errors'][] = 'Error en la consulta: ' . $mysqli->error;
    }

    $mysqli->close();
    echo json_encode($response);
    exit;
}

// Si llegamos aquí, es GET: mostramos el formulario
$nombreUsuario = !empty($_SESSION['nombre'])
    ? htmlspecialchars($_SESSION['nombre'], ENT_QUOTES, 'UTF-8')
    : 'Usuario';
$rolUsuario = !empty($_SESSION['rol'])
    ? htmlspecialchars($_SESSION['rol'], ENT_QUOTES, 'UTF-8')
    : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Incidencia</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
        body { background: linear-gradient(135deg, #f5f7fa 0%, #e4ecfb 100%); min-height: 100vh;
            display: flex; justify-content: center; align-items: center; padding: 20px; }
        .form-container {
            background: #fff; border-radius: var(--border-radius); box-shadow: var(--box-shadow);
            width: 100%; max-width: 600px; overflow: hidden;
        }
        .form-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white; padding: 25px 30px;
        }
        .header-content { display: flex; justify-content: space-between; align-items: center; }
        .header-content h1 { font-size: 24px; font-weight: 600; }
        .btn-back {
            display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px;
            background: rgba(255,255,255,0.2); color: white; text-decoration: none;
            border-radius: 30px; font-weight: 500; transition: var(--transition);
            backdrop-filter: blur(5px);
        }
        .btn-back:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); }

        .form-body { padding: 30px; }
        #messageContainer { margin-bottom: 20px; }
        .form-group { margin-bottom: 25px; position: relative; }
        .form-group label {
            display: block; margin-bottom: 8px; font-weight: 500; color: var(--dark-text);
            display: flex; align-items: center; gap: 8px;
        }
        .form-group label i { color: var(--primary-color); font-size: 18px; }
        .form-control {
            width: 100%; padding: 14px 16px; border: 1px solid #e1e5eb;
            border-radius: 8px; font-size: 16px; transition: var(--transition);
            background-color: #f8fafc;
        }
        .form-control:focus {
            outline: none; border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67,97,238,0.15);
        }
        .hint { font-size: 13px; color: var(--light-text); margin-top: 8px;
            font-style: italic; display: block; }
        .time-container { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .radio-group { display: flex; gap: 25px; margin-top: 10px; }
        .radio-option { display: flex; align-items: center; gap: 8px; }
        .radio-option input { width: 18px; height: 18px; accent-color: var(--primary-color); }
        textarea.form-control { min-height: 120px; resize: vertical; }

        .btn-submit {
            width: 100%; padding: 16px; background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: white; border: none; border-radius: 8px; font-size: 17px; font-weight: 600;
            cursor: pointer; transition: var(--transition); box-shadow: 0 4px 15px rgba(67,97,238,0.3);
            display: flex; justify-content: center; align-items: center; gap: 10px;
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(67,97,238,0.4); }
        .btn-submit:active { transform: translateY(1px); }

        .error {
            color: var(--danger-color); margin-top: 8px; font-size: 14px;
            padding: 12px; background: rgba(231,76,60,0.08);
            border-radius: 8px; border-left: 4px solid var(--danger-color);
        }
        .error ul { margin: 8px 0 0 20px; }
        .error li { margin-bottom: 5px; }

        /* flatpickr styles */
        .flatpickr-calendar { font-family: 'Poppins', sans-serif;
            box-shadow: var(--box-shadow); border-radius: 12px; overflow: hidden; border: none;
        }
        .flatpickr-day.selected { background: var(--primary-color); border-color: var(--primary-color); }
        .flatpickr-day.today { border-color: var(--primary-color); }
        .flatpickr-day.today:hover,
        .flatpickr-day.today:focus { background: var(--primary-color); color: white; }
        .flatpickr-day.inRange {
            background: rgba(67,97,238,0.1);
            box-shadow: -5px 0 0 rgba(67,97,238,0.1), 5px 0 0 rgba(67,97,238,0.1);
        }
        .flatpickr-months .flatpickr-prev-month:hover svg,
        .flatpickr-months .flatpickr-next-month:hover svg { fill: var(--primary-color); }

        @media (max-width: 576px) {
            .form-header { padding: 20px; }
            .header-content h1 { font-size: 20px; }
            .form-body { padding: 20px; }
            .time-container { grid-template-columns: 1fr; }
            .radio-group { flex-direction: column; gap: 12px; }
        }
    </style>
</head>
<body>
<div class="form-container">
    <div class="form-header">
        <div class="header-content">
            <h1><i class="fas fa-file-alt"></i> Registro de Incidencias</h1>
            <a href="index.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="form-body">
        <div id="messageContainer"></div>
        <form id="incidenciaForm">
            <div class="form-group">
                <label for="tipo_incidencia"><i class="fas fa-list"></i> Tipo de Incidencia:</label>
                <select class="form-control" name="tipo_incidencia" id="tipo_incidencia" required>
                    <option value="">-- Selecciona --</option>
                    <option value="PERMISO">Permiso</option>
                    <option value="JUSTIFICANTE">Justificante</option>
                    <option value="PASE DE SALIDA">Pase de Salida</option>
                    <option value="CAMBIO DE HORARIO">Cambio de horario</option>
                    <option value="OMISIÓN DE CHECADA">Omisión de checada</option>
                </select>
            </div>

            <div class="form-group">
                <label for="fecha_solicitada"><i class="far fa-calendar-alt"></i> Fechas solicitadas:</label>
                <input type="text" class="form-control" name="fecha_solicitada" id="fecha_solicitada"
                       placeholder="Selecciona las fechas" readonly required>
                <span class="hint">Puedes seleccionar hasta 3 fechas</span>
            </div>

            <div class="form-group">
                <label><i class="far fa-clock"></i> Horario:</label>
                <div class="time-container">
                    <div>
                        <label for="hora_inicio">Hora inicio:</label>
                        <input type="time" class="form-control"
                               name="hora_inicio" id="hora_inicio"
                               min="07:00" max="21:00" required>
                    </div>
                    <div>
                        <label for="hora_fin">Hora fin:</label>
                        <input type="time" class="form-control"
                               name="hora_fin" id="hora_fin"
                               min="07:00" max="21:00" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-tag"></i> Tipo de permiso:</label>
                <div class="radio-group">
                    <div class="radio-option">
                        <input type="radio" name="tipo_permiso" id="personal" value="PERSONAL" required>
                        <label for="personal">PERSONAL</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" name="tipo_permiso" id="institucional" value="INSTITUCIONAL">
                        <label for="institucional">INSTITUCIONAL</label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="motivo"><i class="fas fa-comment-alt"></i> Exposición de motivos:</label>
                <textarea class="form-control" name="motivo" id="motivo" required
                          placeholder="Describe el motivo de tu solicitud..."></textarea>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Enviar Solicitud
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script>
    // Flatpickr para fechas
    flatpickr("#fecha_solicitada", {
        mode: "multiple",
        dateFormat: "Y-m-d",
        locale: "es",
        disableMobile: true,
        onChange(dates) {
            if (dates.length > 3) dates.pop();
            dates.sort((a,b)=>a-b);
            this.setDate(dates);
            document.getElementById('fecha_solicitada').value =
                dates.map(d=>d.toISOString().slice(0,10)).join(', ');
        }
    });

    // Validación de horas y envío AJAX
    document.getElementById('incidenciaForm').addEventListener('submit', async e => {
        e.preventDefault();
        const cont = document.getElementById('messageContainer');
        cont.innerHTML = '';

        const hi = document.getElementById('hora_inicio').value;
        const hf = document.getElementById('hora_fin').value;
        const min = '07:00', max = '21:00';

        if (!hi || !hf) {
            cont.innerHTML = '<div class="error">Debes indicar hora de inicio y fin.</div>';
            return;
        }
        if (hi < min || hi > max || hf < min || hf > max) {
            cont.innerHTML = `<div class="error">
                El horario debe estar entre ${min} y ${max}.
            </div>`;
            return;
        }
        if (hf <= hi) {
            cont.innerHTML = '<div class="error">La hora fin debe ser posterior a la de inicio.</div>';
            return;
        }

        const data = new FormData(e.target);
        try {
            const res  = await fetch('incidencia.php', { method:'POST', body:data });
            const json = await res.json();
            if (json.success) {
                await Swal.fire({ icon:'success', title:'¡Éxito!', text: json.message });
                e.target.reset();
                flatpickr("#fecha_solicitada").clear();
            } else {
                let html = '<div class="error"><ul>';
                json.errors.forEach(err=> html += `<li>${err}</li>`);
                html += '</ul></div>';
                cont.innerHTML = html;
            }
        } catch(err) {
            cont.innerHTML = `<div class="error">Error en la conexión: ${err.message}</div>`;
        }
    });

    // Cambio de borde según tipo de incidencia
    document.getElementById('tipo_incidencia').addEventListener('change', function(){
        const map = {
            'PERMISO': 'var(--primary-color)',
            'JUSTIFICANTE':'#9b59b6',
            'PASE DE SALIDA':'var(--secondary-color)',
            'CAMBIO DE HORARIO':'var(--warning-color)',
            'OMISIÓN DE CHECADA':'var(--danger-color)'
        };
        const fc = document.querySelector('.form-container');
        fc.style.borderLeft = map[this.value] ? `6px solid ${map[this.value]}` : 'none';
    });
</script>
</body>
</html>
