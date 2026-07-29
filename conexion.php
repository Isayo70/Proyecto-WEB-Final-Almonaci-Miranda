<?php
$servidor = "localhost";
$usuario = "root";
$password = ""; 
$base_datos = "pruebatla"; 

$conexion = new mysqli($servidor, $usuario, $password, $base_datos);

if ($conexion->connect_error) {
    die("La conexión falló: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
?>