<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login");
    exit();
}
if (isset($_SESSION['rol']) && $_SESSION['rol'] == 4) {
    header("Location: indexcliente");
    exit();
}
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2)) {
    header("Location: login");
    exit();
}
include 'conexion.php'; 
if ($_SERVER["REQUEST_METHOD"] == "POST") {  
    $id_producto = intval($_POST['id_producto'] ?? 0);
    $cantidad_vendida = intval($_POST['cantidad'] ?? 0);
    $metodo_pago = trim($_POST['metodo_pago'] ?? 'Efectivo');
    $id_usuario = intval($_SESSION['id_usuario'] ?? 0); 
    if ($id_producto <= 0 || $cantidad_vendida <= 0) {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Error en Venta - PruebaTla</title>
            <link rel="stylesheet" href="Diseñoestilo.css">
        </head>
        <body>
            <div class="container" style="text-align: center; max-width: 500px; margin-top: 100px;">
                <h2 style="color: #dc2626; margin-top: 0;">⚠️ Datos no válidos</h2>
                <p style="color: #4b5563; font-size: 16px;">La cantidad o el producto seleccionado no son correctos.</p>
                <br>
                <a href="nueva_venta" class="btn" style="background: #111827; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;">Volver a intentar</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }

    $sql_check = "SELECT cantidad_stock, precio FROM inventario WHERE id_producto = ?";
    $stmt_check = $conexion->prepare($sql_check);
    $stmt_check->bind_param("i", $id_producto);
    $stmt_check->execute();
    $resultado = $stmt_check->get_result();

    if ($resultado->num_rows === 1) {
        $producto = $resultado->fetch_assoc();
        $stock_actual = intval($producto['cantidad_stock']);
        $precio_unitario = floatval($producto['precio']);
        $total_venta = $precio_unitario * $cantidad_vendida;

        if ($stock_actual >= $cantidad_vendida) {
            
            $sql_t = "SELECT id_turno FROM turnos_caja WHERE id_usuario = ? AND estatus = 'Abierto' ORDER BY id_turno DESC LIMIT 1";
            $stmt_t = $conexion->prepare($sql_t);
            $stmt_t->bind_param("i", $id_usuario);
            $stmt_t->execute();
            $res_t = $stmt_t->get_result();
            $turno_actual = $res_t->fetch_assoc();
            $id_turno_activo = $turno_actual['id_turno'] ?? null;
            $stmt_t->close();

            $conexion->begin_transaction();

            try {
                $nuevo_stock = $stock_actual - $cantidad_vendida;
                $sql_update = "UPDATE inventario SET cantidad_stock = ? WHERE id_producto = ?";
                $stmt_update = $conexion->prepare($sql_update);
                $stmt_update->bind_param("ii", $nuevo_stock, $id_producto);
                $stmt_update->execute();
                $stmt_update->close();

                $fecha_actual = date('Y-m-d H:i:s');
                $tipo_entrega = 'sucursal'; 
                $estado_envio = 'Entregado'; 

                $sql_venta = "INSERT INTO ventas (id_usuario, id_turno, total, tipo_entrega, metodo_pago, estado_envio, fecha_venta) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt_venta = $conexion->prepare($sql_venta);
                $stmt_venta->bind_param("iidssss", $id_usuario, $id_turno_activo, $total_venta, $tipo_entrega, $metodo_pago, $estado_envio, $fecha_actual);
                $stmt_venta->execute();
                
                $id_venta_generada = $conexion->insert_id;
                $stmt_venta->close();

                $sql_det = "INSERT INTO detalle_ventas (id_venta, id_producto, cantidad) VALUES (?, ?, ?)";
                $stmt_det = $conexion->prepare($sql_det);
                $stmt_det->bind_param("iii", $id_venta_generada, $id_producto, $cantidad_vendida);
                $stmt_det->execute();
                $stmt_det->close();

                $conexion->commit();

                header("Location: index?mensaje=venta_exitosa&folio=" . $id_venta_generada);
                exit();

            } catch (Exception $e) {
                $conexion->rollback();
                
                ?>
                <!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <title>Error en Transacción - PruebaTla</title>
                    <link rel="stylesheet" href="Diseñoestilo.css">
                </head>
                <body>
                    <div class="container" style="text-align: center; max-width: 600px; margin-top: 100px;">
                        <h2 style="color: #dc2626; margin-top: 0;">❌ Error al procesar la venta</h2>
                        <p style="color: #4b5563; font-size: 15px;"><?= htmlspecialchars($e->getMessage()) ?></p>
                        <br>
                        <a href="nueva_venta" class="btn" style="background: #111827; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;">Regresar al Punto de Venta</a>
                    </div>
                </body>
                </html>
                <?php
                exit();
            }
            
        } else {
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Stock Insuficiente - PruebaTla</title>
                <link rel="stylesheet" href="Diseñoestilo.css">
            </head>
            <body>
                <div class="container" style="text-align: center; max-width: 600px; margin-top: 100px;">
                    <h2 style="color: #dc2626; margin-top: 0;">❌ Stock Insuficiente</h2>
                    <p style="color: #4b5563; font-size: 16px; line-height: 1.5;">
                        Estás intentando vender <strong><?= $cantidad_vendida ?></strong> piezas, pero en el inventario solo quedan <strong><?= $stock_actual ?></strong> disponibles.
                    </p>
                    <br>
                    <a href="nueva_venta" class="btn" style="background: #111827; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;">Volver al Punto de Venta</a>
                </div>
            </body>
            </html>
            <?php
            exit();
        }
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Producto No Encontrado - PruebaTla</title>
            <link rel="stylesheet" href="Diseñoestilo.css">
        </head>
        <body>
            <div class="container" style="text-align: center; max-width: 500px; margin-top: 100px;">
                <h2 style="color: #dc2626; margin-top: 0;">❌ Producto No Encontrado</h2>
                <p style="color: #4b5563; font-size: 16px;">El artículo seleccionado ya no existe en la base de datos del inventario.</p>
                <br>
                <a href="nueva_venta" class="btn" style="background: #111827; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;">Volver al Punto de Venta</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
    $stmt_check->close();
} else {
    header("Location: index");
    exit();
}
?>