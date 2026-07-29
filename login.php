<?php
session_start();
if (isset($_SESSION['usuario'])) {
    if (isset($_SESSION['rol']) && $_SESSION['rol'] == 4) {
        header("Location: indexcliente");
    } else {
        header("Location: index");
    }
    exit();
}
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'conexion.php';
    $nombre = trim($_POST['usuario'] ?? '');
    $pass = $_POST['password'] ?? '';
    
    if (!empty($nombre) && !empty($pass)) {
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
                    $error = "Contraseña incorrecta.";
                }
            } else {
                $error = "El usuario no existe.";
            }
            $stmt->close();
        }
    } else {
        $error = "Por favor, completa todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">    
</head>
<body>
    <div class="contenedor-formulario">
        <h2>Iniciar Sesión</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <label for="usuario" style="font-size: 14px; color: #4b5563; font-weight: bold;">Usuario:</label>
            <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required>
            <label for="password" style="font-size: 14px; color: #4b5563; font-weight: bold;">Contraseña:</label>
            <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
            
            <button type="submit">Entrar al Sistema</button>
            <div style="text-align: center; margin-top: 20px;">
                <p style="color: #6b7280; font-size: 14px; margin-bottom: 5px;">¿Eres cliente nuevo?</p>
                <a href="registro_cliente" style="color: #0284c7; font-weight: bold; text-decoration: none; font-size: 14px;">Crear cuenta de Cliente</a>
            </div>
        </form>
    </div>

</body>
</html>