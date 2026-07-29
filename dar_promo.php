<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 1) {
    header("Location: index.php");
    exit();
}

include 'conexion.php';

$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mensaje_error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nuevo_precio'])) {
    $nuevo_precio = floatval($_POST['nuevo_precio']);
    $id_post = intval($_POST['id_producto']);

    $sql_update = "UPDATE inventario SET precio_original = COALESCE(precio_original, precio), precio = ? WHERE id_producto = ?";    $stmt_upd = $conexion->prepare($sql_update);
    $stmt_upd->bind_param("di", $nuevo_precio, $id_post);

    if ($stmt_upd->execute()) {
        header("Location: index.php?mensaje=promo_aplicada");
        exit();
    } else {
        $mensaje_error = "Hubo un error al actualizar el precio en la base de datos.";
    }
    $stmt_upd->close();
}

$sql_prod = "SELECT id_producto, nombre_producto, precio, fecha_caducidad, categoria FROM inventario WHERE id_producto = ?";
$stmt = $conexion->prepare($sql_prod);
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$resultado = $stmt->get_result();
$producto = $resultado->fetch_assoc();
$stmt->close();

if (!$producto) {
    die("<h2 style='text-align:center; margin-top:50px; color:#dc2626;'>Error: Producto no encontrado en el inventario.</h2>");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplicar Promoción - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
    <style>
        body { background-color: #f8fafc; font-family: Arial, sans-serif; }
        .promo-card { 
            max-width: 450px; margin: 60px auto; background: #ffffff; padding: 30px; 
            border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); 
            border-top: 5px solid #f59e0b;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #374151; }
        .form-group input[type="text"], .form-group input[type="number"] {
            width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;
            font-size: 16px;
        }
        .readonly-input { background-color: #f3f4f6; color: #6b7280; cursor: not-allowed; }
        
        .botones-rapidos { display: flex; gap: 10px; margin-top: 10px; }
        .btn-descuento { 
            background: #fef3c7; color: #b45309; border: 1px solid #fde68a; 
            padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; flex: 1;
        }
        .btn-descuento:hover { background: #fde68a; }
        
        .btn-guardar {
            width: 100%; background: #f59e0b; color: white; border: none; padding: 14px;
            font-size: 16px; font-weight: bold; border-radius: 6px; cursor: pointer; margin-top: 10px;
        }
        .btn-guardar:hover { background: #d97706; }
        .btn-cancelar {
            display: block; text-align: center; margin-top: 15px; color: #64748b; text-decoration: none; font-weight: 600;
        }
    </style>
</head>
<body>

<div class="promo-card">
    <h2 style="color: #d97706; margin-top: 0; text-align: center;">🔥 Aplicar Promoción Especial</h2>
    <p style="text-align: center; color: #64748b; margin-bottom: 25px;">Ajusta el precio para liquidar este artículo.</p>

    <?php if(!empty($mensaje_error)): ?>
        <div style="background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center;">
            <?= $mensaje_error ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="id_producto" value="<?= $producto['id_producto'] ?>">
        <input type="hidden" id="precio_base" value="<?= $producto['precio'] ?>">

        <div class="form-group">
            <label>Producto Seleccionado:</label>
            <input type="text" class="readonly-input" value="<?= htmlspecialchars($producto['nombre_producto']) ?>" readonly>
        </div>

        <?php if($producto['categoria'] === 'Suplemento'): ?>
        <div class="form-group">
            <label>Caducidad (¡Motivo de promo!):</label>
            <input type="text" class="readonly-input" value="<?= $producto['fecha_caducidad'] ?>" style="color: #dc2626; font-weight: bold;" readonly>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Precio Actual (MXN):</label>
            <input type="text" class="readonly-input" value="$<?= number_format($producto['precio'], 2) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Descuentos Rápidos:</label>
            <div class="botones-rapidos">
                <button type="button" class="btn-descuento" onclick="aplicarDescuento(10)">-10%</button>
                <button type="button" class="btn-descuento" onclick="aplicarDescuento(20)">-20%</button>
                <button type="button" class="btn-descuento" onclick="aplicarDescuento(30)">-30%</button>
                <button type="button" class="btn-descuento" onclick="aplicarDescuento(50)" style="background: #fee2e2; color: #dc2626; border-color: #fecaca;">-50%</button>
            </div>
        </div>

        <div class="form-group">
            <label for="nuevo_precio">Nuevo Precio a Cobrar (MXN):</label>
            <input type="number" step="0.01" min="1" name="nuevo_precio" id="nuevo_precio" placeholder="Ej. 199.99" required style="border-color: #f59e0b; font-weight: bold; color: #065f46;">
        </div>

        <button type="submit" class="btn-guardar">Guardar Precio de Promoción</button>
        <a href="index.php" class="btn-cancelar">Cancelar y volver al panel</a>
    </form>
</div>

<script>
    function aplicarDescuento(porcentaje) {
        let precioBase = parseFloat(document.getElementById('precio_base').value);
        let descuento = precioBase * (porcentaje / 100);
        let precioFinal = precioBase - descuento;
        
        document.getElementById('nuevo_precio').value = precioFinal.toFixed(2);
    }
</script>

</body>
</html>