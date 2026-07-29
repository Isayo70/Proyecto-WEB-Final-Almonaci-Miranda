<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['rol']) && ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2)) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_producto'])) {
    
    $id_producto = (int)$_POST['id_producto'];
    $cantidad = (int)$_POST['cantidad'];
    
    if ($id_producto > 0 && $cantidad > 0) {
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = array();
        }
        
        if (isset($_SESSION['carrito'][$id_producto])) {
            $_SESSION['carrito'][$id_producto] += $cantidad;
        } else {
            $_SESSION['carrito'][$id_producto] = $cantidad;
        }
    }
    
    header("Location: indexcliente");
    exit();
    
} else {
    header("Location: indexcliente");
    exit();
}
?>