<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'conexion.php'; 
    
    $nombre = trim($_POST['usuario'] ?? '');
    $pass = $_POST['password'] ?? '';
    
    if (empty($nombre) || empty($pass)) {
        header("Location: login?error=campos_vacios");
        exit();
    }

    $sql = "SELECT id_usuario, nombre_usuario, password_hash, id_rol FROM usuarios WHERE nombre_usuario = ?";
    
    if ($stmt = $conexion->prepare($sql)) {
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 1) {
            $usuario_bd = $resultado->fetch_assoc();
            
            if (password_verify($pass, $usuario_bd['password_hash'])) {    
                $_SESSION['usuario'] = $usuario_bd['nombre_usuario'];
                $_SESSION['rol'] = $usuario_bd['id_rol'];
                $_SESSION['id_usuario'] = $usuario_bd['id_usuario']; 
                
                if ($_SESSION['rol'] == 4) {
                    header("Location: indexcliente");
                } else {
                    header("Location: index");
                }
                exit();
                
            } else {
                header("Location: login?error=password_incorrecto");
                exit();
            }
        } else {
            header("Location: login?error=usuario_no_encontrado");
            exit();
        }
        $stmt->close();
    }
} else {
    header("Location: login");
    exit();
}
?>