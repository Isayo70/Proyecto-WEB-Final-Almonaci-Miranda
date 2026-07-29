<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login");
    exit();
}

include 'conexion.php'; 
$nombre_usuario = $_SESSION['usuario'];

$sql = "CALL obtener_perfil_usuario(?)"; 
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $nombre_usuario);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado && $fila = $resultado->fetch_assoc()) {
    $rol_correcto = $fila['rol'];
    $imagen_correcta = !empty($fila['imagen']) ? $fila['imagen'] : "img/perfil_default.png";

    switch ($rol_correcto) {
        case 'Dueño':
        case 'Empleado':
            break;
        case 'Cliente':
            break;
    }
}

while($conexion->more_results() && $conexion->next_result()) {
    if($extraResult = $conexion->store_result()) {
        $extraResult->free();
    }
}

$stmt->close();
$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobación de Perfil</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>
    <div class="container" style="text-align: center; max-width: 500px;">
        <?php if (isset($rol_correcto)): ?>
            <h2>Bienvenido, <?php echo htmlspecialchars($rol_correcto); ?></h2>
            <p>Usuario: <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong></p>
            
            <div style="margin: 20px 0;">
                <img src="<?php echo htmlspecialchars($imagen_correcta); ?>" alt="Foto de Perfil" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 3px solid #111827;">
            </div>
            
            <br>
            <a href="index" class="btn">Ir al Panel Principal</a>
        <?php else: ?>
            <p style="color: #dc2626; font-weight: bold;">Error: La rutina de la base de datos no devolvió datos para este usuario.</p>
            <a href="login" class="btn">Volver al Login</a>
        <?php endif; ?>
    </div>
</body>
</html>