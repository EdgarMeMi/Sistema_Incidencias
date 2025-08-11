<?php
global $mysqli;
require('fpdf186/fpdf.php');
require 'sesion/auth.php';
require('sesion/conexion.php');

// ID de incidencia estático o dinámico
$id_incidencia = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id_incidencia || !is_numeric($id_incidencia)) {
    die("ID de incidencia inválido.");
}

$query = "SELECT i.*, u.nombre AS nombre_usuario, u.firma, d.nombre AS division
          FROM incidencias i
          JOIN usuarios u ON i.usuario_id = u.id
          LEFT JOIN divisiones d ON i.division_id = d.id
          WHERE i.id = ?
          LIMIT 1";

setlocale(LC_TIME, 'es_MX.UTF-8', 'es_ES.UTF-8', 'spanish');
$mysqli->set_charset("utf8");

$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $id_incidencia);
$stmt->execute();

$result = $stmt->get_result();
if (!$result || $result->num_rows === 0) {
    die('No se encontró la incidencia con ese ID.');
}

$incidencia = $result->fetch_assoc();

class PDF extends FPDF {
    function Header() {
        $this->Image('images/logoTecno.png', 28.5, 11, 20);
        $this->Image('images/iso_logo.png', 155, 13, 15);
        $this->Image('images/iso_logo14.png', 168, 13, 20);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 11, utf8_decode('Sistema de Gestión de Calidad del ITSSP'), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 11, utf8_decode('INCIDENCIA'), 0, 1, 'C');
        $this->Ln(10);
        $this->Rect(23,10,165,22);
        $this->Rect(23,10,30,22);
        $this->Rect(153,10,35,22);
        $this->Rect(53,10,100,11);
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 6, utf8_decode('Revisión: 06    Fecha de emisión: 2017-septiembre    Código: FT-RH-05    Documento: PR-RH-01    Página: ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function Incidencia($data) {
        $this->SetFont('Arial', '', 11);
        $this->SetX(23);
        $lineHeight = 7;

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(2, $lineHeight, utf8_decode('L.A. RUTH HERRERA DUEÑES'), 0, 1, 'L');
        $this->SetX(23);
        $this->Cell(2, $lineHeight, utf8_decode('JEFA DE DEPARTAMENTO DE PERSONAL'), 0, 1, 'L');
        $this->Ln(3);

        $this->SetFont('Arial', 'B', 11);
        $this->Cell(150, $lineHeight, utf8_decode('FECHA:'), 0, 0,'R');
        $this->SetFont('Arial', '', 11);

        $fecha = new DateTime($data['fecha_creacion']);
        $dia = $fecha->format('d');
        $mes = strftime('%B', $fecha->getTimestamp());
        $mes = mb_convert_case($mes, MB_CASE_TITLE, "UTF-8");
        $anio = $fecha->format('Y');

        $this->Line(160,65,189,65);
        $fecha_formateada = "$dia/$mes/$anio";
        $this->Cell(50, $lineHeight, utf8_decode($fecha_formateada), 0, 1);
        $this->Ln(3);

        $this->SetX(23);
        $this->Cell(20, $lineHeight, utf8_decode('El (la) C.'), 0, 0);
        $this->Line(41,74,122,74);
        $this->Cell(80, $lineHeight, utf8_decode($data['nombre_usuario']), 0, 0);
        $this->Cell(30, $lineHeight, utf8_decode('solicita registro de '), 0, 1);

        $this->SetX(23);
        $this->Line(98,81,180,81);
        $this->Cell(80, $lineHeight, utf8_decode('incidencias de labores en grupo/área de:'), 0, 0);
        $this->Cell(68, $lineHeight, utf8_decode($data['division']), 0, 1);

        // PROCESAMIENTO DE FECHAS SOLICITADAS
        $fechas_raw = explode(',', $data['fecha_solicitada']);
        $dias = [];
        $meses = [];
        $anios = [];

        foreach ($fechas_raw as $fecha_txt) {
            $fecha = DateTime::createFromFormat('Y-m-d', trim($fecha_txt));
            if ($fecha) {
                $dias[] = $fecha->format('d');
                $meses[] = strftime('%B', $fecha->getTimestamp());
                $anios[] = $fecha->format('Y');
            }
        }

        $dias_unicos = array_unique($dias);
        $meses_unicos = array_unique(array_map(function($m) {
            return ucfirst(mb_convert_case($m, MB_CASE_LOWER, 'UTF-8'));
        }, $meses));
        $anios_unicos = array_unique($anios);

        $dias_texto = implode(', ', array_slice($dias_unicos, 0, -1));
        if (count($dias_unicos) > 1) {
            $dias_texto .= ' y ' . end($dias_unicos);
        } else {
            $dias_texto = $dias_unicos[0];
        }

        $meses_texto = implode(' y ', $meses_unicos);
        $anios_texto = implode(' y ', $anios_unicos);

        // Mostrar días, meses y años
        $this->SetX(23);
        $this->Line(55,88,88,88);
        $this->Cell(35, $lineHeight, utf8_decode('por el (los) día(s)'), 0, 0);
        $this->Cell(30, $lineHeight, utf8_decode($dias_texto), 0, 0);
        $this->Line(110,88,148,88);
        $this->Cell(30, $lineHeight, utf8_decode('del mes de'), 0, 0);
        $this->Cell(30, $lineHeight, utf8_decode($meses_texto), 0, 0);
        $this->Cell(15, $lineHeight, utf8_decode('de'), 0, 0);
        $this->Line(158,88,180,88);
        $this->Cell(20, $lineHeight, utf8_decode($anios_texto), 0, 1);

        $this->SetX(23);
        $this->Cell(15, $lineHeight, utf8_decode('de las'), 0, 0);
        $this->Line(37,95,60,95);
        $this->Cell(25, $lineHeight, utf8_decode($data['hora_inicio']), 0, 0);
        $this->Cell(30, $lineHeight, utf8_decode('horas a las'), 0, 0);
        $this->Line(85,95,115,95);
        $this->Cell(25, $lineHeight, utf8_decode($data['hora_fin']), 0, 0);
        $this->Cell(15, $lineHeight, utf8_decode('horas.'), 0, 1);

        $this->Ln(4);
        $this->SetX(23);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(60, $lineHeight, utf8_decode('TIPO DE INCIDENCIA REQUERIDA:'), 0, 1);
        $this->Ln(3);
        $this->SetFont('Arial', '', 10);
        $this->SetX(35);

        $tiposFila1 = ['PERMISO', 'JUSTIFICANTE', 'PASE DE SALIDA'];
        foreach ($tiposFila1 as $tipo) {
            $checked = strtoupper($data['tipo_incidencia']) === $tipo ? 'X' : ' ';
            $this->Cell(6, $lineHeight, "[$checked]", 0, 0);
            $this->Cell(40, $lineHeight, utf8_decode($tipo), 0, 0);
        }

        $this->Ln(7);
        $this->SetX(52);

        $tiposFila2 = ['CAMBIO DE HORARIO', 'OMISIÓN DE CHECADA'];
        foreach ($tiposFila2 as $tipo) {
            $checked = strtoupper($data['tipo_incidencia']) === $tipo ? 'X' : ' ';
            $this->Cell(6, $lineHeight, "[$checked]", 0, 0);
            $this->Cell(45, $lineHeight, utf8_decode($tipo), 0, 0);
        }

        $this->Ln(10);
        $this->SetX(23);

        $tipo_permiso = strtolower($data['tipo_permiso']) === 'personal' ? 'Personales' : 'Institucionales';
        $this->SetX(67);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(25, $lineHeight, utf8_decode('PERSONALES'), 0, 0);
        $this->Cell(10, $lineHeight, $tipo_permiso === 'Personales' ? '[X]' : '[ ]', 0, 0);
        $this->Cell(33, $lineHeight, utf8_decode('INSTITUCIONALES'), 0, 0);
        $this->Cell(6, $lineHeight, $tipo_permiso === 'Institucionales' ? '[X]' : '[ ]', 0, 1);

        $this->Ln(2);
        $this->SetX(23);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(60, $lineHeight, utf8_decode('EXPOSICIÓN DE MOTIVOS:'), 0, 1);
        $this->SetFont('Arial', '', 10);
        $this->SetX(23);
        $this->MultiCell(0, $lineHeight, utf8_decode($data['motivo']));
        $this->Ln(35);

        // Firmas
        $x1 = $this->GetX();
        $y = $this->GetY();

        if (!empty($data['firma']) && file_exists($data['firma'] . ".png")) {
            $this->Image($data['firma'] . ".png", $x1 + 20, $y, 40);
        }

        if ($data['estado'] === 'ACEPTADO' && !empty($data['firma_jefe'])) {
            global $mysqli;
            $stmt_firma = $mysqli->prepare("SELECT firma FROM usuarios WHERE id = ?");
            $stmt_firma->bind_param("i", $data['firma_jefe']);
            $stmt_firma->execute();
            $stmt_firma->bind_result($firma_jefe_img);
            $stmt_firma->fetch();
            $stmt_firma->close();

            if (!empty($firma_jefe_img) && file_exists("$firma_jefe_img.png")) {
                $this->Image("$firma_jefe_img.png", $x1 + 110, $y, 40);
            }
        }

        $this->Ln(25);
        $this->Cell(90, 6, utf8_decode('Nombre y Firma del (a) Interesado (a)'), 0, 0, 'C');
        $this->Cell(90, 6, utf8_decode('Firma del (a) Jefe (a) Inmediato (a)'), 0, 1, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->Incidencia($incidencia);
$pdf->Output();
?>