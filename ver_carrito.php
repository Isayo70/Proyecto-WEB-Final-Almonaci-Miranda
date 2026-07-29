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

$mensaje_error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_pagar'])) {
    $id_usuario = $_SESSION['id_usuario'] ?? 0;
    $total_venta = floatval($_POST['total_venta']);
    $tipo_entrega = mysqli_real_escape_string($conexion, $_POST['tipo_entrega']);
    $direccion = ($tipo_entrega === 'domicilio') ? mysqli_real_escape_string($conexion, $_POST['direccion_cliente']) : 'Recoge en Sucursal';
    $metodo_pago = mysqli_real_escape_string($conexion, $_POST['metodo_pago']);

    if (!empty($_SESSION['carrito'])) {
        $sql_venta = "INSERT INTO ventas (id_usuario, total, tipo_entrega, direccion, estado_envio) VALUES (?, ?, ?, ?, 'Pendiente')";
        $stmt_v = $conexion->prepare($sql_venta);
        $stmt_v->bind_param("idss", $id_usuario, $total_venta, $tipo_entrega, $direccion);
        
        if ($stmt_v->execute()) {
            $id_venta_generada = $stmt_v->insert_id;
            $stmt_v->close();

            foreach ($_SESSION['carrito'] as $id_prod => $cant) {
                $id_prod = intval($id_prod);
                $cant = intval($cant);

                $res_p = mysqli_query($conexion, "SELECT precio FROM inventario WHERE id_producto = $id_prod");
                if ($row_p = mysqli_fetch_assoc($res_p)) {
                    $precio_unitario = $row_p['precio'];
                    
                    $sql_det = "INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
                    $stmt_d = $conexion->prepare($sql_det);
                    $stmt_d->bind_param("iiid", $id_venta_generada, $id_prod, $cant, $precio_unitario);
                    $stmt_d->execute();
                    $stmt_d->close();

                    mysqli_query($conexion, "UPDATE inventario SET cantidad_stock = cantidad_stock - $cant WHERE id_producto = $id_prod");
                }
            }

            unset($_SESSION['carrito']);
            header("Location: generar_ticket.php?id=" . $id_venta_generada);
            exit();
        } else {
            $mensaje_error = "Error al procesar la compra en la base de datos.";
        }
    }
}

$usuario_actual = $_SESSION['usuario'];
$nombre_cliente = $usuario_actual; 
$correo_cliente = "No registrado";

$stmt_user = $conexion->prepare("SELECT id_usuario, nombre_real, correo FROM usuarios WHERE nombre_usuario = ?");
$stmt_user->bind_param("s", $usuario_actual);
$stmt_user->execute();
$resultado_user = $stmt_user->get_result();

if ($fila_user = $resultado_user->fetch_assoc()) {
    $_SESSION['id_usuario'] = $fila_user['id_usuario']; // Aseguramos el ID en sesión
    $nombre_cliente = !empty($fila_user['nombre_real']) ? $fila_user['nombre_real'] : $usuario_actual;
    $correo_cliente = !empty($fila_user['correo']) ? $fila_user['correo'] : "No registrado";
}
$stmt_user->close();

