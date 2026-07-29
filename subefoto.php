<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login");
    exit();
}

$carpeta = "imgs/";
$mensaje = "";
$imagen_subida = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES["foto"]) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        
        $nombre_original = basename($_FILES['foto']['name']);
        $nombre_unico = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", $nombre_original);
        $rutadestino = $carpeta . $nombre_unico;

        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        if (move_uploaded_file($_FILES['foto']["tmp_name"], $rutadestino)) {
            $mensaje = "¡Foto subida correctamente al servidor!";
            $imagen_subida = $rutadestino;
        } else {
            $mensaje = "Error al mover la imagen subida.";
        }
    } else {
        $mensaje = "No se ha recibido ninguna imagen válida.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Fotografía - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>

    <div class="container" style="max-width: 500px; margin: 60px auto; text-align: center;">
        <h2 style="color: #111827; margin-top: 0;">Gestor de Carga de Imágenes</h2>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                <p style="font-weight: bold; color: #059669; margin-top: 0;"><?= htmlspecialchars($mensaje) ?></p>
                <?php if (!empty($imagen_subida)): ?>
                    <p style="font-size: 13px; color: #64748b; word-break: break-all;">Ruta: <?= htmlspecialchars($imagen_subida) ?></p>
                    <img src="<?= htmlspecialchars($imagen_subida) ?>" alt="Vista previa" style="max-width: 100%; height: 200px; object-fit: cover; border-radius: 6px; margin-top: 10px; border: 1px solid #cbd5e1;">
                <?php endif; ?>
            </div>
            <a href="subefoto" class="btn" style="background: #0284c7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Subir otra foto</a>
        <?php else: ?>
            <p style="color: #4b5563; font-size: 14px; margin-bottom: 20px;">Selecciona una imagen desde tu dispositivo para almacenarla en el servidor.</p>
            
            <form action="" method="POST" enctype="multipart/form-data" style="text-align: left;">
                <label for="foto" style="font-weight: 600; color: #374151; font-size: 14px; display: block; margin-bottom: 8px;">Seleccione la fotografía:</label>
                <input type="file" name="foto" id="foto" accept="image/*" required style="width: 100%; padding: 10px; border: 1px dashed #cbd5e1; border-radius: 6px; background: #f8fafc; box-sizing: border-box; margin-bottom: 20px; cursor: pointer;">
                
                <button type="submit" class="btn" style="width: 100%; background: #059669; color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px;">Subir Imagen</button>
            </form>
        <?php endif; ?>

        <div style="margin-top: 25px;">
            <a href="index" style="color: #6b7280; text-decoration: none; font-size: 14px; font-weight: bold;">← Volver al Panel Principal</a>
        </div>
    </div>

</body>
</html>