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
    $id_producto = (int)$_POST['id_producto'];
    $nuevo_precio = (float)$_POST['nuevo_precio'];
    if ($nuevo_precio >= 0 && $id_producto > 0) {
        $sql = "UPDATE inventario SET precio = ? WHERE id_producto = ?";
        
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("di", $nuevo_precio, $id_producto);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    header("Location: index?mensaje=promocion_aplicada");
    exit();
    
} else {
    header("Location: index");
    exit();
}
?>