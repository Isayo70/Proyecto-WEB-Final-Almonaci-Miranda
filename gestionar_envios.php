<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login");
    exit();
}

if (isset($_SESSION['rol']) && $_SESSION['rol'] == 4) {
    header("Location: indexcliente");
    exit();
}

if (!isset($_SESSION['rol']) || ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2)) {
    header("Location: login");
    exit();
}

include 'conexion.php';

$mensaje_exito = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['id_venta_domicilio'])) {
        $id_venta = intval($_POST['id_venta_domicilio']);
        $paqueteria = trim($_POST['paqueteria']);
        $numero_guia = trim($_POST['numero_guia']);
        $nuevo_estado = 'Enviado';

        if ($id_venta > 0 && !empty($paqueteria) && !empty($numero_guia)) {
            $sql_update = "UPDATE ventas SET estado_envio = ?, paqueteria = ?, numero_guia = ? WHERE id_venta = ?";
            $stmt = $conexion->prepare($sql_update);
            $stmt->bind_param("sssi", $nuevo_estado, $paqueteria, $numero_guia, $id_venta);
            
            if ($stmt->execute()) {
                $mensaje_exito = "¡La guía para la venta #$id_venta se registró correctamente!";
            }
            $stmt->close();
        }
    }
    
    if (isset($_POST['id_venta_sucursal'])) {
        $id_venta = intval($_POST['id_venta_sucursal']);
        $nuevo_estado = 'Entregado';

        if ($id_venta > 0) {
            $sql_update = "UPDATE ventas SET estado_envio = ? WHERE id_venta = ?";
            $stmt = $conexion->prepare($sql_update);
            $stmt->bind_param("si", $nuevo_estado, $id_venta);
            
            if ($stmt->execute()) {
                $mensaje_exito = "¡La venta #$id_venta fue marcada como entregada en mostrador!";
            }
            $stmt->close();
        }
    }
}

$sql_domicilio = "SELECT id_venta, total, direccion FROM ventas WHERE tipo_entrega = 'domicilio' AND estado_envio = 'Pendiente' ORDER BY id_venta ASC";
$resultado_domicilio = $conexion->query($sql_domicilio);

$sql_sucursal = "SELECT id_venta, total FROM ventas WHERE tipo_entrega = 'sucursal' AND estado_envio = 'Pendiente' ORDER BY id_venta ASC";
$resultado_sucursal = $conexion->query($sql_sucursal);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Envíos - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>

    <div style="background-color: rgba(255, 255, 255, 0.95); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb;">
        <div style="font-size: 18px; font-weight: bold;">📦 Panel de Logística y Envíos</div>
        <a href="index" style="color: #007bff; text-decoration: none; font-weight: bold;">Volver al Inicio</a>
    </div>

    <div class="panel-container">
        
        <?php if (!empty($mensaje_exito)): ?>
            <div class="alerta-exito"><?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>

        <h2 class="seccion-titulo">🚚 Pedidos a Domicilio (Pendientes)</h2>
        <?php if ($resultado_domicilio && $resultado_domicilio->num_rows > 0): ?>
            <?php while ($pedido = $resultado_domicilio->fetch_assoc()): ?>
                <div class="pedido-card">
                    <div class="info-pedido">
                        <h3 style="margin-top: 0; color: #0284c7;">Venta #<?= $pedido['id_venta'] ?></h3>
                        <p><strong>Total pagado:</strong> $<?= number_format($pedido['total'], 2) ?></p>
                        <p><strong>Dirección de Entrega:</strong><br> 
                           <span style="background: #fef3c7; padding: 6px 10px; border-radius: 4px; display: inline-block; margin-top: 5px; color: #92400e;">📍 <?= htmlspecialchars($pedido['direccion']) ?></span>
                        </p>
                    </div>
                    <div class="form-envio">
                        <h4 style="margin-top: 0; color: #1e293b;">Registrar Despacho</h4>
                        <form action="" method="POST">
                            <input type="hidden" name="id_venta_domicilio" value="<?= $pedido['id_venta'] ?>">
                            <label style="font-size: 14px; color: #475569;">Paquetería:</label>
                            <select name="paqueteria" required>
                                <option value="">Selecciona una empresa...</option>
                                <option value="Estafeta">Estafeta</option>
                                <option value="FedEx">FedEx</option>
                                <option value="DHL">DHL</option>
                                <option value="Redpack">Redpack</option>
                            </select>
                            <label style="font-size: 14px; color: #475569;">Número de Rastreo (Guía):</label>
                            <input type="text" name="numero_guia" placeholder="Ej. 1234567890" required>
                            <button type="submit" class="btn-enviar" onclick="return confirm('¿Confirmas el envío de este paquete?');">Confirmar Envío</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color: #6b7280;">No hay pedidos a domicilio pendientes en este momento.</p>
        <?php endif; ?>

        <h2 class="seccion-titulo">🏪 Entregas en Sucursal (Pendientes)</h2>
        <?php if ($resultado_sucursal && $resultado_sucursal->num_rows > 0): ?>
            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                <?php while ($pedido = $resultado_sucursal->fetch_assoc()): ?>
                    <div class="pedido-card" style="width: calc(50% - 30px); flex-direction: column;">
                        <h3 style="margin-top: 0; color: #059669;">Folio #<?= $pedido['id_venta'] ?></h3>
                        <p><strong>Total a pagar / pagado:</strong> $<?= number_format($pedido['total'], 2) ?></p>
                        <p style="color: #64748b; font-size: 0.9em;">Esperando al cliente en mostrador para retiro en sucursal.</p>
                        
                        <form action="" method="POST" style="margin-top: auto;">
                            <input type="hidden" name="id_venta_sucursal" value="<?= $pedido['id_venta'] ?>">
                            <button type="submit" class="btn-entregar" onclick="return confirm('¿El cliente ya recogió su pedido en sucursal?');">
                                ✔️ Marcar como Entregado
                            </button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="color: #6b7280;">No hay entregas pendientes en sucursal.</p>
        <?php endif; ?>

    </div>
</body>
</html>