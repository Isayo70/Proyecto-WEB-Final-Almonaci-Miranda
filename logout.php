<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$nombre_usuario = $_SESSION['usuario'];

$sql = "UPDATE turnos_caja 
        SET estatus = 'Cerrado', fecha_cierre = NOW() 
        WHERE estatus = 'Abierto' 
        AND id_usuario = (SELECT id_usuario FROM usuarios WHERE nombre_usuario = ? LIMIT 1)";
        
if ($stmt = $conexion->prepare($sql)) {
    $stmt->bind_param("s", $nombre_usuario);
    $stmt->execute();
    $stmt->close();
}

session_unset();
session_destroy();

if (ini_get("session.use_cookies")){
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header("Location: login.php");
exit();
?>