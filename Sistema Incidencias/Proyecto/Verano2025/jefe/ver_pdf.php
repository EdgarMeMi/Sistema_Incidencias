<?php
// ver_pdf-jefe

// Verifica que el ID esté presente
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("ID de incidencia inválido.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Revisión de Incidencia #<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?></title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3a56e4;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
        body {
            background: linear-gradient(135deg,#f5f7fa 0%,#e4ecfb 100%);
            min-height:100vh; display:flex; justify-content:center; padding:30px;
        }
        .review-container {
            width:100%; max-width:1000px; background:white; border-radius:var(--border-radius);
            box-shadow:var(--box-shadow); overflow:hidden; display:flex; flex-direction:column;
        }
        .review-header {
            background: linear-gradient(135deg,var(--primary-color),var(--primary-dark));
            color:white; padding:25px 30px; display:flex; justify-content:space-between; align-items:center;
        }
        .header-content { display:flex; align-items:center; gap:15px; }
        .header-content i {
            font-size:28px; background:rgba(255,255,255,0.2); width:50px; height:50px;
            border-radius:50%; display:flex; align-items:center; justify-content:center;
        }
        .header-title { font-size:24px; font-weight:600; }
        .btn-back {
            display:inline-flex; align-items:center; gap:8px; padding:10px 20px;
            background:rgba(255,255,255,0.2); color:white; text-decoration:none;
            border-radius:30px; font-weight:500; transition:var(--transition);
            backdrop-filter:blur(5px);
        }
        .btn-back:hover {
            background:rgba(255,255,255,0.3); transform:translateY(-2px);
        }
        .review-body {
            padding:30px; flex:1; display:flex; flex-direction:column;
        }
        .pdf-container {
            flex:1; display:flex; flex-direction:column; border-radius:var(--border-radius);
            overflow:hidden; box-shadow:var(--box-shadow);
        }
        .pdf-header {
            background:#f8fafc; padding:15px 20px; border-bottom:1px solid #eef2f7;
            display:flex; align-items:center; gap:10px;
        }
        .pdf-title { font-weight:500; color:var(--dark-text); }
        .pdf-iframe { flex:1; width:100%; border:none; }
    </style>
</head>
<body>
<div class="review-container">
    <!-- Cabecera -->
    <div class="review-header">
        <div class="header-content">
            <i class="fas fa-file-contract"></i>
            <h1 class="header-title">Revisión de Incidencia #<?= htmlspecialchars($id, ENT_QUOTES,'UTF-8') ?></h1>
        </div>
        <a href="consulta_personal.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver al inicio
        </a>
    </div>

    <!-- PDF -->
    <div class="review-body">
        <div class="pdf-container">
            <div class="pdf-header">
                <i class="fas fa-file-pdf"></i>
                <div class="pdf-title">Documento de la Incidencia</div>
            </div>
            <iframe class="pdf-iframe" src="../generar_pdf.php?id=<?= urlencode($id) ?>"></iframe>
        </div>
    </div>
</div>
</body>
</html>