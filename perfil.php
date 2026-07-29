<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login");
    exit();
}

include 'conexion.php'; 
$nombre_usuario = $_SESSION['usuario'];
$carpeta = "imgs/";
$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES["foto"])) {
    if ($_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $nombre_archivo = time() . "_" . basename($_FILES['foto']['name']); // Evitamos nombres duplicados
        $rutadestino = $carpeta . $nombre_archivo;
        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0777, true);
        }
        if (move_uploaded_file($_FILES['foto']["tmp_name"], $rutadestino)) {
            $sql_update = "UPDATE usuarios SET imagen = ? WHERE nombre_usuario = ?"; 
            $stmt_update = $conexion->prepare($sql_update);
            $stmt_update->bind_param("ss", $rutadestino, $nombre_usuario);
            
            if ($stmt_update->execute()) {
                $mensaje = "¡Foto de perfil actualizada con éxito!";
                $tipo_mensaje = "exito";
            } else {
                $mensaje = "Error al actualizar la base de datos.";
                $tipo_mensaje = "error";
            }
            $stmt_update->close();
        } else {
            $mensaje = "Error al mover la imagen subida al servidor.";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "Por favor, selecciona una imagen válida.";
        $tipo_mensaje = "error";
    }
}

while($conexion->more_results() && $conexion->next_result()) {
    $extraResult = $conexion->use_result();
    if($extraResult instanceof mysqli_result){
        $extraResult->free();
    }
}

$sql = "CALL obtener_perfil_usuario(?)"; 
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $nombre_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$rol_correcto = "Desconocido";
$imagen_correcta = "imgs/default.jpeg"; 

if ($resultado && $fila = $resultado->fetch_assoc()) {
    $rol_correcto = $fila['rol'];
    
    if (!empty($fila['imagen']) && file_exists($fila['imagen'])) {
        $imagen_correcta = $fila['imagen']; 
    }
} else {
    $mensaje = "Error: La rutina de la base de datos no devolvió datos para este usuario.";
    $tipo_mensaje = "error";
}

$stmt->close();
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>

    <div style="background-color: rgba(255, 255, 255, 0.95); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb;">
        <div style="font-size: 16px; color: #374151;">
            👤 Configuración de Cuenta
        </div>
        <div>
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 4): ?>
                <a href="indexcliente" style="color: #0284c7; text-decoration: none; font-weight: bold; margin-right: 20px;">Volver al Catálogo</a>
            <?php else: ?>
                <a href="index" style="color: #0284c7; text-decoration: none; font-weight: bold; margin-right: 20px;">Volver al Inicio</a>
            <?php endif; ?>
            <a href="logout" style="color: #dc2626; text-decoration: none; font-weight: bold;">Cerrar Sesión</a>
        </div>
    </div>

    <div class="perfil-container">
        <h2 style="margin-top: 0; color: #111827;">Mi Perfil</h2>

        <?php if (!empty($mensaje)): ?>
            <div class="<?= $tipo_mensaje === 'exito' ? 'alerta-exito' : 'alerta-error' ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <div>
            <?php 
            switch ($rol_correcto) {
                case 'Dueño':
                    echo "<h3 style='color: #1e293b;'>Bienvenido Dueño</h3>";
                    break;
                case 'Empleado':
                    echo "<h3 style='color: #1e293b;'>Bienvenido Empleado</h3>";
                    break;
                case 'Cliente':
                    echo "<h3 style='color: #1e293b;'>Bienvenido Cliente</h3>";
                    break;
                default:
                    echo "<h3 style='color: #64748b;'>Tipo de usuario no reconocido</h3>";
                    break;
            }
            ?>
            <p style="color: #4b5563; margin-top: -5px;">Usuario: <strong><?= htmlspecialchars($nombre_usuario) ?></strong></p>
            
            <img src="<?= htmlspecialchars($imagen_correcta) ?>" alt="Perfil" class="perfil-imagen">
        </div>

        <div class="perfil-form-box">
            <h3 style="margin-top: 0; color: #1e293b; font-size: 1.1em; margin-bottom: 15px;">Cambiar Foto de Perfil</h3>
            <form action="" method="POST" enctype="multipart/form-data">
                <label for="foto" style="display: block; text-align: left; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">Selecciona una nueva imagen:</label>
                <input type="file" name="foto" id="foto" accept="image/*" required style="margin-bottom: 20px;">
                
                <button type="submit" class="btn-actualizar-foto">Actualizar Fotografía</button>
            </form>
        </div>
    </div>

</body>
</html>