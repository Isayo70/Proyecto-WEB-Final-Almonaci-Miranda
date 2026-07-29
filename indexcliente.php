<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login");
    exit();
}
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 4) {
    if (isset($_SESSION['rol']) && ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2)) {
        header("Location: index");
    } else {
        header("Location: login");
    }
    exit();
}
include 'conexion.php';
$q = trim($_GET['q'] ?? '');
$categoria_filtro = trim($_GET['cat'] ?? 'Todos');
$genero_filtro = trim($_GET['genero'] ?? ''); 
$raw_products = [];
$products = []; 
if ($q !== '') {
    $sql = "SELECT * FROM inventario WHERE nombre_producto LIKE ? AND estatus = 'Activo'";
    $stmt = $conexion->prepare($sql);
    $term = "%{$q}%";
    $stmt->bind_param("s", $term);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado) {
        $raw_products = $resultado->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
} else {
    $sql = "SELECT * FROM inventario WHERE estatus = 'Activo'";
    if ($categoria_filtro !== 'Todos') {
        $sql .= " AND categoria = '" . mysqli_real_escape_string($conexion, $categoria_filtro) . "'";
    }
    if ($genero_filtro !== '') {
        $sql .= " AND genero = '" . mysqli_real_escape_string($conexion, $genero_filtro) . "'";
    }
    $resultado = $conexion->query($sql);
    if ($resultado) {
        $raw_products = $resultado->fetch_all(MYSQLI_ASSOC);
    }
}
foreach ($raw_products as $prod) {
    $es_suplemento = ($prod['categoria'] === 'Suplemento');
    $detalle_extra = $es_suplemento ? ($prod['lote'] ?? 'General') : ($prod['color'] ?? 'N/A');
    $llave = $prod['nombre_producto'] . '_' . $detalle_extra;
    if (!isset($products[$llave])) {
        $products[$llave] = [
            'nombre_producto' => $prod['nombre_producto'],
            'categoria' => $prod['categoria'],
            'detalle' => $detalle_extra, 
            'precio' => $prod['precio'],
            'precio_original' => $prod['precio_original'] ?? null, 
            'imagen' => $prod['imagen'],
            'cantidad_stock_total' => 0,
            'variantes' => []
        ];
    }
    $products[$llave]['cantidad_stock_total'] += $prod['cantidad_stock'];
    $products[$llave]['variantes'][] = [
        'id_producto' => $prod['id_producto'],
        'etiqueta' => $es_suplemento ? "Lote: " . ($prod['lote'] ?? 'N/A') . " (Cad: " . ($prod['fecha_caducidad'] ?? 'N/T') . ")" : "Talla " . $prod['talla'],
        'stock' => $prod['cantidad_stock']
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo de Ropa Deportiva - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>
    <div class="cliente-navbar">
        <div style="font-size: 16px; color: #374151;">
            Bienvenido(a), <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong>
        </div>
        <div style="display: flex; gap: 15px; align-items: center;">
            <a href="mis_pedidos" style="color: #0284c7; text-decoration: none; font-weight: bold; padding: 10px;">📦 Mis Pedidos</a>
            <a href="ver_carrito" style="background-color: #059669; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; transition: background 0.3s;">
                🛒 Ir a Pagar
                <?php 
                if (isset($_SESSION['carrito']) && count($_SESSION['carrito']) > 0) {
                    $total_articulos = array_sum($_SESSION['carrito']);
                    echo "<span style='background: white; color: #059669; padding: 2px 7px; border-radius: 12px; margin-left: 8px; font-size: 13px; font-weight: bold;'>" . $total_articulos . "</span>";
                }
                ?>
            </a>
            <a href="logout" style="border: 1px solid #dc2626; color: #dc2626; background-color: transparent; padding: 9px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; transition: all 0.3s;" onmouseover="this.style.backgroundColor='#dc2626'; this.style.color='white';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#dc2626';" onclick="return confirm('¿Seguro que quieres cerrar sesión?');">
                Cerrar Sesión
            </a>
        </div>
    </div>
    <main class="shop-page" style="padding: 20px 40px;">
        <div style="text-align: center;">
            <h1 class="cliente-titulo" style="margin-bottom: 10px;">
                Catálogo General
                <?php if ($q): ?>
                    <br><small style="color: #6b7280; font-size: 16px;">Resultados para “<?= htmlspecialchars($q, ENT_QUOTES) ?>”</small>
                <?php endif; ?>
            </h1>
            <div style="display: flex; justify-content: center; gap: 10px; margin: 20px 0 30px 0; flex-wrap: wrap;">
                <a href="indexcliente" style="padding: 9px 20px; border-radius: 20px; text-decoration: none; font-weight: bold; font-size: 14px; background: <?= ($categoria_filtro === 'Todos' && $genero_filtro === '') ? '#0f172a' : '#e2e8f0'; ?>; color: <?= ($categoria_filtro === 'Todos' && $genero_filtro === '') ? '#fff' : '#475569'; ?>; transition: all 0.2s;">✨ Ver Todos</a>
                <a href="indexcliente?genero=Caballero" style="padding: 9px 20px; border-radius: 20px; text-decoration: none; font-weight: bold; font-size: 14px; background: <?= ($genero_filtro === 'Caballero') ? '#0f172a' : '#e2e8f0'; ?>; color: <?= ($genero_filtro === 'Caballero') ? '#fff' : '#475569'; ?>; transition: all 0.2s;">👕 Caballero</a>
                <a href="indexcliente?genero=Dama" style="padding: 9px 20px; border-radius: 20px; text-decoration: none; font-weight: bold; font-size: 14px; background: <?= ($genero_filtro === 'Dama') ? '#0f172a' : '#e2e8f0'; ?>; color: <?= ($genero_filtro === 'Dama') ? '#fff' : '#475569'; ?>; transition: all 0.2s;">👚 Dama</a>
                <a href="indexcliente?genero=Nino" style="padding: 9px 20px; border-radius: 20px; text-decoration: none; font-weight: bold; font-size: 14px; background: <?= ($genero_filtro === 'Nino') ? '#0f172a' : '#e2e8f0'; ?>; color: <?= ($genero_filtro === 'Nino') ? '#fff' : '#475569'; ?>; transition: all 0.2s;">👦 Niño</a>
                <a href="indexcliente?cat=Suplemento" style="padding: 9px 20px; border-radius: 20px; text-decoration: none; font-weight: bold; font-size: 14px; background: <?= ($categoria_filtro === 'Suplemento') ? '#0f172a' : '#e2e8f0'; ?>; color: <?= ($categoria_filtro === 'Suplemento') ? '#fff' : '#475569'; ?>; transition: all 0.2s;">💊 Suplementos</a>
            </div>
        </div>
        <div class="products-grid">
            <?php if (!empty($products)): ?>
               <?php foreach ($products as $product): ?>
                    <article class="product-card">
                        <div style="position: relative;">
                            <?php if (!empty($product['precio_original']) && $product['precio_original'] > $product['precio']): ?>
                                <?php $descuento_porcentaje = round((($product['precio_original'] - $product['precio']) / $product['precio_original']) * 100); ?>
                                <div style="position: absolute; top: 12px; right: 12px; background: #ef4444; color: white; padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 13px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); z-index: 10; border: 2px solid white;">
                                    🔥 -<?= $descuento_porcentaje ?>%
                                </div>
                            <?php endif; ?>
                            <img src="<?= htmlspecialchars($product['imagen'] ?? 'Imgs/logo.png', ENT_QUOTES) ?>"
                                 alt="<?= htmlspecialchars($product['nombre_producto'], ENT_QUOTES) ?>">
                            <div class="product-info" style="margin-top: 15px; padding: 0 15px;">
                                <h3 style="margin: 0 0 8px 0; font-size: 18px; color: #1f2937;">
                                    <?= htmlspecialchars($product['nombre_producto'], ENT_QUOTES) ?>
                                </h3>
                                <?php if ($product['categoria'] === 'Ropa'): ?>
                                    <p style="color: #4b5563; font-size: 14px; margin: 0 0 5px 0;">Color: <strong style="color: #111827;"><?= htmlspecialchars($product['detalle'], ENT_QUOTES) ?></strong></p>
                                <?php else: ?>
                                    <p style="color: #4b5563; font-size: 14px; margin: 0 0 5px 0;">Categoría: <strong style="color: #111827;">Suplemento Deportivo</strong></p>
                                <?php endif; ?>
                                <?php if ($product['cantidad_stock_total'] > 0): ?>
                                    <p style="color: #059669; font-size: 13px; font-weight: bold; margin: 0 0 10px 0;">✅ Disponibles: <?= $product['cantidad_stock_total'] ?> unidades</p>
                                <?php else: ?>
                                    <p style="color: #dc2626; font-size: 13px; font-weight: bold; margin: 0 0 10px 0;">❌ Producto Agotado</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="padding: 15px; border-top: 1px solid #f1f5f9; background: #fafafa;">
                            <?php if (!empty($product['precio_original']) && $product['precio_original'] > $product['precio']): ?>
                                <div style="text-align: center; margin-bottom: 15px; display: flex; flex-direction: column; line-height: 1.2;">
                                    <span style="font-size: 0.9em; color: #64748b; text-decoration: line-through;">Antes: $<?= number_format($product['precio_original'], 2) ?></span>
                                    <span style="font-weight: bold; font-size: 1.5em; color: #ef4444;">🔥 Ahora: $<?= number_format($product['precio'], 2) ?></span>
                                </div>
                            <?php else: ?>
                                <div class="product-price" style="font-weight: bold; font-size: 1.3em; margin-bottom: 15px; color: #059669; text-align: center;">
                                    $<?= number_format($product['precio'], 2) ?>
                                </div>
                            <?php endif; ?>
                            <form action="agregar_carrito" method="POST">
                                <label style="font-size: 13px; color: #475569; display: block; margin-bottom: 5px; font-weight: 600;">
                                    <?= ($product['categoria'] === 'Ropa') ? 'Selecciona tu talla:' : 'Selecciona presentación/lote:'; ?>
                                </label>
                                <select name="id_producto" style="width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 6px; border: 1px solid #cbd5e1; background: #ffffff; cursor: pointer;" required>
                                    <option value="">Elegir opción...</option>
                                    <?php foreach ($product['variantes'] as $variante): ?>
                                        <?php if ($variante['stock'] > 0): ?>
                                            <option value="<?= $variante['id_producto'] ?>">
                                                <?= htmlspecialchars($variante['etiqueta']) ?> (<?= $variante['stock'] ?> disp.)
                                            </option>
                                        <?php else: ?>
                                            <option value="<?= $variante['id_producto'] ?>" disabled style="color: #94a3b8;">
                                                <?= htmlspecialchars($variante['etiqueta']) ?> (Agotado)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="cantidad" value="1">
                                <?php if ($product['cantidad_stock_total'] > 0): ?>
                                    <button type="submit" class="btn-catalogo-carrito">
                                        AGREGAR AL CARRITO
                                    </button>
                                <?php else: ?>
                                    <button type="button" disabled class="btn-catalogo-agotado">
                                        SIN STOCK
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </article>
               <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #6b7280; font-size: 18px; width: 100%; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); grid-column: 1 / -1;">
                    No hay productos disponibles en esta categoría por el momento.
                </p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>