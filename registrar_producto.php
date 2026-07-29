<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 1) {
    header("Location: index");
    exit();
}

include 'conexion.php';
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_producto = mysqli_real_escape_string($conexion, $_POST['nombre_producto']);
    $categoria       = mysqli_real_escape_string($conexion, $_POST['categoria']);
    $genero          = mysqli_real_escape_string($conexion, $_POST['genero']); // NUEVO CAMPO
    $talla           = mysqli_real_escape_string($conexion, $_POST['talla']);
    $color           = mysqli_real_escape_string($conexion, $_POST['color']);
    $cantidad_stock  = intval($_POST['cantidad_stock']);
    $precio          = floatval($_POST['precio']);
    $fecha_caducidad = !empty($_POST['fecha_caducidad']) ? $_POST['fecha_caducidad'] : NULL;
    $lote            = !empty($_POST['lote']) ? mysqli_real_escape_string($conexion, $_POST['lote']) : NULL;
    $ruta_imagen = "imgs/default.jpeg"; 
    
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $directorio_destino = "imgs/";
        $nombre_archivo = time() . "_" . basename($_FILES['imagen']['name']);
        $ruta_final = $directorio_destino . $nombre_archivo;

        $tipo_archivo = strtolower(pathinfo($ruta_final, PATHINFO_EXTENSION));
        $tipos_permitidos = array("jpg", "jpeg", "png", "gif", "webp");

        if (in_array($tipo_archivo, $tipos_permitidos)) {
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_final)) {
                $ruta_imagen = $ruta_final;
            } else {
                $mensaje = "<p style='color:red;'>Error al subir la imagen. Se usará la predeterminada.</p>";
            }
        } else {
            $mensaje = "<p style='color:red;'>Formato de imagen no permitido. Usa JPG, PNG o WEBP.</p>";
        }
    }

    $sql = "INSERT INTO inventario (nombre_producto, categoria, genero, talla, color, cantidad_stock, precio, imagen, fecha_caducidad, lote, estatus) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Activo')";
            
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssssidsss", $nombre_producto, $categoria, $genero, $talla, $color, $cantidad_stock, $precio, $ruta_imagen, $fecha_caducidad, $lote);

    if ($stmt->execute()) {
        header("Location: index?mensaje=producto_registrado");
        exit();
    } else {
        $mensaje = "<p style='color:red;'>Error al registrar el producto: " . $stmt->error . "</p>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Nuevo Producto - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>

<div class="login-container" style="max-width: 600px; margin: 40px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <a href="index" style="color: #4f46e5; text-decoration: none; font-weight: bold; margin-bottom: 15px; display: inline-block;">← Volver al Panel</a>
    <h2 style="margin-top: 0;">Registrar Nuevo Artículo</h2>
    
    <?= $mensaje ?>

    <form action="registrar_producto" method="POST" enctype="multipart/form-data">
        
        <label for="nombre_producto" style="display:block; margin-bottom:5px; font-weight:bold; font-size:14px;">Nombre del Producto / Suplemento:</label>
        <input type="text" name="nombre_producto" required style="width:100%; padding:10px; margin-bottom:15px; border: 1px solid #ccc; border-radius:4px; box-sizing:border-box;">

        <div style="display: flex; gap: 15px; margin-bottom: 15px;">
            <div style="flex: 1;">
                <label for="categoria" style="display:block; margin-bottom:5px; font-weight:bold; font-size:14px;">Categoría:</label>
                <select name="categoria" id="categoria_select" required style="width:100%; padding:10px; border: 1px solid #ccc; border-radius:4px; box-sizing:border-box;" onchange="toggleCamposSuplemento()">
                    <option value="Ropa">Ropa</option>
                    <option value="Suplemento">Suplemento</option>
                </select>
            </div>
            
            <div style="flex: 1;">
                <label for="genero" style="display:block; margin-bottom:5px; font-weight:bold; font-size:14px;">Género / Clasificación:</label>
                <select name="genero" id="genero_select" required style="width:100%; padding:10px; border: 1px solid #ccc; border-radius:4px; box-sizing:border-box;">
                    <option value="Unisex">Unisex / General</option>
                    <option value="Caballero">Caballero</option>
                    <option value="Dama">Dama</option>
                    <option value="Nino">Niño</option>
                </select>
            </div>
        </div>

        <label for="talla" id="label_talla" style="display:block; margin-bottom:5px; font-weight:bold; font-size:14px;">Talla:</label>
        <select name="talla" id="talla_select" style="width:100%; padding:10px; margin-bottom:15px; border: 1px solid #ccc; border-radius:4px; box-sizing:border-box;">
            <option value="Unitalla">Unitalla</option>
            <option value="XS">XS</option>
            <option value="S">S</option>
            <option value="M">M</option>
            <option value="L">L</option>
            <option value="XL">XL</option>
            <option value="XXL">XXL</option>
            <option value="N/A">N/A (Suplementos)</option>
        </select>

        <label for="color" id="label_color" style="display:block; margin-bottom:5px; font-weight:bold; font-size:14px;">Color (Solo letras):</label>
        <input type="text" name="color" id="input_color" placeholder="Ej. Negro o Azul Marino" style="width:100%; padding:10px; margin-bottom:15px; border: 1px solid #ccc; border-radius:4px; box-sizing:border-box;">

        <div id="campos_suplemento" style="display:none; background:#f3f4f6; padding:15px; border-radius:6px; margin-bottom:15px;">
            <label for="fecha_caducidad" style="display:block; margin-bottom:5px; font-weight:bold; font-size:14px; color:#b45309;">Fecha de Caducidad (Requerido para Suplementos):</label>
            <input type="date" name="fecha_caducidad" id="input_caducidad" style="width:100%; padding:10px; margin-bottom:15px; border: 1px solid #ccc; border-radius:4px; box-sizing:border-box;">

            <label for="lote" style="display:block; margin-bottom:5px; font-weight:bold; font-size:14px;">Lote o Sabor (Opcional):</label>
            <input type="text" name="lote" placeholder="Ej. Lote-1425 o Sabor Chocolate" style="width:100%; padding:10px; border: 1px solid #ccc; border-radius:4px; box-sizing:border-box;">
        </div>

        <div style="display: flex; gap: 15px; margin-bottom: 15px;">
            <div style="flex: 1;">
                <label for="cantidad_stock" style="display:block; margin-bottom:5px; font-weight:bold; font-size:14px;">Cantidad Inicial (Stock):</label>
                <input type="number" name="cantidad_stock" min="1" required style="width:100%; padding:10px; border: 1px solid #ccc; border-radius:4px; box-sizing:border-box;">
            </div>
            <div style="flex: 1;">
                <label for="precio" style="display:block; margin-bottom:5px; font-weight:bold; font-size:14px;">Precio de Venta ($ MXN):</label>
                <input type="number" name="precio" step="0.01" min="1" required style="width:100%; padding:10px; border: 1px solid #ccc; border-radius:4px; box-sizing:border-box;">
            </div>
        </div>

        <label for="imagen" style="display:block; margin-bottom:5px; font-weight:bold; font-size:14px;">Fotografía del Producto (Opcional):</label>
        <input type="file" name="imagen" accept="image/*" style="width:100%; padding:10px; margin-bottom:20px; border: 1px dashed #ccc; background:#f9fafb; border-radius:4px; box-sizing:border-box;">

        <button type="submit" style="width:100%; padding:12px; background-color:#111827; color:white; border:none; border-radius:4px; font-size:16px; font-weight:bold; cursor:pointer;">
            Guardar Producto en Inventario
        </button>
    </form>
</div>

<script>
    function toggleCamposSuplemento() {
        const categoria = document.getElementById('categoria_select').value;
        const generoSelect = document.getElementById('genero_select');
        const camposSuplemento = document.getElementById('campos_suplemento');
        const inputCaducidad = document.getElementById('input_caducidad');
        
        const labelTalla = document.getElementById('label_talla');
        const selectTalla = document.getElementById('talla_select');
        const labelColor = document.getElementById('label_color');
        const inputColor = document.getElementById('input_color');

        if (categoria === 'Suplemento') {
            camposSuplemento.style.display = 'block';
            inputCaducidad.setAttribute('required', 'true');
            labelTalla.style.display = 'none';
            selectTalla.style.display = 'none';
            labelColor.style.display = 'none';
            inputColor.style.display = 'none';
            
            generoSelect.value = 'Unisex';
            
        } else {
            camposSuplemento.style.display = 'none';
            inputCaducidad.removeAttribute('required');
            labelTalla.style.display = 'block';
            selectTalla.style.display = 'block';
            labelColor.style.display = 'block';
            inputColor.style.display = 'block';
        }
    }
</script>

</body>
</html>