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

include 'conexion.php';

$id_producto = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_producto <= 0) {
    die("<h2 style='text-align:center; margin-top:50px; font-family:sans-serif;'>Producto no válido. <a href='indexcliente'>Volver al catálogo</a></h2>");
}

$sql = "SELECT * FROM inventario WHERE id_producto = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("<h2 style='text-align:center; margin-top:50px; font-family:sans-serif;'>Producto no encontrado. <a href='indexcliente'>Volver al catálogo</a></h2>");
}

$producto = $resultado->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($producto['nombre_producto']) ?> - Detalles</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>

    <a href="indexcliente" class="btn-volver">← Volver al catálogo</a>

    <div class="detalle-container">
        <div class="imagen-producto">
            <img src="<?= htmlspecialchars($producto['imagen'] ?? 'Imgs/logo.png') ?>" 
                 alt="<?= htmlspecialchars($producto['nombre_producto']) ?>">
        </div>

        <div class="info-producto">
            <h1 style="margin-top: 0; color: #111827;"><?= htmlspecialchars($producto['nombre_producto']) ?></h1>
            
            <div class="precio-detalle">
                $<?= number_format($producto['precio'], 2) ?> MXN
            </div>
            
            <div style="margin: 20px 0;">
                <span class="badge-detalle"><strong>Talla:</strong> <?= htmlspecialchars($producto['talla'] ?? 'Única') ?></span>
                <span class="badge-detalle"><strong>Color/Detalle:</strong> <?= htmlspecialchars($producto['color'] ?? $producto['lote'] ?? 'Estándar') ?></span>
            </div>

            <p style="color: #4b5563;"><strong>Disponibles en inventario:</strong> <?= htmlspecialchars($producto['cantidad_stock'] ?? 0) ?> piezas</p>
            
            <p style="color: #6b7280; line-height: 1.6;">
                Artículo de alta calidad disponible en nuestra tienda deportiva. Diseñado para ofrecer el máximo confort y rendimiento.
            </p>

            <form method="POST" action="agregar_carrito">
                <input type="hidden" name="id_producto" value="<?= $producto['id_producto'] ?>">
                
                <div style="margin-top: 20px;">
                    <label for="cantidad" style="color: #374151;"><strong>Cantidad:</strong></label>
                    <input type="number" id="cantidad" name="cantidad" value="1" min="1" max="<?= htmlspecialchars($producto['cantidad_stock'] ?? 1) ?>" style="width: 70px; padding: 8px; margin-left: 10px; border: 1px solid #d1d5db; border-radius: 4px;">
                </div>
                
                <button type="submit" class="btn-comprar">🛒 AGREGAR AL CARRITO</button>
            </form>
        </div>
    </div>

</body>
</html>