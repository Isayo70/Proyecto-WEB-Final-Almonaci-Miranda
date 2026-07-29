<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 1) {
    header("Location: index.php");
    exit();
}
include 'conexion.php';
$sql_cortes = "SELECT 
                    t.id_turno, 
                    u.nombre_usuario, 
                    t.fecha_apertura, 
                    t.fecha_cierre, 
                    t.estatus,
                    t.total_efectivo,
                    t.total_tarjeta,
                    t.total_venta,
                    (SELECT SUM(v.total) FROM ventas v WHERE v.id_usuario = t.id_usuario AND v.fecha_venta >= t.fecha_apertura) as ventas_en_curso
                FROM turnos_caja t
                JOIN usuarios u ON t.id_usuario = u.id_usuario
                ORDER BY t.fecha_apertura DESC";

$resultado_cortes = mysqli_query($conexion, $sql_cortes);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Cortes de Caja - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
    <style>
        .badge-abierto { background-color: #d1fae5; color: #065f46; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge-cerrado { background-color: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .monto-principal { font-weight: bold; font-size: 16px; color: #059669; }
        .desglose { font-size: 11px; color: #64748b; margin-top: 4px; font-weight: normal; }
    </style>
</head>
<body style="background-color: #f8fafc; font-family: Arial, sans-serif;">

<div class="container" style="max-width: 1100px; margin: 40px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #1e293b;">📊 Historial de Cortes por Empleado</h2>
        <a href="index.php" style="background-color: #4f46e5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold;">← Volver al Panel</a>
    </div>
    
    <p style="color: #64748b; margin-bottom: 30px;">Supervisa el rendimiento y los cierres de caja de todos tus vendedores.</p>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background-color: #f1f5f9; color: #334155; border-bottom: 2px solid #cbd5e1;">
                    <th style="padding: 15px 10px;">ID TURNO</th>
                    <th style="padding: 15px 10px;">EMPLEADO</th>
                    <th style="padding: 15px 10px;">APERTURA</th>
                    <th style="padding: 15px 10px;">CIERRE</th>
                    <th style="padding: 15px 10px;">ESTATUS</th>
                    <th style="padding: 15px 10px; text-align: right;">TOTAL VENDIDO (Desglose)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado_cortes && mysqli_num_rows($resultado_cortes) > 0): ?>
                    <?php while($corte = mysqli_fetch_assoc($resultado_cortes)): ?>
                        
<?php 
                        if ($corte['estatus'] == 'Cerrado') {
                            $total_mostrar = $corte['total_venta'] ?? 0;
                            $efectivo = $corte['total_efectivo'] ?? 0;
                            $tarjeta = $corte['total_tarjeta'] ?? 0;
                            $transferencia = $corte['total_transferencia'] ?? 0;
                        } else {
                            $total_mostrar = $corte['ventas_en_curso'] ?? 0;
                        }
                        ?>

                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 15px 10px; font-weight: bold; color: #64748b;">#<?= $corte['id_turno'] ?></td>
                            <td style="padding: 15px 10px; font-weight: bold; color: #0f172a;">👤 <?= htmlspecialchars($corte['nombre_usuario']) ?></td>
                            <td style="padding: 15px 10px; font-size: 14px; color: #475569;"><?= date("d/m/Y h:i A", strtotime($corte['fecha_apertura'])) ?></td>
                            <td style="padding: 15px 10px; font-size: 14px; color: #475569;">
                                <?= $corte['fecha_cierre'] ? date("d/m/Y h:i A", strtotime($corte['fecha_cierre'])) : '<span style="color:#b45309;">Turno en curso...</span>' ?>
                            </td>
                            <td style="padding: 15px 10px;">
                                <?php if ($corte['estatus'] == 'Abierto'): ?>
                                    <span class="badge-abierto">🟢 Abierto</span>
                                <?php else: ?>
                                    <span class="badge-cerrado">🔴 Cerrado</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 10px; text-align: right;">
                                <div class="monto-principal">$<?= number_format($total_mostrar, 2) ?> MXN</div>
                                
                                <?php if ($corte['estatus'] == 'Cerrado' && $total_mostrar > 0): ?>
                                    <div class="desglose">
                                        Efec: $<?= number_format($efectivo, 2) ?> | Tarj: $<?= number_format($tarjeta, 2) ?> | Transf: $<?= number_format($transferencia, 2) ?>
                                    </div>
                                <?php elseif ($corte['estatus'] == 'Abierto'): ?>
                                    <div class="desglose" style="color: #b45309;">(Calculando en vivo...)</div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="padding: 30px; text-align: center; color: #64748b;">No hay turnos registrados en el sistema aún.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>