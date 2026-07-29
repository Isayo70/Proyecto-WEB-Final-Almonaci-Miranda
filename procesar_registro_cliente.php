<?php
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario'] ?? '');
    $pass_plana = trim($_POST['contrasena'] ?? '');
    
    $rol_asignado = 4; 

    if (empty($usuario) || empty($pass_plana)) {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Error de Registro - PruebaTla</title>
            <link rel="stylesheet" href="Diseñoestilo.css">
        </head>
        <body>
            <div class="container" style="text-align: center; max-width: 500px; margin-top: 100px;">
                <h2 style="color: #dc2626; margin-top: 0;">⚠️ Error en el Registro</h2>
                <p style="color: #4b5563; font-size: 16px;">Todos los campos son obligatorios.</p>
                <br>
                <a href="registro_cliente" class="btn" style="background: #111827; color: white;">Volver a intentar</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }

    $contrasena_hash = password_hash($pass_plana, PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuarios (nombre_usuario, password_hash, id_rol) VALUES (?, ?, ?)";
    
    if ($stmt = $conexion->prepare($sql)) {
        $stmt->bind_param("ssi", $usuario, $contrasena_hash, $rol_asignado);
        
        try {
            if ($stmt->execute()) {
                header("Location: login?mensaje=cuenta_creada");
                exit();
            }
        } catch (mysqli_sql_exception $e) {
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Usuario Duplicado - PruebaTla</title>
                <link rel="stylesheet" href="Diseñoestilo.css">
            </head>
            <body>
                <div class="container" style="text-align: center; max-width: 500px; margin-top: 100px;">
                    <h2 style="color: #dc2626; margin-top: 0;">⚠️ Nombre de Usuario No Disponible</h2>
                    <p style="color: #4b5563; font-size: 16px; line-height: 1.5;">
                        Lo sentimos, el nombre de usuario <strong>"<?= htmlspecialchars($usuario) ?>"</strong> ya está registrado en el sistema. Por favor, elige uno diferente.
                    </p>
                    <br>
                    <a href="registro_cliente" class="btn" style="background: #111827; color: white;">Intentar con otro nombre</a>
                </div>
            </body>
            </html>
            <?php
            exit();
        }
        $stmt->close();
    } else {
        echo "Error preparando la consulta en la base de datos: " . $conexion->error;
    }
} else {
    header("Location: login");
    exit();
}
?>