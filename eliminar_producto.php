<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
    header("Location: login");
    exit();
}

include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_producto = intval($_POST['id_producto'] ?? 0);

    if ($id_producto > 0) {
        $sql = "DELETE FROM inventario WHERE id_producto = ?";
        
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("i", $id_producto);
            
            try {
                if ($stmt->execute()) {
                    header("Location: index?mensaje=producto_eliminado");
                    exit();
                }
            } catch (mysqli_sql_exception $e) {
                ?>
                <!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <title>Error al eliminar - PruebaTla</title>
                    <link rel="stylesheet" href="Diseñoestilo.css">
                </head>
                <body>
                    <div class="container" style="text-align: center; max-width: 600px; margin-top: 100px;">
                        <h2 style="color: #dc2626; margin-top: 0;">⚠️ No se puede eliminar el producto</h2>
                        <p style="color: #4b5563; font-size: 16px; line-height: 1.5;">
                            Este artículo ya está ligado a ventas anteriores o tickets. Por seguridad del sistema y de tus registros contables, no se puede borrar completamente.
                        </p>
                        <p style="background-color: #f3f4f6; padding: 12px; border-radius: 6px; color: #1f2937;">
                            <strong>Sugerencia:</strong> Usa la opción de editar para poner su stock en 0 o cambiar su estatus.
                        </p>
                        <br>
                        <a href="index" class="btn" style="background: #111827; color: white;">Volver al Inicio</a>
                    </div>
                </body>
                </html>
                <?php
                exit();
            }
            $stmt->close();
        }
    } else {
        header("Location: index");
        exit();
    }
} else {
    header("Location: index");
    exit();
}
?>