<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
    header("Location: index.php");
    exit();
}

include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_producto = intval($_POST['id_producto']);
    $nuevo_stock = intval($_POST['nuevo_stock']);

    if ($id_producto > 0 && $nuevo_stock >= 0) {
        $sql = "UPDATE inventario SET cantidad_stock = ? WHERE id_producto = ?";
        
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("ii", $nuevo_stock, $id_producto);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    header("Location: index.php?mensaje=stock_actualizado");
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>