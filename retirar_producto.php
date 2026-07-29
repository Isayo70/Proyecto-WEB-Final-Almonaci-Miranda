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
    $id_producto = intval($_POST['id_producto'] ?? 0);

    if ($id_producto > 0) {
        $sql = "UPDATE inventario SET estatus = 'Inactivo' WHERE id_producto = ?";
        
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("i", $id_producto);
            
            try {
                if ($stmt->execute()) {
                    $stmt->close();
                    $conexion->close();
                    
                    header("Location: index?mensaje=producto_retirado");
                    exit();
                }
            } catch (mysqli_sql_exception $e) {
                ?>
                <!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <title>Error al Retirar - PruebaTla</title>
                    <link rel="stylesheet" href="Diseñoestilo.css">
                </head>
                <body>
                    <div class="container" style="text-align: center; max-width: 550px; margin-top: 100px;">
                        <h2 style="color: #dc2626; margin-top: 0;">⚠️ Error en la Base de Datos</h2>
                        <p style="color: #4b5563; font-size: 15px; line-height: 1.5;">
                            No se pudo actualizar el estatus del producto en el inventario.
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
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>ID Inválido - PruebaTla</title>
            <link rel="stylesheet" href="Diseñoestilo.css">
        </head>
        <body>
            <div class="container" style="text-align: center; max-width: 500px; margin-top: 100px;">
                <h2 style="color: #dc2626; margin-top: 0;">⚠️ Identificador No Válido</h2>
                <p style="color: #4b5563; font-size: 16px;">El producto seleccionado no cuenta con un ID válido para retirar.</p>
                <br>
                <a href="index" class="btn" style="background: #111827; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;">Volver al Panel</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
} else {
    header("Location: index");
    exit();
}

$conexion->close();
?>