if (isset($_GET['accion']) && $_GET['accion'] === 'vaciar') {
    unset($_SESSION['carrito']);
    header("Location: ver_carrito");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Carrito de Compras - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>

<div class="carrito-container">
    <h1 style="text-align: center; color: #1e293b; margin-top: 0;">Carrito de <?= htmlspecialchars($nombre_cliente) ?></h1>

    <?php if (!empty($mensaje_error)): ?>
        <div style="background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: bold;">
            <?= $mensaje_error ?>
        </div>
    <?php endif; ?>

    <?php
    if (isset($_SESSION['carrito']) && count($_SESSION['carrito']) > 0) {
        
        $ids_productos = array_keys($_SESSION['carrito']);
        $ids_limpios = array_map('intval', $ids_productos);
        $ids_csv = implode(',', $ids_limpios);
        
        $sql = "SELECT * FROM inventario WHERE id_producto IN ($ids_csv)";
        $resultado = mysqli_query($conexion, $sql);
        
        echo "<table>";
        echo "<thead><tr><th>Prenda</th><th>Nombre</th><th>Precio Unitario</th><th>Cantidad</th><th>Subtotal</th></tr></thead>";
        echo "<tbody>";

        $total_pagar = 0;

        while ($producto = mysqli_fetch_assoc($resultado)) {
            $id = $producto['id_producto'];
            $cantidad = $_SESSION['carrito'][$id]; 
            $precio = $producto['precio'];
            $subtotal = $precio * $cantidad;
            $total_pagar += $subtotal;

            echo "<tr>";
            echo "<td><img src='" . htmlspecialchars($producto['imagen'] ?? 'imgs/default.jpeg') . "' class='img-carrito' alt='Imagen'></td>";
            echo "<td style='font-weight: 600;'>" . htmlspecialchars($producto['nombre_producto']) . "</td>";
            echo "<td>$" . number_format($precio, 2) . " MXN</td>";
            echo "<td><strong>$cantidad</strong></td>";
            echo "<td style='color: #059669; font-weight: bold;'>$" . number_format($subtotal, 2) . " MXN</td>";
            echo "</tr>";
        }

        echo "</tbody></table>";
        echo "<div class='total-box'>Total a pagar: <strong>$" . number_format($total_pagar, 2) . " MXN</strong></div>";
        ?>
        <form action="ver_carrito" method="POST">            
            <input type="hidden" name="total_venta" value="<?php echo $total_pagar; ?>">
            <input type="hidden" name="accion_pagar" value="1">

            <div class="seccion-formulario">
                <h3>1. Opciones de Entrega</h3>
                
                <div class="radio-group">
                    <label>
                        <input type="radio" name="tipo_entrega" value="domicilio" id="radio_domicilio" checked>
                        🚚 Envío a Domicilio
                    </label>
                </div>
                
                <div class="radio-group">
                    <label>
                        <input type="radio" name="tipo_entrega" value="sucursal" id="radio_sucursal">
                        🏪 Recoger en Sucursal Central (Aguascalientes)
                    </label>
                </div>

                <div class="direccion-caja" id="caja_direccion">
                    <label for="direccion_cliente" style="font-weight: 600; display: block; margin-bottom: 8px; color: #374151; font-size: 0.95em;">Dirección completa de entrega:</label>
                    <textarea name="direccion_cliente" id="direccion_cliente" rows="3" placeholder="Ej. Calle Francisco I. Madero #123, Colonia Centro..." required></textarea>
                </div>
                
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #cbd5e1;">
                    <p style="font-size: 0.9em; color: #64748b; margin: 0;"><strong>Recibo digital:</strong> Los detalles de esta compra se enviarán a <strong><?= htmlspecialchars($correo_cliente) ?></strong></p>
                </div>
            </div>

            <div class="seccion-formulario">
                <h3>2. Selecciona tu Método de Pago</h3>
                <p style="font-size: 0.9em; color: #64748b; margin-top: -5px; margin-bottom: 15px;">Elige cómo deseas realizar tu pago de forma segura.</p>
                
                <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
                    <label style="border: 1px solid #cbd5e1; padding: 12px 15px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 10px; font-weight: 600; background: #fff;">
                        <input type="radio" name="metodo_pago" value="tarjeta" id="pago_tarjeta" checked onchange="toggleMetodoPago()">
                        💳 Tarjeta de Crédito o Débito
                    </label>
                    
                    <label style="border: 1px solid #cbd5e1; padding: 12px 15px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 10px; font-weight: 600; background: #fff;">
                        <input type="radio" name="metodo_pago" value="spei" id="pago_spei" onchange="toggleMetodoPago()">
                        🏦 Transferencia Bancaria en Línea (SPEI)
                    </label>

                    <label style="border: 1px solid #cbd5e1; padding: 12px 15px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 10px; font-weight: 600; background: #fff;">
                        <input type="radio" name="metodo_pago" value="paypal" id="pago_paypal" onchange="toggleMetodoPago()">
                        💛 Pagar con PayPal
                    </label>

                    <label id="label_efectivo" style="display: none; border: 1px solid #cbd5e1; padding: 12px 15px; border-radius: 8px; cursor: pointer; align-items: center; gap: 10px; font-weight: 600; background: #fff;">
                        <input type="radio" name="metodo_pago" value="efectivo" id="pago_efectivo" onchange="toggleMetodoPago()">
                        💵 Pagar en efectivo al recoger en sucursal
                    </label>
                </div>

                <div id="caja_tarjeta" class="grid-pago" style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div class="input-group-pago" style="grid-column: span 2;">
                        <label>Nombre en la tarjeta:</label>
                        <input type="text" name="titular_tarjeta" value="<?= htmlspecialchars($nombre_cliente) ?>" placeholder="Ej. Juan Pérez">
                    </div>
                    <div class="input-group-pago" style="grid-column: span 2;">
                        <label>Número de Tarjeta:</label>
                        <input type="text" name="numero_tarjeta" placeholder="0000 0000 0000 0000" maxlength="16">
                    </div>
                    <div class="input-group-pago">
                        <label>Fecha de Expiración (MM/AA):</label>
                        <input type="text" name="fecha_expiracion" placeholder="12/28" maxlength="5">
                    </div>
                    <div class="input-group-pago">
                        <label>CVV:</label>
                        <input type="password" name="cvv" placeholder="123" maxlength="4">
                    </div>
                </div>

                <div id="caja_spei" style="display: none; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <p style="margin: 0 0 10px 0; font-size: 14px; color: #334155;">Datos CLABE interbancaria (BBVA / Santander) para tu transferencia.</p>
                </div>

                <div id="caja_paypal" style="display: none; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center;">
                    <p style="margin: 0; font-size: 14px; color: #334155;">Serás vinculado con tu cuenta <strong>PayPal</strong> al confirmar.</p>
                </div>

                <div id="caja_efectivo" style="display: none; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center;">
                    <p style="margin: 0; font-size: 14px; color: #334155;">Pagarás tu pedido en mostrador al momento de recogerlo en nuestra <strong>Sucursal Central</strong>.</p>
                </div>
            </div>

            <button type="submit" class='btn-carrito btn-pagar' style="width: 100%; margin-top: 15px; padding: 14px; font-size: 16px; background-color: #0f172a; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">
                Confirmar y Pagar $<?= number_format($total_pagar, 2) ?> MXN
            </button>

            <div class='acciones-carrito' style="margin-top: 20px;">
                <div>
                    <a href='indexcliente' class='btn-carrito btn-seguir'>← Seguir comprando</a> 
                    <a href='ver_carrito?accion=vaciar' class='btn-carrito btn-vaciar'>Vaciar carrito</a>
                </div>
            </div>
        </form>

        <script>
            const radioDomicilio = document.getElementById('radio_domicilio');
            const radioSucursal = document.getElementById('radio_sucursal');
            const cajaDireccion = document.getElementById('caja_direccion');
            const inputDireccion = document.getElementById('direccion_cliente');
            const labelEfectivo = document.getElementById('label_efectivo');
            const pagoEfectivo = document.getElementById('pago_efectivo');
            const pagoTarjeta = document.getElementById('pago_tarjeta');

            radioDomicilio.addEventListener('change', function() {
                if(this.checked) {
                    cajaDireccion.style.display = 'block';
                    inputDireccion.setAttribute('required', 'true');
                    
                    labelEfectivo.style.display = 'none';
                    
                    if(pagoEfectivo.checked) {
                        pagoTarjeta.checked = true;
                        toggleMetodoPago(); 
                    }
                }
            });

            radioSucursal.addEventListener('change', function() {
                if(this.checked) {
                    cajaDireccion.style.display = 'none';
                    inputDireccion.removeAttribute('required');
                    inputDireccion.value = '';
                    
                    labelEfectivo.style.display = 'flex';
                }
            });

            function toggleMetodoPago() {
                const pagoTarjetaChecked = document.getElementById('pago_tarjeta').checked;
                const pagoSpeiChecked = document.getElementById('pago_spei').checked;
                const pagoPaypalChecked = document.getElementById('pago_paypal').checked;
                const pagoEfectivoChecked = document.getElementById('pago_efectivo').checked;

                const cajaTarjeta = document.getElementById('caja_tarjeta');
                const cajaSpei = document.getElementById('caja_spei');
                const cajaPaypal = document.getElementById('caja_paypal');
                const cajaEfectivo = document.getElementById('caja_efectivo');

                cajaTarjeta.style.display = 'none';
                cajaSpei.style.display = 'none';
                cajaPaypal.style.display = 'none';
                cajaEfectivo.style.display = 'none';

                if (pagoTarjetaChecked) {
                    cajaTarjeta.style.display = 'grid'; 
                } else if (pagoSpeiChecked) {
                    cajaSpei.style.display = 'block';
                } else if (pagoPaypalChecked) {
                    cajaPaypal.style.display = 'block';
                } else if (pagoEfectivoChecked) {
                    cajaEfectivo.style.display = 'block';
                }
            }
        </script>
        
        <?php
    } else {
        echo "<div style='text-align: center; padding: 40px;'>";
        echo "<p style='font-size: 1.2em; color: #64748b; margin-bottom: 20px;'>Tu carrito está vacío en este momento.</p>";
        echo "<a href='indexcliente' class='btn-carrito btn-seguir'>Ir al Catálogo</a>";
        echo "</div>";
    }
    ?>
</div>

</body>
</html>