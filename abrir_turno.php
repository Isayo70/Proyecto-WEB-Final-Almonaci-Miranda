<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['rol']) && $_SESSION['rol'] == 4) {
    header("Location: indexcliente.php");
    exit();
}
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2)) {
    session_destroy();
    header("Location: login.php");
    exit();
}
include 'conexion.php';
$id_usuario = $_SESSION['id_usuario'];
$sql_check = "SELECT id_turno FROM turnos_caja WHERE id_usuario = ? AND estatus = 'Abierto'";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("i", $id_usuario);
$stmt_check->execute();
$res_check = $stmt_check->get_result();
if ($res_check->num_rows > 0) {
    $stmt_check->close();
    echo "<script>alert('Error: Ya tienes un turno activo. Debes cerrarlo antes de abrir uno nuevo.'); window.location='corte_caja.php';</script>";
    exit();
}
$stmt_check->close();
$sql = "INSERT INTO turnos_caja (id_usuario, fecha_apertura, estatus) VALUES (?, NOW(), 'Abierto')";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->close();

header("Location: index.php?mensaje=turno_iniciado");
exit();
?>