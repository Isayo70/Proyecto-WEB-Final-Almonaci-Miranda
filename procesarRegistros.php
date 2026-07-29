<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login");
    exit();
}
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
    if (isset($_SESSION['rol']) && $_SESSION['rol'] == 4) {
        header("Location: indexcliente");
        exit();
    } else {
        header("Location: index");
        exit();
    }
}
include 'conexion.php';
$usuario      = trim($_POST['usuario'] ?? '');
$pass_plana   = trim($_POST['contrasena'] ?? '');
$estatus      = trim($_POST['estatus'] ?? '');
$tipo_usuario = trim($_POST['tipo_usuario'] ?? '');
$estado       = trim($_POST['estado'] ?? '');
if ($usuario === '' || $pass_plana === '') {
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
            <h2 style="color: #dc2626; margin-top: 0;">⚠️ Campos Obligatorios</h2>
            <p style="color: #4b5563; font-size: 16px;">El usuario y la contraseña son campos obligatorios.</p>
            <br>
            <a href="index" class="btn" style="background: #111827; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;">Volver al Panel</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$contrasena = password_hash($pass_plana, PASSWORD_DEFAULT);

$sql = "INSERT INTO pruebas (usuario, password, estatus, tipo_usuario, estado) VALUES (?, ?, ?, ?, ?)";

if ($stmt = $conexion->prepare($sql)) {
    $stmt->bind_param("sssss", $usuario, $contrasena, $estatus, $tipo_usuario, $estado);

    try {
        if ($stmt->execute()) {
            $nuevo_id = $stmt->insert_id;
            $stmt->close();
            $conexion->close();
            
            header("Location: index?mensaje=registro_prueba_exitoso&id=" . $nuevo_id);
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Error de Base de Datos - PruebaTla</title>
            <link rel="stylesheet" href="Diseñoestilo.css">
        </head>
        <body>
            <div class="container" style="text-align: center; max-width: 600px; margin-top: 100px;">
                <h2 style="color: #dc2626; margin-top: 0;">⚠️ Error en el Registro</h2>
                <p style="color: #4b5563; font-size: 15px; line-height: 1.5;">
                    No se pudo completar la inserción en la tabla de pruebas (es probable que el usuario ya exista).
                </p>
                <p style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 6px; font-family: monospace; font-size: 13px;">
                    <?= htmlspecialchars($e->getMessage()) ?>
                </p>
                <br>
                <a href="index" class="btn" style="background: #111827; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;">Volver al Panel</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
    
    $stmt->close();
} else {
    die("Error al preparar la consulta en la base de datos: " . $conexion->error);
}

$conexion->close();
?>