<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['rol']) && $_SESSION['rol'] == 4) {
    header("Location: indexcliente.php");
    exit();
}

if (!isset($_SESSION['rol']) || ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2)) {
    session_destroy(); 
    header("Location: login.php");
    exit();
}
?>
<?php
include 'conexion.php';

$nombre_usuario = htmlspecialchars($_SESSION['usuario']);
$rol_usuario = 'Desconocido';
$imagen_usuario = 'img/perfil_default.png';

$sql_perfil = "CALL obtener_perfil_usuario(?)"; 
$stmt = $conexion->prepare($sql_perfil);
$stmt->bind_param("s", $nombre_usuario);
$stmt->execute();
$resultado_perfil = $stmt->get_result();

if ($resultado_perfil && $fila = $resultado_perfil->fetch_assoc()) {
    $rol_usuario = $fila['rol'];
    if (!empty($fila['imagen'])) {
        $imagen_usuario = $fila['imagen']; 
    }
}
$stmt->close();

while($conexion->more_results() && $conexion->next_result()) {
    if($extraResult = $conexion->store_result()) {
        $extraResult->free();
    }
}

$id_usuario_actual = $_SESSION['id_usuario'] ?? 0;
$sql_turno_activo = "SELECT * FROM turnos_caja WHERE id_usuario = ? AND estatus = 'Abierto' LIMIT 1";
$stmt_ta = $conexion->prepare($sql_turno_activo);
$stmt_ta->bind_param("i", $id_usuario_actual);
$stmt_ta->execute();
$resultado_ta = $stmt_ta->get_result();
$turno_activo = $resultado_ta->fetch_assoc();
$stmt_ta->close();

$sql_inventario = "SELECT id_producto, nombre_producto, categoria, talla, color, cantidad_stock, precio, estatus, fecha_caducidad, lote FROM inventario";
$resultado_inventario = mysqli_query($conexion, $sql_inventario);

$sql_usuarios = "SELECT u.id_usuario, u.nombre_usuario, i.nombre, i.apellidos, i.curp, i.matricula, i.ine, i.estado_civil 
                 FROM usuarios u 
                 LEFT JOIN usuarios_identidad i ON u.id_usuario = i.id_usuario";
$resultado_usuarios = mysqli_query($conexion, $sql_usuarios);

$sql_alertas = "SELECT id_producto, nombre_producto, talla, cantidad_stock 
                FROM inventario 
                WHERE cantidad_stock <= 5 
                ORDER BY cantidad_stock ASC";
$resultado_alertas = mysqli_query($conexion, $sql_alertas);

$sql_caducidad = "SELECT id_producto, nombre_producto, fecha_caducidad, lote, cantidad_stock, precio 
                  FROM inventario 
                  WHERE categoria = 'Suplemento' AND fecha_caducidad IS NOT NULL AND fecha_caducidad <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
                  ORDER BY fecha_caducidad ASC";
$resultado_caducidad = mysqli_query($conexion, $sql_caducidad);

$sql_ventas = "SELECT v.id_venta, u.nombre_usuario, v.total, v.tipo_entrega, v.direccion, v.estado_envio 
               FROM ventas v 
               LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario 
               ORDER BY v.id_venta DESC";
$resultado_ventas = mysqli_query($conexion, $sql_ventas);

$sql_turno_activo_global = "SELECT fecha_apertura FROM turnos_caja WHERE estatus = 'Abierto' ORDER BY id_turno DESC LIMIT 1";
$res_turno = mysqli_query($conexion, $sql_turno_activo_global);

if ($res_turno && mysqli_num_rows($res_turno) > 0) {
    $turno = mysqli_fetch_assoc($res_turno);
    $inicio_turno = $turno['fecha_apertura'];
    $sql_ventas_hoy = "SELECT SUM(total) as total_hoy, COUNT(id_venta) as pedidos_hoy 
                       FROM ventas 
                       WHERE fecha_venta >= '$inicio_turno'";
} else {
    $sql_ventas_hoy = "SELECT SUM(total) as total_hoy, COUNT(id_venta) as pedidos_hoy 
                       FROM ventas 
                       WHERE DATE(fecha_venta) = CURDATE()";
}

