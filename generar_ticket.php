<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login");
    exit();
}

include 'conexion.php';

if (!file_exists('fpdf.php')) {
    die("Error: No se encontró el archivo fpdf.php. Asegúrate de haberlo colocado en la carpeta del proyecto.");
}

require('fpdf.php');

$id_venta = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_venta <= 0) {
    die("Error: No se especificó un folio de venta válido.");
}

$sql = "SELECT v.id_venta, v.total, v.tipo_entrega, v.metodo_pago, v.fecha_venta, u.nombre_usuario, v.id_usuario 
        FROM ventas v 
        LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario 
        WHERE v.id_venta = ?";
        
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("Error: La venta solicitada no existe en el sistema.");
}

$venta = $resultado->fetch_assoc();
$stmt->close();

if (isset($_SESSION['rol']) && $_SESSION['rol'] == 4) {
    if ($venta['id_usuario'] != $_SESSION['id_usuario']) {
        die("Acceso denegado: No puedes visualizar tickets de otros usuarios.");
    }
}

$pdf = new FPDF('P', 'mm', array(80, 200));
$pdf->AddPage();
$pdf->SetMargins(5, 5, 5);

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(70, 6, utf8_decode('PRUEBATLA'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(70, 5, utf8_decode('Ropa Deportiva para Dama y Caballero'), 0, 1, 'C');
$pdf->Cell(70, 5, utf8_decode('Sucursal Centro, Aguascalientes'), 0, 1, 'C');
$pdf->Ln(3);

$pdf->Cell(70, 2, '---------------------------------------------------------', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(70, 5, utf8_decode('TICKET DE COMPRA'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->Ln(2);
$pdf->Cell(35, 5, 'Folio: #' . $venta['id_venta'], 0, 0, 'L');
$pdf->Cell(35, 5, 'Fecha: ' . date("d/m/Y", strtotime($venta['fecha_venta'])), 0, 1, 'R');
$pdf->Cell(70, 5, 'Hora: ' . date("h:i A", strtotime($venta['fecha_venta'])), 0, 1, 'L');
$pdf->Cell(70, 5, utf8_decode('Cliente: ' . ($venta['nombre_usuario'] ?? 'Mostrador')), 0, 1, 'L');

$pdf->Ln(2);
$pdf->Cell(70, 2, '---------------------------------------------------------', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(35, 5, 'Canal:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 9);
$tipo = ($venta['tipo_entrega'] == 'domicilio') ? 'En Linea' : 'Sucursal';
$pdf->Cell(35, 5, utf8_decode($tipo), 0, 1, 'R');

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(35, 5, utf8_decode('Método de Pago:'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 9);
$metodo = !empty($venta['metodo_pago']) ? $venta['metodo_pago'] : 'Efectivo';
$pdf->Cell(35, 5, utf8_decode($metodo), 0, 1, 'R');

$pdf->Ln(3);
$pdf->Cell(70, 2, '---------------------------------------------------------', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(10, 4, 'CANT', 0, 0, 'C');
$pdf->Cell(40, 4, utf8_decode('PRODUCTO'), 0, 0, 'L');
$pdf->Cell(20, 4, 'IMPORTE', 0, 1, 'R');
$pdf->SetFont('Arial', '', 8);

$sql_detalles = "SELECT d.cantidad, i.precio, i.nombre_producto, i.talla, i.color, i.categoria, i.lote 
                 FROM detalle_venta d 
                 JOIN inventario i ON d.id_producto = i.id_producto 
                 WHERE d.id_venta = ?
                 UNION ALL
                 SELECT d.cantidad, i.precio, i.nombre_producto, i.talla, i.color, i.categoria, i.lote 
                 FROM detalle_ventas d 
                 JOIN inventario i ON d.id_producto = i.id_producto 
                 WHERE d.id_venta = ?";
                 
$stmt_detalles = $conexion->prepare($sql_detalles);

if ($stmt_detalles) {
    $stmt_detalles->bind_param("ii", $id_venta, $id_venta);
    $stmt_detalles->execute();
    $resultado_detalles = $stmt_detalles->get_result();
    
    $pdf->Ln(5);

    while ($detalle = $resultado_detalles->fetch_assoc()) {
        $cantidad = $detalle['cantidad'];
        $precio_unitario = $detalle['precio'] ?? 0; 
        $importe = $cantidad * $precio_unitario; 
        
        if ($detalle['categoria'] === 'Suplemento') {
            $nombre_txt = $detalle['nombre_producto'] . ' (' . ($detalle['lote'] ?? '') . ')';
        } else {
            $nombre_txt = $detalle['nombre_producto'] . ' (' . $detalle['talla'] . ' ' . $detalle['color'] . ')';
        }
        
        $nombre_completo = utf8_decode($nombre_txt);
        $nombre_corto = substr($nombre_completo, 0, 23);
        
        $pdf->Cell(10, 5, $cantidad, 0, 0, 'C');
        $pdf->Cell(40, 5, $nombre_corto, 0, 0, 'L');
        $pdf->Cell(20, 5, '$' . number_format($importe, 2), 0, 1, 'R');
    }
    $stmt_detalles->close();
} else {
    $pdf->Cell(70, 5, 'Error leyendo detalles de venta', 0, 1, 'C');
}

$pdf->Ln(2);
$pdf->Cell(70, 2, '---------------------------------------------------------', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(35, 7, 'TOTAL:', 0, 0, 'L');
$pdf->Cell(35, 7, '$' . number_format($venta['total'], 2) . ' MXN', 0, 1, 'R');

$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(70, 4, utf8_decode('¡Gracias por tu preferencia!'), 0, 1, 'C');
$pdf->Cell(70, 4, utf8_decode('Conserva este ticket para cualquier aclaración.'), 0, 1, 'C');

$pdf->Output('I', 'Ticket_Folio_' . $venta['id_venta'] . '.pdf');
?>