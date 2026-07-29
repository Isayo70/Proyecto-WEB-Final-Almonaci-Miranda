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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Producto - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>
    <div class="registro-basico-container">
        <h2>Registrar Nuevo Producto</h2>
        
        <form action="procesar_inventario" method="POST">
            
            <label for="nombre">Nombre del Producto:</label>
            <input type="text" id="nombre" name="nombre" placeholder="Ej. Playera Deportiva" required>

            <label for="talla">Talla:</label>
            <input type="text" id="talla" name="talla" placeholder="Ej. M, L, Unitalla">

            <label for="color">Color:</label>
            <input type="text" id="color" name="color" placeholder="Ej. Azul, Negro">

            <label for="stock">Cantidad en Stock:</label>
            <input type="number" id="stock" name="stock" value="1" min="0" required>

            <label for="precio">Precio ($ MXN):</label>
            <input type="number" step="0.01" id="precio" name="precio" placeholder="0.00" required>

            <label for="estatus">Estatus Inicial:</label>
            <select id="estatus" name="estatus">
                <option value="Activo">Alta / Activo</option>
                <option value="Agotado">Agotado</option>
                <option value="Baja">Baja</option>
            </select>

            <button type="submit" class="btn-guardar-basico">Guardar Producto</button>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="index" style="color: #6b7280; text-decoration: none; font-size: 14px; font-weight: bold;">← Volver a la lista principal</a>
        </div>
    </div>
</body>
</html>