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

$id_usuario_actual = $_SESSION['id_usuario'] ?? 0;

$sql_t = "SELECT id_turno, fecha_apertura FROM turnos_caja WHERE id_usuario = ? AND estatus = 'Abierto' ORDER BY id_turno DESC LIMIT 1";
$stmt_t = $conexion->prepare($sql_t);
$stmt_t->bind_param("i", $id_usuario_actual);
$stmt_t->execute();
$res_t = $stmt_t->get_result();
$t_actual = $res_t->fetch_assoc();
$stmt_t->close();

$id_turno_activo = $t_actual['id_turno'] ?? 0;

$sql_totales = "SELECT 
    COUNT(id_venta) AS total_operaciones,
    COALESCE(SUM(total), 0) AS gran_total,
    COALESCE(SUM(CASE WHEN tipo_entrega = 'sucursal' THEN total ELSE 0 END), 0) AS total_sucursal,
    COALESCE(SUM(CASE WHEN tipo_entrega = 'domicilio' THEN total ELSE 0 END), 0) AS total_en_linea,
    COALESCE(SUM(CASE WHEN metodo_pago = 'Efectivo' THEN total ELSE 0 END), 0) AS total_efectivo,
    COALESCE(SUM(CASE WHEN metodo_pago = 'Tarjeta' THEN total ELSE 0 END), 0) AS total_tarjeta,
    COALESCE(SUM(CASE WHEN metodo_pago = 'Transferencia' THEN total ELSE 0 END), 0) AS total_transferencia
FROM ventas 
WHERE id_turno = ?";

$stmt_totales = $conexion->prepare($sql_totales);
$stmt_totales->bind_param("i", $id_turno_activo);
$stmt_totales->execute();
$resultado_totales = $stmt_totales->get_result();
$totales = $resultado_totales->fetch_assoc();
$stmt_totales->close();

$sql_ventas_turno = "SELECT id_venta, total, tipo_entrega, metodo_pago, DATE_FORMAT(fecha_venta, '%h:%i %p') AS hora 
                     FROM ventas 
                     WHERE id_turno = ? 
                     ORDER BY id_venta DESC";
$stmt_ventas = $conexion->prepare($sql_ventas_turno);
$stmt_ventas->bind_param("i", $id_turno_activo);
$stmt_ventas->execute();
$resultado_ventas_turno = $stmt_ventas->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Corte de Turno - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>

    <div style="background-color: rgba(255, 255, 255, 0.95); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb;">
        <div style="font-size: 18px; font-weight: bold;">💰 Módulo Financiero (Corte por Turno)</div>
        <a href="index.php" style="color: #007bff; text-decoration: none; font-weight: bold;">Volver al Inicio</a>
    </div>

    <div class="dashboard-container">
        
        <?php if (!$t_actual): ?>
            <div class="aviso-turno">
                ⚠️ Atención: No tienes un turno abierto en este momento. Estás viendo los acumulados generales recientes. Te sugerimos iniciar turno desde tu panel principal.
            </div>
        <?php endif; ?>

        <div class="header-corte">
            <h1>Corte de Caja de tu Turno</h1>
            <p>
                <?php if ($t_actual): ?>
                    Iniciado el: <strong><?= date('d/m/Y h:i A', strtotime($t_actual['fecha_apertura'])) ?></strong> | 
                <?php else: ?>
                    Fecha: <strong><?= date('d / m / Y') ?></strong> | 
                <?php endif; ?>
                Operaciones en este turno: <strong><?= $totales['total_operaciones'] ?></strong>
            </p>
            <div class="gran-total">$<?= number_format($totales['gran_total'], 2) ?></div>
            <span style="background: #e2e8f0; padding: 5px 15px; border-radius: 20px; color: #475569; font-weight: bold;">TOTAL RECAUDADO EN EL TURNO</span>
        </div>

        <div class="grid-cards">
            <div class="card-corte card-efectivo">
                <h3>💵 Efectivo en Caja</h3>
                <div class="monto">$<?= number_format($totales['total_efectivo'], 2) ?></div>
            </div>
            <div class="card-corte card-tarjeta">
                <h3>💳 Pagos con Tarjeta</h3>
                <div class="monto">$<?= number_format($totales['total_tarjeta'], 2) ?></div>
            </div>
            <div class="card-corte card-transferencia">
                <h3>📲 Transferencias</h3>
                <div class="monto">$<?= number_format($totales['total_transferencia'], 2) ?></div>
            </div>
        </div>

        <div class="grid-cards" style="grid-template-columns: 1fr 1fr;">
            <div class="card-corte card-sucursal">
                <h3>🏪 Ventas en Sucursal Física</h3>
                <div class="monto">$<?= number_format($totales['total_sucursal'], 2) ?></div>
            </div>
            <div class="card-corte card-web">
                <h3>🌐 Ventas Tienda en Línea</h3>
                <div class="monto">$<?= number_format($totales['total_en_linea'], 2) ?></div>
            </div>
        </div>

        <div class="tabla-container">
            <h2 style="margin-top: 0; color: #1e293b;">Transacciones Registradas en este Turno</h2>
            
            <?php if ($resultado_ventas_turno && $resultado_ventas_turno->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>FOLIO</th>
                            <th>HORA</th>
                            <th>MÉTODO DE PAGO</th>
                            <th>CANAL DE VENTA</th>
                            <th>MONTO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($venta = $resultado_ventas_turno->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $venta['id_venta'] ?></strong></td>
                                <td style="font-weight: bold; color: #475569;"><?= $venta['hora'] ?></td>
                                <td>
                                    <?php 
                                        if ($venta['metodo_pago'] == 'Efectivo') echo "💵 Efectivo";
                                        elseif ($venta['metodo_pago'] == 'Tarjeta') echo "💳 Tarjeta";
                                        else echo "📲 " . htmlspecialchars($venta['metodo_pago']);
                                    ?>
                                </td>
                                <td><?= $venta['tipo_entrega'] == 'domicilio' ? '🚚 En Línea (Domicilio)' : '🏪 Mostrador / Sucursal' ?></td>
                                <td style="color: #059669; font-weight: bold;">$<?= number_format($venta['total'], 2) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #64748b; padding: 20px;">Aún no se han registrado ventas en este turno.</p>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>