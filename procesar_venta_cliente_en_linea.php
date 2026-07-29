<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login");
    exit();
}
if (isset($_SESSION['rol']) && ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2)) {
    header("Location: index");
    exit();
}
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 4) {
    header("Location: login");
    exit();
}
include 'conexion.php';
if (!isset($_SESSION['carrito']) || count($_SESSION['carrito']) === 0) {
    header("Location: indexcliente");
    exit();
}
$tipo_entrega = isset($_POST['tipo_entrega']) ? trim($_POST['tipo_entrega']) : 'sucursal';
$direccion_cliente = isset($_POST['direccion_cliente']) ? trim($_POST['direccion_cliente']) : '';
$conexion->begin_transaction(); 
try {
    $total_venta = 0;
    $ids_productos = array_keys($_SESSION['carrito']);
    $ids_csv = implode(',', array_map('intval', $ids_productos));
    $sql_precios = "SELECT id_producto, precio, cantidad_stock FROM inventario WHERE id_producto IN ($ids_csv)";
    $resultado_precios = $conexion->query($sql_precios);
    $productos_db = [];
    while ($fila = $resultado_precios->fetch_assoc()) {
        $productos_db[$fila['id_producto']] = $fila;
        $cantidad_pedida = $_SESSION['carrito'][$fila['id_producto']];
        
        if ($fila['cantidad_stock'] < $cantidad_pedida) {
            throw new Exception("Lo sentimos, ya no hay suficiente stock disponible para el producto ID: " . $fila['id_producto']);
        }
        
        $total_venta += ($fila['precio'] * $cantidad_pedida);
    }
    $id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : null;
    $sql_venta = "INSERT INTO ventas (id_usuario, total, tipo_entrega, direccion, metodo_pago) VALUES (?, ?, ?, ?, ?)";
    $stmt_venta = $conexion->prepare($sql_venta);
    $metodo_pago = 'Tarjeta';
    $stmt_venta->bind_param("idsss", $id_usuario, $total_venta, $tipo_entrega, $direccion_cliente, $metodo_pago);
    $stmt_venta->execute();
    $id_venta_generada = $conexion->insert_id; 
    $sql_detalle = "INSERT INTO detalle_ventas (id_venta, id_producto, cantidad) VALUES (?, ?, ?)";
    $stmt_detalle = $conexion->prepare($sql_detalle);
    $sql_descuento = "UPDATE inventario SET cantidad_stock = cantidad_stock - ? WHERE id_producto = ?";
    $stmt_descuento = $conexion->prepare($sql_descuento);

    foreach ($_SESSION['carrito'] as $id_producto => $cantidad) {
        $stmt_detalle->bind_param("iii", $id_venta_generada, $id_producto, $cantidad);
        $stmt_detalle->execute();

        $stmt_descuento->bind_param("ii", $cantidad, $id_producto);
        $stmt_descuento->execute();
    }

    $conexion->commit();

    unset($_SESSION['carrito']);

    $mensaje = "¡Venta procesada con éxito!<br><br> Tu número de folio es: <strong>#" . $id_venta_generada . "</strong><br><br>";
    
    if ($tipo_entrega == 'domicilio') {
        $mensaje .= "🚚 <strong>Método de entrega:</strong> Envío a domicilio<br>";
        $mensaje .= "📍 <strong>Dirección de destino:</strong> " . htmlspecialchars($direccion_cliente);
    } else {
        $mensaje .= "🏪 <strong>Método de entrega:</strong> Recoger en sucursal central de Aguascalientes";
    }
    
    $tipo_alerta = "success";

} catch (Exception $e) {
    $conexion->rollback();
    $mensaje = "Error al procesar la compra: " . $e->getMessage();
    $tipo_alerta = "error";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado de la Compra - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>

    <div class="resultado-compra-wrapper">
        <div class="mensaje-box">
            <h1 class="<?= $tipo_alerta === 'success' ? 'success-title' : 'error-title' ?>" style="margin-top: 0;">
                <?= $tipo_alerta === 'success' ? '✅ ¡Compra Exitosa!' : '❌ Ups, algo salió mal' ?>
            </h1>
            <p style="font-size: 1.1em; line-height: 1.6; margin: 20px 0; color: #374151;">
                <?= $mensaje ?>
            </p>
            <a href="indexcliente" class="btn-accion-compra">Volver al Catálogo</a>
        </div>
    </div>

</body>
</html>