$res_ventas_hoy = mysqli_query($conexion, $sql_ventas_hoy);
$datos_hoy = mysqli_fetch_assoc($res_ventas_hoy);
$ingresos_hoy = $datos_hoy['total_hoy'] ?? 0;
$pedidos_hoy = $datos_hoy['pedidos_hoy'] ?? 0;
$sql_ventas_mes = "SELECT SUM(total) as total_mes 
                   FROM ventas 
                   WHERE MONTH(fecha_venta) = MONTH(CURDATE()) 
                   AND YEAR(fecha_venta) = YEAR(CURDATE())";
$res_ventas_mes = mysqli_query($conexion, $sql_ventas_mes);
$datos_mes = mysqli_fetch_assoc($res_ventas_mes);
$ingresos_mes = $datos_mes['total_mes'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario - Punto de Venta</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>

    <div class="navbar">
        <div class="navbar-user">
            <span>Usuario: <strong><?php echo $nombre_usuario; ?></strong> (<?php echo $rol_usuario; ?>)</span>
            <img src="<?php echo htmlspecialchars($imagen_usuario); ?>" alt="Foto de perfil" class="perfil-img">
        </div>
        <a href="logout.php" class="btn-logout" onclick="return confirm('¿Seguro que quieres salir del sistema?');">Cerrar Sesión</a>
    </div>

    <div class="container">
        <h1>Hola <?php echo $nombre_usuario; ?></h1>
        <p class="subtitle">Panel de control principal del sistema</p>

        <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] == 1 || $rol_usuario === 'Dueño')): ?>
        <div class="admin-box">
            <h3 style="margin-top: 0;">Opciones de Administrador:</h3>
            <a href="registrar_producto.php" class="btn">Registrar Nuevo Producto</a>
            <a href="registro_usuario.php" class="btn">Registrar Nuevo Usuario</a>
            
            <a href="historial_cortes.php" class="btn" style="background-color: #8b5cf6; color: white;">📋 Ver Cortes de Empleados</a>

            <h2 style="margin-top: 20px;">Resumen Financiero</h2>
            <div class="dashboard-cards" style="display: flex; gap: 15px; margin-bottom: 20px;">
                <div class="card" style="background: #f3f4f6; padding: 15px; border-radius: 6px; flex: 1;">
                    <div class="card-title">💰 Ingresos de Hoy</div>
                    <p class="card-value" style="font-size: 20px; font-weight: bold; color: #059669;">$<?php echo number_format($ingresos_hoy, 2); ?></p>
                </div>
                <div class="card blue" style="background: #f3f4f6; padding: 15px; border-radius: 6px; flex: 1;">
                    <div class="card-title">📦 Pedidos de Hoy</div>
                    <p class="card-value" style="font-size: 20px; font-weight: bold; color: #0369a1;"><?php echo $pedidos_hoy; ?></p>
                </div>
                <div class="card purple" style="background: #f3f4f6; padding: 15px; border-radius: 6px; flex: 1;">
                    <div class="card-title">📈 Ingresos del Mes</div>
                    <p class="card-value" style="font-size: 20px; font-weight: bold; color: #7c3aed;">$<?php echo number_format($ingresos_mes, 2); ?></p>
                </div>
            </div>
        </div>

        <h2>Lista de Usuarios Registrados</h2>
        <div style="overflow-x: auto; margin-bottom: 40px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>USUARIO</th>
                        <th>NOMBRE COMPLETO</th>
                        <th>CURP</th>
                        <th>MATRÍCULA</th>
                        <th>INE</th>
                        <th>ESTADO CIVIL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($resultado_usuarios && mysqli_num_rows($resultado_usuarios) > 0) {
                        $contador = 1; 
                        while($fila_u = mysqli_fetch_assoc($resultado_usuarios)) {
                            echo "<tr>";
                            echo "<td>" . $contador . "</td>"; 
                            echo "<td><strong>" . htmlspecialchars($fila_u['nombre_usuario']) . "</strong></td>";
                            $nombre_completo = trim(($fila_u['nombre'] ?? '') . ' ' . ($fila_u['apellidos'] ?? ''));
                            if (!empty($nombre_completo)) {
                                echo "<td>" . htmlspecialchars($nombre_completo) . "</td>";
                                echo "<td>" . htmlspecialchars($fila_u['curp'] ?? '-') . "</td>";
                                echo "<td>" . htmlspecialchars($fila_u['matricula'] ?? '-') . "</td>";
                                echo "<td>" . htmlspecialchars($fila_u['ine'] ?? '-') . "</td>";
                                echo "<td>" . htmlspecialchars($fila_u['estado_civil'] ?? '-') . "</td>";
                            } else {
                                echo "<td colspan='5' class='texto-gris'>Perfil de identidad sin completar</td>";
                            }
                            echo "</tr>";
                            
                            $contador++; 
                        }
                    } else {
                        echo "<tr><td colspan='7'>No hay usuarios registrados.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2)): ?>
        
        <div class="empleado-box">
            <h3 style="margin-top: 0; color: #065f46;">Control de Turno y Operación:</h3>
            
            <?php if (!$turno_activo): ?>
                <form action="abrir_turno.php" method="POST" style="margin-top: 10px;">
                    <button type="submit" class="btn" style="background-color: #059669; color: white; width: 100%;">🟢 Iniciar Turno (Abrir Caja)</button>
                </form>
            <?php else: ?>
                <p style="color: #047857; font-weight: bold;">Turno activo desde las: <?= date('h:i A', strtotime($turno_activo['fecha_apertura'])) ?></p>
                <a href="nueva_venta.php" class="btn btn-verde" style="margin-bottom: 10px; display: block; text-align: center;">Registrar Nueva Venta</a>
                <a href="corte_caja.php" class="btn" style="background-color: #f59e0b; color: white; display: block; text-align: center; margin-bottom: 10px;">📊 Ver Corte de mi Turno</a>
                <form action="cerrar_turno.php" method="POST">
                    <input type="hidden" name="id_turno" value="<?php echo $turno_activo['id_turno']; ?>">
                    <button type="submit" class="btn" style="background-color: #dc2626; color: white; width: 100%; text-align: center; margin-bottom: 10px;" onclick="return confirm('¿Seguro que deseas cerrar turno y salir del sistema?');">🔴 Cerrar Turno (Salida)</button>
                </form>
            <?php endif; ?>
            <a href="gestionar_envios.php" class="btn" style="background-color: #0369a1; color: white; margin-top: 10px; display: block; text-align: center;">📦 Panel de Envíos y Entregas</a>
        </div>
        <?php endif; ?>
        <h2>Últimos Pedidos / Historial de Ventas</h2>
        <div style="background-color: rgba(255, 255, 255, 0.95); padding: 25px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin: 20px auto; overflow-x: auto; margin-bottom: 40px;">
            <table style="margin-top: 0;">
                <thead>
                    <tr>
                        <th style="background-color: #e0f2fe; color: #0369a1;">FOLIO</th>
                        <th style="background-color: #e0f2fe; color: #0369a1;">CLIENTE</th>
                        <th style="background-color: #e0f2fe; color: #0369a1;">TOTAL</th>
                        <th style="background-color: #e0f2fe; color: #0369a1;">TIPO DE ENTREGA</th>
                        <th style="background-color: #e0f2fe; color: #0369a1;">DIRECCIÓN</th>
                        <th style="background-color: #e0f2fe; color: #0369a1;">ESTADO</th>
                        <th style="background-color: #e0f2fe; color: #0369a1;">TICKET</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($resultado_ventas && mysqli_num_rows($resultado_ventas) > 0) {
                        while($fila_v = mysqli_fetch_assoc($resultado_ventas)) {
                            echo "<tr>";
                            echo "<td><strong>#" . $fila_v['id_venta'] . "</strong></td>";
                            echo "<td>" . htmlspecialchars($fila_v['nombre_usuario'] ?? 'Cliente Mostrador') . "</td>";
                            echo "<td style='color: #059669; font-weight: bold;'>$" . number_format($fila_v['total'], 2) . "</td>";
                            $entrega = ($fila_v['tipo_entrega'] == 'domicilio') ? '🚚 Envío a Domicilio' : '🏪 Recoge en Sucursal';
                            echo "<td>" . $entrega . "</td>";
                            $direccion_txt = !empty($fila_v['direccion']) ? htmlspecialchars($fila_v['direccion']) : '-';
                            echo "<td>" . $direccion_txt . "</td>";
                            $estado = $fila_v['estado_envio'] ?? 'Pendiente';
                            if ($estado == 'Entregado') {
                                $badge = "<span style='background-color: #d1fae5; color: #065f46; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;'>🛍️ Entregado</span>";
                            } elseif ($estado == 'Enviado') {
                                $badge = "<span style='background-color: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;'>✅ Enviado</span>";
                            } else {
                                $badge = "<span style='background-color: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;'>⏳ Pendiente</span>";
                            }
                            echo "<td>" . $badge . "</td>";
                            echo "<td><a href='generar_ticket.php?id=" . $fila_v['id_venta'] . "' target='_blank' style='background-color: #ef4444; color: white; padding: 4px 10px; border-radius: 5px; text-decoration: none; font-size: 12px; font-weight: bold;'>📄 Ticket PDF</a></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align: center; color: #6b7280; padding: 20px;'>Aún no hay ventas registradas en el sistema.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="alerta-box">
            <h3 style="margin-top: 0; color: #b45309;">⚠️ Alertas de Stock Bajo</h3>
            <table style="margin-bottom: 0; background-color: rgba(255, 255, 255, 0.5);">
                <thead>
                    <tr>
                        <th style="background-color: #fef08a; color: #854d0e;">ID</th>
                        <th style="background-color: #fef08a; color: #854d0e;">PRODUCTO</th>
                        <th style="background-color: #fef08a; color: #854d0e;">TALLA</th>
                        <th style="background-color: #fef08a; color: #854d0e;">STOCK</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($resultado_alertas && mysqli_num_rows($resultado_alertas) > 0) {
                        while($fila_alerta = mysqli_fetch_assoc($resultado_alertas)) {
                            $estiloFilaAlerta = ($fila_alerta['cantidad_stock'] == 0) ? "background-color: #fee2e2;" : "";
                            echo "<tr style='{$estiloFilaAlerta}'>";
                            echo "<td>" . $fila_alerta['id_producto'] . "</td>";
                            echo "<td>" . htmlspecialchars($fila_alerta['nombre_producto']) . "</td>";
                            echo "<td>" . htmlspecialchars($fila_alerta['talla']) . "</td>";
                            echo "<td style='font-weight: bold; color: #dc2626;'>" . $fila_alerta['cantidad_stock'] . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align: center; color: #059669; font-weight: bold;'>Todo el inventario cuenta con buen stock.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="alerta-box" style="margin-top: 20px; border: 1px solid #fde68a; background: #fffbeb;">
            <h3 style="margin-top: 0; color: #d97706;">⏰ Alertas de Caducidad Próxima (Próximos 30 días)</h3>
            <table style="margin-bottom: 0; background-color: rgba(255, 255, 255, 0.5);">
                <thead>
                    <tr>
                        <th style="background-color: #fef3c7; color: #92400e;">ID</th>
                        <th style="background-color: #fef3c7; color: #92400e;">PRODUCTO</th>
                        <th style="background-color: #fef3c7; color: #92400e;">FECHA CADUCIDAD</th>
                        <th style="background-color: #fef3c7; color: #92400e;">ACCIÓN SUGERIDA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($resultado_caducidad && mysqli_num_rows($resultado_caducidad) > 0) {
                        while($fila_cad = mysqli_fetch_assoc($resultado_caducidad)) {
                            echo "<tr>";
                            echo "<td>" . $fila_cad['id_producto'] . "</td>";
                            echo "<td>" . htmlspecialchars($fila_cad['nombre_producto']) . "</td>";
                            echo "<td style='font-weight: bold; color: #dc2626;'>" . $fila_cad['fecha_caducidad'] . "</td>";

                            if (isset($_SESSION['rol']) && $_SESSION['rol'] == 1) {
                                echo "<td><a href='dar_promo.php?id=" . $fila_cad['id_producto'] . "' style='background: #f59e0b; color: white; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold;'>🔥 Aplicar Promo</a></td>";
                            } else {
                                $mensaje_alerta = urlencode("⚠️ Hola patrón, el sistema me avisa que el suplemento *" . $fila_cad['nombre_producto'] . "* (ID: " . $fila_cad['id_producto'] . ") caduca el " . $fila_cad['fecha_caducidad'] . ". ¿Me autoriza ponerlo en descuento?");          
                                echo "<td><a href='https://wa.me/4494681121?text=$mensaje_alerta' target='_blank' style='background: #25D366; color: white; padding: 6px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block;'>💬 Avisar por WhatsApp</a></td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align: center; color: #059669; font-weight: bold;'>No hay suplementos próximos a caducar.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <h2>Inventario Disponible</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>PRODUCTO</th>
                    <th>CATEGORÍA</th>
                    <th>TALLA / DETALLE</th>
                    <th>COLOR / LOTE</th>
                    <th>STOCK</th>
                    <th>PRECIO</th>
                    <th>CADUCIDAD</th>
                    <th>ESTATUS</th>
                    
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 1): ?>
                        <th>ACCIONES</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($resultado_inventario && mysqli_num_rows($resultado_inventario) > 0) {
                    while($fila = mysqli_fetch_assoc($resultado_inventario)) {
                        $cat = $fila['categoria'] ?? 'Ropa';
                        echo "<tr>";
                        echo "<td>" . $fila['id_producto'] . "</td>";
                        echo "<td>" . htmlspecialchars($fila['nombre_producto']) . "</td>";
                        echo "<td>" . htmlspecialchars($cat) . "</td>";
                        echo "<td>" . htmlspecialchars($fila['talla']) . "</td>";
                        echo "<td>" . htmlspecialchars($cat == 'Suplemento' ? ($fila['lote'] ?? '-') : $fila['color']) . "</td>";
                        echo "<td>" . $fila['cantidad_stock'] . "</td>";
                        echo "<td>$" . number_format($fila['precio'], 2) . "</td>";
                        echo "<td>" . ($cat == 'Suplemento' ? ($fila['fecha_caducidad'] ?? '-') : '-') . "</td>";
                        echo "<td><span class='status-badge'>" . htmlspecialchars($fila['estatus']) . "</span></td>";
                        if (isset($_SESSION['rol']) && $_SESSION['rol'] == 1) {
                            echo "<td style='display: flex; gap: 5px;'>";
                            echo "<button type='button' class='btn-editar' onclick='abrirModalStock(" . $fila['id_producto'] . ", \"" . addslashes($fila['nombre_producto']) . "\", " . $fila['cantidad_stock'] . ")'>✎ Editar</button>";
                            echo "<a href='dar_promo.php?id=" . $fila['id_producto'] . "' style='background-color: #f59e0b; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center;'>🔥 Promo</a>";
                            echo "<form action='eliminar_producto' method='POST' style='margin:0;' onsubmit='return confirm(\"¿Estás seguro de ELIMINAR por completo esta prenda/producto?\");'>
                                    <input type='hidden' name='id_producto' value='" . $fila['id_producto'] . "'>
                                    <button type='submit' style='background-color: #dc2626; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold;'>🗑️ Eliminar</button>
                                  </form>"; 
                            echo "</td>";
                        }
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='10'>No hay productos registrados en el inventario.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div> 
    <div id="modalStock" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:#fff; width:300px; margin:10% auto; padding:20px; border-radius:8px; text-align:center;">
        <h3>Editar Stock</h3>
            <p>Producto: <strong id="nombre_producto_modal"></strong></p>
            <form action="actualizar_stock" method="POST">
                <input type="hidden" id="id_producto_modal" name="id_producto">
                <label for="nuevo_stock_modal">Nueva cantidad en stock:</label><br><br>
                <input type="number" id="nuevo_stock_modal" name="nuevo_stock" min="0" max="99999" required style="width:100%; padding:8px;"><br><br>
                <button type="submit" style="background:#28a745; color:white; padding:10px; border:none; cursor:pointer; border-radius:5px;">Guardar Cambios</button>
                <button type="button" onclick="cerrarModalStock()" style="background:#dc3545; color:white; padding:10px; border:none; cursor:pointer; border-radius:5px; margin-left:10px;">Cancelar</button>
            </form>
        </div>
    </div>
    <script>
    function abrirModalStock(id, nombre, stockActual) {
        document.getElementById('id_producto_modal').value = id;
        document.getElementById('nombre_producto_modal').innerText = nombre;
        document.getElementById('nuevo_stock_modal').value = stockActual;
        document.getElementById('modalStock').style.display = 'block';
    }
    function cerrarModalStock() {
        document.getElementById('modalStock').style.display = 'none';
    }
    </script>
</body>
</html>