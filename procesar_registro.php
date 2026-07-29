<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login");
    exit();
}
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
    if (isset($_SESSION['rol']) && $_SESSION['rol'] == 4) {
        header("Location: indexcliente"); 
    } else {
        header("Location: index"); 
    }
    exit();
}
include 'conexion.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $username = trim($_POST['username'] ?? '');
    $password_raw = $_POST['password'] ?? '';
    $id_rol = intval($_POST['id_rol'] ?? 2); 
    
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $curp = trim($_POST['curp'] ?? '');
    $matricula = trim($_POST['matricula'] ?? '');
    $ine = trim($_POST['ine'] ?? '');
    $ecivil = trim($_POST['ecivil'] ?? '');
    
    $sueldo = floatval($_POST['sueldo'] ?? 0);
    $nss = trim($_POST['nss'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
    $fecha_fin = !empty($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : null;
    
    $rfc = trim($_POST['rfc'] ?? '');
    $clabe = trim($_POST['clabe'] ?? '');
    $cuenta = trim($_POST['cuenta'] ?? '');
    $banco = trim($_POST['banco'] ?? '');
    
    $domicilio = trim($_POST['domicilio'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($username) || empty($password_raw) || empty($nombre) || empty($matricula)) {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Error de Registro - PruebaTla</title>
            <link rel="stylesheet" href="Diseñoestilo.css">
        </head>
        <body>
            <div class="container" style="text-align: center; max-width: 550px; margin-top: 80px;">
                <h2 style="color: #dc2626; margin-top: 0;">⚠️ Faltan Campos Obligatorios</h2>
                <p style="color: #4b5563; font-size: 15px;">Por favor, completa al menos el usuario, contraseña, nombre y matrícula del empleado.</p>
                <br>
                <a href="index" class="btn" style="background: #111827; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;">Volver al Panel</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }

    $password = password_hash($password_raw, PASSWORD_DEFAULT);

    $sql = "CALL sp_registrar_empleado_completo(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);

    if ($stmt === false) {
        die("Error preparando la consulta en el servidor: " . $conexion->error);
    }

    $stmt->bind_param("ssissssssdsssssssssss", 
        $username, $password, $id_rol,
        $nombre, $apellidos, $curp, $matricula, $ine, $ecivil,
        $sueldo, $nss, $area, $fecha_inicio, $fecha_fin,
        $rfc, $clabe, $cuenta, $banco,
        $domicilio, $telefono, $email
    );

    try {
        if ($stmt->execute()) {
            while($conexion->more_results() && $conexion->next_result()) {
                if($extraResult = $conexion->store_result()) {
                    $extraResult->free();
                }
            }
            $stmt->close();
            $conexion->close();

            header("Location: index?mensaje=empleado_registrado");
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Error en Base de Datos - PruebaTla</title>
            <link rel="stylesheet" href="Diseñoestilo.css">
        </head>
        <body>
            <div class="container" style="text-align: center; max-width: 600px; margin-top: 80px;">
                <h2 style="color: #dc2626; margin-top: 0;">⚠️ No se pudo registrar al empleado</h2>
                <p style="color: #4b5563; font-size: 15px; line-height: 1.5;">
                    Ocurrió un conflicto al guardar los datos (es posible que el nombre de usuario, CURP o matrícula ya estén dados de alta en el sistema).
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
}
$conexion->close();
?>