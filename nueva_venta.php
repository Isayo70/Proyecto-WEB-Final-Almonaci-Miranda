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
$sql_productos = "SELECT id_producto, nombre_producto, talla, color, precio, cantidad_stock FROM inventario WHERE estatus = 'Activo' AND cantidad_stock > 0";
$resultado_productos = mysqli_query($conexion, $sql_productos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Punto de Venta - Registrar Venta</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>
    <div class="nueva-venta-container">
        <h2>Registrar Nueva Venta</h2>
        <form action="procesar_venta" method="POST"> 
            <label for="metodo_pago">Método de Pago:</label>
            <select name="metodo_pago" id="metodo_pago" required>
                <option value="Efectivo">💵 Efectivo</option>
                <option value="Tarjeta">💳 Tarjeta</option>
                <option value="Transferencia">📲 Transferencia</option>
            </select>
            <label for="id_producto">Selecciona el Producto:</label>
            <select name="id_producto" id="id_producto" required>
                <option value="">-- Elige una prenda --</option>
                <?php 
                if ($resultado_productos && mysqli_num_rows($resultado_productos) > 0) {
                    while($producto = mysqli_fetch_assoc($resultado_productos)) {
                        $id = $producto['id_producto'];
                        $nombre = htmlspecialchars($producto['nombre_producto']);
                        $talla = htmlspecialchars($producto['talla']);
                        $precio = number_format($producto['precio'], 2);
                        $stock = $producto['cantidad_stock'];
                        echo "<option value='$id'>$nombre (Talla: $talla) - $$precio MXN - [Disponibles: $stock]</option>";
                    }
                } else {
                    echo "<option value=''>No hay productos con stock disponible</option>";
                }
                ?>
            </select>
            <label for="cantidad">Cantidad a vender:</label>
            <input type="number" id="cantidad" name="cantidad" min="1" value="1" required>
            <button type="submit" class="btn-venta">Confirmar Venta y Cobrar</button>
        </form>
        <div style="text-align: center; margin-top: 20px;">
            <a href="index" style="color: #6b7280; text-decoration: none; font-size: 14px; font-weight: bold;">Cancelar y volver al panel</a>
        </div>
    </div>
</body>
</html>