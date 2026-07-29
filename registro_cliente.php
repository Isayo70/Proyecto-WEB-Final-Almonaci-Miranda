<?php
session_start();
include 'conexion.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_real = trim($_POST['nombre_real']);
    $correo = trim($_POST['correo']);
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    if (!empty($nombre_real) && !empty($correo) && !empty($usuario) && !empty($password)) {
        $stmt_check = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE nombre_usuario = ? OR correo = ?");
        $stmt_check->bind_param("ss", $usuario, $correo);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows > 0) {
            $mensaje = "❌ El nombre de usuario o correo ya está en uso.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $rol_cliente = 4; 
            $stmt_insert = $conexion->prepare("INSERT INTO usuarios (nombre_usuario, nombre_real, correo, password_hash, id_rol) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("ssssi", $usuario, $nombre_real, $correo, $hash, $rol_cliente);
            
            if ($stmt_insert->execute()) {
                header("Location: login?mensaje=cuenta_creada");
                exit();
            } else {
                $mensaje = "Error al crear la cuenta en la base de datos.";
            }
        }
    } else {
        $mensaje = "Por favor completa todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Cliente - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>

    <div class="contenedor-formulario" style="margin-top: 5vh;">
        
        <h2 style="text-align: center; color: #0f172a; margin-bottom: 5px; font-size: 1.8em;">Crea tu Cuenta</h2>
        <p style="text-align: center; color: #64748b; font-size: 14px; margin-bottom: 25px; line-height: 1.5;">
            Únete para explorar nuestro catálogo y comprar en línea.
        </p>

        <?php if (!empty($mensaje)): ?>
            <div class="error-msg"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <form action="registro_cliente" method="POST">
            
            <label for="nombre_real">Nombre Completo:</label>
            <input type="text" name="nombre_real" id="nombre_real" required placeholder="Ej. Daniel Puente">

            <label for="correo">Correo Electrónico:</label>
            <input type="email" name="correo" id="correo" required placeholder="daniel@ejemplo.com">

            <label for="usuario">Nombre de Usuario (Para iniciar sesión):</label>
            <input type="text" name="usuario" id="usuario" required placeholder="Ej. daniel_99">

            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password" required placeholder="••••••••">

            <button type="submit" class="btn" style="width: 100%; margin-top: 15px; padding: 12px; font-size: 15px; background-color: #0f172a;">Crear Mi Cuenta</button>
            
        </form>

        <div style="text-align: center; margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
            <p style="font-size: 14px; color: #475569; margin: 0;">¿Ya tienes una cuenta?</p>
            <a href="login" style="color: #0284c7; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 5px;">Inicia sesión aquí →</a>
        </div>
        
    </div>

</body>
</html>