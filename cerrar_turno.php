<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

$sql_turno = "SELECT id_turno, fecha_apertura FROM turnos_caja WHERE id_usuario = ? AND estatus = 'Abierto' ORDER BY id_turno DESC LIMIT 1";
$stmt_t = $conexion->prepare($sql_turno);
$stmt_t->bind_param("i", $id_usuario);
$stmt_t->execute();
$res_t = $stmt_t->get_result();

if ($turno = $res_t->fetch_assoc()) {
    $id_turno = $turno['id_turno'];
    $fecha_apertura = $turno['fecha_apertura'];
    
    $sql_ventas = "SELECT 
        COALESCE(SUM(total), 0) as total_venta,
        COALESCE(SUM(CASE WHEN metodo_pago = 'Efectivo' THEN total ELSE 0 END), 0) as total_efectivo,
        COALESCE(SUM(CASE WHEN metodo_pago = 'Tarjeta' THEN total ELSE 0 END), 0) as total_tarjeta,
        COALESCE(SUM(CASE WHEN metodo_pago = 'Transferencia' THEN total ELSE 0 END), 0) as total_transferencia
        FROM ventas 
        WHERE id_usuario = ? AND fecha_venta >= ?";
        
    $stmt_v = $conexion->prepare($sql_ventas);
    $stmt_v->bind_param("is", $id_usuario, $fecha_apertura);
    $stmt_v->execute();
    $res_v = $stmt_v->get_result();
    $ventas = $res_v->fetch_assoc();
    $stmt_v->close();
    
    $sql_cierre = "UPDATE turnos_caja 
                   SET estatus = 'Cerrado', 
                       fecha_cierre = NOW(),
                       total_efectivo = ?,
                       total_tarjeta = ?,
                       total_transferencia = ?,
                       total_venta = ?
                   WHERE id_turno = ?";
    $stmt_c = $conexion->prepare($sql_cierre);
    $stmt_c->bind_param("ddddi", $ventas['total_efectivo'], $ventas['total_tarjeta'], $ventas['total_transferencia'], $ventas['total_venta'], $id_turno);
    $stmt_c->execute();
    $stmt_c->close();
}
$stmt_t->close();

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