<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login");
    exit();
}

if (isset($_SESSION['rol']) && ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2)) {
    header("Location: index");
    exit();
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 4) {
    header("Location: login");
    exit();
}
include 'conexion.php';
$id_usuario = $_SESSION['id_usuario'];
$sql = "SELECT id_venta, total, tipo_entrega, direccion, estado_envio, paqueteria, numero_guia 
        FROM ventas 
        WHERE id_usuario = ? 
        ORDER BY id_venta DESC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>
    <div style="background-color: rgba(255, 255, 255, 0.95); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb;">
        <div style="font-size: 16px; color: #374151;">
            Hola, <strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong>
        </div>
        <a href="logout" style="color: #dc2626; text-decoration: none; font-weight: bold;">Cerrar Sesión</a>
    </div>
    <div class="pedidos-container">
        <a href="indexcliente" class="btn-volver-catalogo">← Volver al Catálogo</a>
        <h2 style="text-align: center; color: #1e293b; margin-top: 0; margin-bottom: 30px;">Mis Pedidos</h2>
        <?php if ($resultado && $resultado->num_rows > 0): ?>
            <?php while ($pedido = $resultado->fetch_assoc()): ?>
                <div class="pedido-card">
                    <div class="pedido-header">
                        <h3 style="margin: 0; color: #1e293b;">Folio #<?= $pedido['id_venta'] ?></h3>
                        <div>
                           <?php if ($pedido['estado_envio'] === 'Enviado'): ?>
                                <span class="estado-enviado">✅ Enviado</span>
                            <?php elseif ($pedido['estado_envio'] === 'Entregado'): ?>
                                <span class="estado-enviado" style="background-color: #d1fae5; color: #059669;">🛍️ Entregado</span>
                            <?php else: ?>
                                <span class="estado-pendiente">⏳ En preparación</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p style="margin: 5px 0; color: #374151;"><strong>Total pagado:</strong> $<?= number_format($pedido['total'], 2) ?> MXN</p>
                    <?php if ($pedido['tipo_entrega'] === 'sucursal'): ?>
                        <p style="margin: 5px 0; color: #4b5563;">🏪 <strong>Método:</strong> Recoger en Sucursal Central (Aguascalientes)</p>
                        <p style="color: #64748b; font-size: 0.9em; margin-top: 10px;"><em>Presenta tu número de folio en mostrador para recoger tus prendas.</em></p>
                    <?php else: ?>
                        <p style="margin: 5px 0; color: #4b5563;">🚚 <strong>Método:</strong> Envío a Domicilio</p>
                        <p style="margin: 5px 0; color: #4b5563;">📍 <strong>Dirección:</strong> <?= htmlspecialchars($pedido['direccion']) ?></p>
                        <?php if ($pedido['estado_envio'] === 'Enviado'): ?>
                            <div class="rastreo-box">
                                <h4 style="margin-top: 0; margin-bottom: 10px; color: #1e293b;">📦 Información de Rastreo</h4>
                                <p style="margin: 5px 0; color: #374151;"><strong>Paquetería:</strong> <?= htmlspecialchars($pedido['paqueteria']) ?></p>
                                <p style="margin: 5px 0; color: #374151;"><strong>Número de Guía:</strong> <span style="font-size: 1.1em; letter-spacing: 1px; color: #0284c7; font-weight: bold;"><?= htmlspecialchars($pedido['numero_guia']) ?></span></p>
                                <p style="font-size: 0.85em; color: #64748b; margin-bottom: 0; margin-top: 10px;">Ingresa este número en la página web oficial de <?= htmlspecialchars($pedido['paqueteria']) ?> para rastrear tu paquete.</p>
                            </div>
                        <?php else: ?>
                            <p style="color: #64748b; font-size: 0.9em; margin-top: 15px; border-left: 3px solid #d97706; padding-left: 10px;">
                                <em>Tu pedido está siendo empacado por nuestro equipo. Aquí aparecerá tu código de rastreo en cuanto sea entregado a la paquetería.</em>
                            </p>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 40px;">
                <p style="font-size: 1.2em; color: #64748b;">Aún no tienes pedidos registrados.</p>
                <a href="indexcliente" style="color: #0284c7; font-weight: bold; text-decoration: none;">¡Haz tu primera compra!</a>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>