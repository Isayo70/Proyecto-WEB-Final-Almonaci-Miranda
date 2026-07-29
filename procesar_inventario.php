<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login");
    exit();
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
    if (isset($_SESSION['rol']) && $_SESSION['rol'] == 4) {
        header("Location: indexcliente");
    } else {
        header("Location: index"); 
    }
    exit();
}

include 'conexion.php';

$nombre          = trim($_POST['nombre'] ?? $_POST['producto'] ?? '');
$categoria       = trim($_POST['categoria'] ?? 'Ropa');
$stock           = intval($_POST['stock'] ?? 0);
$precio          = floatval($_POST['precio'] ?? 0.00);

$talla           = ($categoria === 'Ropa') ? trim($_POST['talla'] ?? 'Unitalla') : '-';
$color           = ($categoria === 'Ropa') ? trim($_POST['color'] ?? '-') : '-';
$lote            = ($categoria === 'Suplemento' && !empty($_POST['lote'])) ? trim($_POST['lote']) : NULL;
$fecha_caducidad = ($categoria === 'Suplemento' && !empty($_POST['fecha_caducidad'])) ? trim($_POST['fecha_caducidad']) : NULL;

$ruta_destino = 'imgs/default.jpeg';

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $carpeta = "imgs/";
    if (!file_exists($carpeta)) {
        mkdir($carpeta, 0777, true);
    }
    
    $nombre_archivo = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES["imagen"]["name"]));
    $ruta_temporal = $carpeta . $nombre_archivo;
    
    if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $ruta_temporal)) {
        $ruta_destino = $ruta_temporal;
    }
}

$mensaje = "";
$exito = false;

if ($nombre !== '' && $stock >= 0 && $precio >= 0) {
    
    $sql = "INSERT INTO inventario (nombre_producto, categoria, talla, color, cantidad_stock, precio, estatus, fecha_caducidad, lote, imagen) 
            VALUES (?, ?, ?, ?, ?, ?, 'Activo', ?, ?, ?)";
    
    if ($stmt = $conexion->prepare($sql)) {
        $stmt->bind_param("ssssidsss", $nombre, $categoria, $talla, $color, $stock, $precio, $fecha_caducidad, $lote, $ruta_destino);
        if ($stmt->execute()) {
            $exito = true;
            $mensaje = "¡El artículo se ha registrado y guardado en el inventario con éxito!";
        } else {
            $mensaje = "Error al guardar en el inventario: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $mensaje = "Error al preparar la consulta SQL: " . $conexion->error;
    }
} else {
    $mensaje = "Por favor, completa los campos obligatorios con valores válidos.";
}

while($conexion->more_results() && $conexion->next_result()) {
    if($extraResult = $conexion->store_result()) {
        $extraResult->free();
    }
}
$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Procesar Inventario - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>
    <div class="container" style="max-width: 600px; margin: 80px auto; text-align: center;">
        <?php if ($exito): ?>
            <h2 style="color: #047857; margin-top: 0;">✅ ¡Producto Registrado!</h2>
            <p style="color: #4b5563; font-size: 15px; line-height: 1.5; margin: 15px 0;">
                <?= htmlspecialchars($mensaje) ?>
            </p>
            
            <div style="background: #f8fafc; padding: 20px; border-radius: 6px; margin-bottom: 25px; text-align: left; border: 1px solid #e2e8f0;">
                <p style="margin: 6px 0; color: #1e293b;"><strong>Artículo:</strong> <?= htmlspecialchars($nombre) ?></p>
                <p style="margin: 6px 0; color: #1e293b;"><strong>Categoría:</strong> <?= htmlspecialchars($categoria) ?></p>
                <?php if ($categoria === 'Ropa'): ?>
                    <p style="margin: 6px 0; color: #1e293b;"><strong>Talla:</strong> <?= htmlspecialchars($talla) ?> | <strong>Color:</strong> <?= htmlspecialchars($color) ?></p>
                <?php else: ?>
                    <p style="margin: 6px 0; color: #1e293b;"><strong>Lote:</strong> <?= htmlspecialchars($lote ?? 'N/A') ?> | <strong>Caducidad:</strong> <?= htmlspecialchars($fecha_caducidad ?? 'N/A') ?></p>
                <?php endif; ?>
                <p style="margin: 6px 0; color: #1e293b;"><strong>Stock Inicial:</strong> <?= $stock ?> unidades | <strong>Precio:</strong> $<?= number_format($precio, 2) ?> MXN</p>
            </div>
            <a href="index?mensaje=producto_registrado" class="btn btn-verde" style="padding: 12px 25px; text-decoration: none; font-weight: bold; display: inline-block;">Volver al Inventario</a>
        <?php else: ?>
            <h2 style="color: #b91c1c; margin-top: 0;">⚠️ Error en el Registro</h2>
            <p style="color: #4b5563; font-size: 15px; margin: 20px 0;">
                <?= htmlspecialchars($mensaje) ?>
            </p>
            <a href="index" class="btn" style="padding: 12px 25px; text-decoration: none; font-weight: bold; display: inline-block;">Regresar al Panel</a>
        <?php endif; ?>
    </div>

</body>
</html>