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
$mensaje_titulo = "";
$mensaje_detalle = "";
$tipo_estado = "";
$datos_debug = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nombre = trim($_POST['nombre_usuario'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $rol    = intval($_POST['rol'] ?? 0); 

    if ($nombre === '' || $pass === '' || $rol <= 0) {
        $mensaje_titulo = "⚠️ Campos Incompletos";
        $mensaje_detalle = "Faltan campos obligatorios en el formulario (Usuario, Contraseña o Rol).";
        $tipo_estado = "error";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (nombre_usuario, password_hash, id_rol) VALUES (?, ?, ?)";
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("ssi", $nombre, $hash, $rol);
            try {
                if ($stmt->execute()) {
                    $mensaje_titulo = "✅ ¡Usuario Guardado con Éxito!";
                    $mensaje_detalle = "El usuario <strong>" . htmlspecialchars($nombre) . "</strong> ha sido registrado correctamente en el sistema.";
                    $tipo_estado = "exito";
                }
            } catch (mysqli_sql_exception $e) {
                $mensaje_titulo = "⚠️ Error al Guardar en Base de Datos";
                $mensaje_detalle = "Es posible que el nombre de usuario ya exista. Detalle técnico: " . $e->getMessage();
                $tipo_estado = "error";
            }
            $stmt->close();
        } else {
            $mensaje_titulo = "⚠️ Error de Consulta SQL";
            $mensaje_detalle = $conexion->error;
            $tipo_estado = "error";
        }
    }
} else {
    $mensaje_titulo = "⚠️ Acceso No Autorizado";
    $mensaje_detalle = "La página no recibió datos por el método POST (probablemente entraste de forma directa por la URL).";
    $tipo_estado = "error";
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Usuario - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>

    <div class="container" style="max-width: 600px; margin: 80px auto; text-align: center;">
        <h2 style="color: <?= $tipo_estado === 'exito' ? '#059669' : '#dc2626' ?>; margin-top: 0;">
            <?= $mensaje_titulo ?>
        </h2>
        
        <p style="color: #4b5563; font-size: 16px; line-height: 1.5; margin: 20px 0;">
            <?= $mensaje_detalle ?>
        </p>

        <br>
        <a href="index" class="btn" style="background: #111827; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
            Volver al Panel Principal
        </a>
    </div>

</body>
</html>