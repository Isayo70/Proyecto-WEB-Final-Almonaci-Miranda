-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-07-2026 a las 02:51:53
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `pruebatla`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `obtener_perfil_usuario` (IN `p_nombre` VARCHAR(100))   BEGIN
    SELECT u.nombre_usuario, u.email, r.nombre_rol AS rol, u.imagen
    FROM usuarios u
    INNER JOIN roles r ON u.id_rol = r.id_rol
    WHERE u.nombre_usuario = p_nombre;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `procesar_nueva_venta` (IN `p_id_usuario` INT, IN `p_id_producto` INT, IN `p_cantidad` INT, IN `p_total_venta` DECIMAL(10,2))   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;

    -- Creamos el ticket general
    INSERT INTO ventas (id_usuario, total) 
    VALUES (p_id_usuario, p_total_venta);

    -- Recuperamos el ID del ticket que se acaba de crear
    SET @id_del_ticket = LAST_INSERT_ID();

    -- Detallamos qué producto se vendió
    INSERT INTO detalle_ventas (id_venta, id_producto, cantidad) 
    VALUES (@id_del_ticket, p_id_producto, p_cantidad);

    -- Descontamos del inventario
    UPDATE inventario 
    SET cantidad_stock = cantidad_stock - p_cantidad 
    WHERE id_producto = p_id_producto;

    -- Dejamos el registro de la salida
    INSERT INTO movimientos (id_producto, id_usuario, tipo_movimiento, cantidad)
    VALUES (p_id_producto, p_id_usuario, 'Salida por Venta', p_cantidad);

    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrar_cliente` (IN `p_nombre_usuario` VARCHAR(100), IN `p_email` VARCHAR(100), IN `p_password_hash` VARCHAR(255))   BEGIN
    -- Asignamos automáticamente el rol 4 (Cliente)
    INSERT INTO usuarios (nombre_usuario, email, password_hash, id_rol)
    VALUES (p_nombre_usuario, p_email, p_password_hash, 4);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrar_corte_caja` (IN `p_id_usuario` INT)   BEGIN
    DECLARE v_total_del_dia DECIMAL(10,2);

    -- Sumamos las ventas de hoy del empleado
    SELECT COALESCE(SUM(total), 0) INTO v_total_del_dia
    FROM ventas
    WHERE id_usuario = p_id_usuario 
      AND DATE(fecha) = CURDATE();

    -- Guardamos el registro
    INSERT INTO cortes_caja (id_usuario, total_calculado)
    VALUES (p_id_usuario, v_total_del_dia);
    
    -- Devolvemos el dato a PHP
    SELECT v_total_del_dia AS total_corte;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrar_entrada_mercancia` (IN `p_nombre` VARCHAR(100), IN `p_talla` VARCHAR(10), IN `p_color` VARCHAR(30), IN `p_stock` INT, IN `p_precio` DECIMAL(10,2))   BEGIN
    INSERT INTO inventario (nombre_producto, talla, color, cantidad_stock, precio)
    VALUES (p_nombre, p_talla, p_color, p_stock, p_precio);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_empleado_completo` (IN `p_username` VARCHAR(50), IN `p_password` VARCHAR(255), IN `p_id_rol` INT, IN `p_nombre` VARCHAR(50), IN `p_apellidos` VARCHAR(50), IN `p_curp` VARCHAR(18), IN `p_matricula` VARCHAR(20), IN `p_ine` VARCHAR(20), IN `p_ecivil` VARCHAR(20), IN `p_sueldo` DECIMAL(10,2), IN `p_nss` VARCHAR(11), IN `p_area` VARCHAR(50), IN `p_fecha_inicio` DATE, IN `p_fecha_fin` DATE, IN `p_rfc` VARCHAR(13), IN `p_clabe` VARCHAR(18), IN `p_cuenta` VARCHAR(20), IN `p_banco` VARCHAR(50), IN `p_domicilio` TEXT, IN `p_telefono` VARCHAR(15), IN `p_email` VARCHAR(100))   BEGIN
    DECLARE v_last_id INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;

        -- Corrección de los nombres de las columnas para coincidir con tu tabla real
        INSERT INTO usuarios (nombre_usuario, password_hash, id_rol, email) 
        VALUES (p_username, p_password, p_id_rol, p_email);

        SET v_last_id = LAST_INSERT_ID();

        INSERT INTO usuarios_identidad (id_usuario, nombre, apellidos, curp, matricula, ine, estado_civil)
        VALUES (v_last_id, p_nombre, p_apellidos, p_curp, p_matricula, p_ine, p_ecivil);

        INSERT INTO usuarios_empleo (id_usuario, sueldo, nss, area_contrato, fecha_contrato, fin_contrato)
        VALUES (v_last_id, p_sueldo, p_nss, p_area, p_fecha_inicio, p_fecha_fin);

        INSERT INTO usuarios_fiscal (id_usuario, rfc, cue_interbancaria, num_cuenta_banco, banco)
        VALUES (v_last_id, p_rfc, p_clabe, p_cuenta, p_banco);

        INSERT INTO usuarios_contacto (id_usuario, domicilio, telefono, email)
        VALUES (v_last_id, p_domicilio, p_telefono, p_email);

    COMMIT;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cortes_caja`
--

CREATE TABLE `cortes_caja` (
  `id_corte` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `total_calculado` decimal(10,2) NOT NULL,
  `fecha_corte` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id_detalle` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_venta`
--

INSERT INTO `detalle_venta` (`id_detalle`, `id_venta`, `id_producto`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 7, 1, 499.00),
(2, 3, 13, 1, 700.00),
(3, 3, 15, 1, 300.00),
(4, 3, 6, 1, 250.00),
(5, 4, 7, 1, 499.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas`
--

CREATE TABLE `detalle_ventas` (
  `id_detalle` int(11) NOT NULL,
  `id_venta` int(11) DEFAULT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_ventas`
--

INSERT INTO `detalle_ventas` (`id_detalle`, `id_venta`, `id_producto`, `cantidad`) VALUES
(1, 2, 1, 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

CREATE TABLE `inventario` (
  `id_producto` int(11) NOT NULL,
  `nombre_producto` varchar(100) NOT NULL,
  `categoria` varchar(50) DEFAULT 'Ropa',
  `genero` varchar(50) DEFAULT 'Unisex',
  `talla` varchar(10) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `cantidad_stock` int(11) DEFAULT 0,
  `precio` decimal(10,2) NOT NULL,
  `precio_original` decimal(10,2) DEFAULT NULL,
  `fecha_caducidad` date DEFAULT NULL,
  `lote` varchar(50) DEFAULT NULL,
  `estatus` varchar(20) DEFAULT 'Activo',
  `imagen` varchar(255) DEFAULT 'imgs/default.jpeg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario`
--

INSERT INTO `inventario` (`id_producto`, `nombre_producto`, `categoria`, `genero`, `talla`, `color`, `cantidad_stock`, `precio`, `precio_original`, `fecha_caducidad`, `lote`, `estatus`, `imagen`) VALUES
(1, 'Conjunto Deportivo Nike', 'Ropa', 'Caballero', 'Unitalla', 'Negro', 62, 711.00, 790.00, NULL, NULL, 'Activo', 'imgs/1785011370_Conjunto.jpg'),
(2, 'Playera Basica Nike', 'Ropa', 'Caballero', 'XS', 'Negro', 0, 250.00, NULL, NULL, NULL, 'Activo', 'imgs/1785013706_basicanegranike.webp'),
(3, 'Playera Basica Nike', 'Ropa', 'Caballero', 'S', 'Negro', 81, 250.00, NULL, NULL, NULL, 'Activo', 'imgs/1785013729_basicanegranike.webp'),
(4, 'Playera Basica Nike', 'Ropa', 'Caballero', 'M', 'Negro', 198, 250.00, NULL, NULL, NULL, 'Activo', 'imgs/1785013758_basicanegranike.webp'),
(5, 'Playera Basica Nike', 'Ropa', 'Caballero', 'L', 'Negro', 170, 250.00, NULL, NULL, NULL, 'Activo', 'imgs/1785013809_basicanegranike.webp'),
(6, 'Playera Basica Nike', 'Ropa', 'Caballero', 'XL', 'Negro', 299, 250.00, NULL, NULL, NULL, 'Activo', 'imgs/1785013933_basicanegranike.webp'),
(7, 'PSYCHOTIC 35 SERV INSANE LABZ', 'Suplemento', 'Unisex', '-', '-', 495, 499.00, NULL, '2027-01-20', '2026-A', 'Activo', 'imgs/1785014249_preentreno.jpg'),
(8, 'Optimum Nutrition Gold Standard 100% Whey Protein Powder, Double Rich Chocolate, 5 Pound (Packaging ', 'Suplemento', 'Unisex', '-', '-', 397, 1700.00, NULL, '2028-02-14', '1425-O', 'Activo', 'imgs/1785014829_protewhey.jpg'),
(9, 'Enterizo Alo', 'Ropa', 'Dama', 'Unitalla', 'Cafe', 9, 490.00, NULL, NULL, NULL, 'Activo', 'imgs/1785015935_aloenterizo.jpg'),
(10, 'Mutant Mass / 56g Proteina / 192g Carbohidratos / 1100 calorias (5 LB, CHOCOLATE)', 'Suplemento', 'Unisex', '-', '-', 499, 560.00, NULL, '2026-07-29', '1425-K', 'Activo', 'imgs/1785170447_MutantMa.jpg'),
(11, 'Disfraz de Toy Story para Niño de 1 a 10 años', 'Ropa', 'Nino', 'Unitalla', 'Woody', 100, 700.00, NULL, NULL, NULL, 'Activo', 'imgs/1785171450_woddy.jpg'),
(12, 'Conjunto Deportivo NFL ', 'Ropa', 'Nino', 'XS', 'Amarillo ', 250, 499.00, NULL, NULL, NULL, 'Activo', 'imgs/1785172225_amarillonfl.jpg'),
(13, 'Birdman Falcon Performance Proteina Premium Alto Rendimiento En Polvo, 30gr proteina y 3gr Creatina ', 'Suplemento', 'Unisex', 'Unitalla', '', 18, 700.00, 1400.00, '2026-01-12', 'Sabor Choco Bronze ', 'Activo', 'imgs/1785172379_birmandprote.jpg'),
(14, 'Top Alo', 'Ropa', 'Dama', 'XS', 'Negro', 9, 350.00, NULL, NULL, NULL, 'Activo', 'imgs/1785172562_top.webp'),
(15, 'chichifli', 'Suplemento', 'Unisex', 'Unitalla', '', 99, 300.00, NULL, '2026-07-16', '1425', 'Activo', 'imgs/1785256984_WhatsApp Image 2026-07-27 at 11.19.57 AM.jpeg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos`
--

CREATE TABLE `movimientos` (
  `id_movimiento` int(11) NOT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `tipo_movimiento` varchar(50) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id_permiso` int(11) NOT NULL,
  `nombre_permiso` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`) VALUES
(1, 'Dueño'),
(2, 'Empleado'),
(4, 'Cliente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permisos`
--

CREATE TABLE `rol_permisos` (
  `id_rol` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos_caja`
--

CREATE TABLE `turnos_caja` (
  `id_turno` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_apertura` datetime DEFAULT current_timestamp(),
  `fecha_cierre` datetime DEFAULT NULL,
  `monto_inicial` decimal(10,2) DEFAULT 0.00,
  `estatus` varchar(20) DEFAULT 'Abierto',
  `total_efectivo` decimal(10,2) DEFAULT 0.00,
  `total_tarjeta` decimal(10,2) DEFAULT 0.00,
  `total_venta` decimal(10,2) DEFAULT 0.00,
  `total_transferencia` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Volcado de datos para la tabla `turnos_caja`
--

INSERT INTO `turnos_caja` (`id_turno`, `id_usuario`, `fecha_apertura`, `fecha_cierre`, `monto_inicial`, `estatus`, `total_efectivo`, `total_tarjeta`, `total_venta`, `total_transferencia`) VALUES
(1, 9, '2026-07-27 23:13:47', '2026-07-27 23:15:41', 0.00, 'Cerrado', 7900.00, 0.00, 7900.00, 0.00),
(2, 5, '2026-07-28 10:43:41', '2026-07-28 10:45:12', 0.00, 'Cerrado', 0.00, 0.00, 0.00, 0.00),
(3, 9, '2026-07-28 10:49:41', '2026-07-28 10:50:32', 0.00, 'Cerrado', 0.00, 0.00, 0.00, 0.00),
(4, 5, '2026-07-28 17:29:10', '2026-07-28 17:31:27', 0.00, 'Cerrado', 0.00, 0.00, 0.00, 0.00),
(5, 9, '2026-07-28 17:39:12', '2026-07-28 17:40:52', 0.00, 'Cerrado', 0.00, 0.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre_usuario` varchar(100) NOT NULL,
  `nombre_real` varchar(100) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `id_rol` int(11) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT 'Imgs/dueño.webp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre_usuario`, `nombre_real`, `correo`, `email`, `password_hash`, `id_rol`, `imagen`) VALUES
(5, 'Harold_Miranda', 'Almonaci Miranda', NULL, 'harold70@gmail.com', '$2a$10$.5Elh8fgxypNUWhpUUr/xOa2sZm0VIaE0qWuGGl9otUfobb46T1Pq', 1, 'Imgs/isayo_D.png'),
(9, 'Daniel Puente', NULL, NULL, 'Danpuente@gmail.com', '$2y$10$HemGUchfbXQLZGKcvsycvO7A0Hm.09GgayXNMYALbiDu9by6IY7lm', 2, 'Imgs/Cliente_D.png'),
(10, 'Manu_20', 'Emmanuel Almonaci Miranda', 'Manuel70@gmail.com', '', '$2y$10$p4rtDgQ72mh8QeqfM3fEMeqBjbhO8DH9TV/wW0KREhI30TvJkt0Qy', 4, 'Imgs/dueño.webp');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_contacto`
--

CREATE TABLE `usuarios_contacto` (
  `id_usuario` int(11) NOT NULL,
  `domicilio` text DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios_contacto`
--

INSERT INTO `usuarios_contacto` (`id_usuario`, `domicilio`, `telefono`, `email`) VALUES
(9, 'San Jose de Gracia', '4492581234', 'Danpuente@gmail.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_empleo`
--

CREATE TABLE `usuarios_empleo` (
  `id_usuario` int(11) NOT NULL,
  `sueldo` decimal(10,2) NOT NULL,
  `nss` varchar(11) DEFAULT NULL,
  `area_contrato` varchar(50) DEFAULT NULL,
  `fecha_contrato` date NOT NULL,
  `fin_contrato` date DEFAULT NULL,
  `input_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios_empleo`
--

INSERT INTO `usuarios_empleo` (`id_usuario`, `sueldo`, `nss`, `area_contrato`, `fecha_contrato`, `fin_contrato`, `input_date`) VALUES
(9, 1500.00, '142A1425DÑ', 'Sistemas', '2026-07-27', '2027-07-27', '2026-07-28 01:41:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_fiscal`
--

CREATE TABLE `usuarios_fiscal` (
  `id_usuario` int(11) NOT NULL,
  `rfc` varchar(13) DEFAULT NULL,
  `cue_interbancaria` varchar(18) DEFAULT NULL,
  `num_cuenta_banco` varchar(20) DEFAULT NULL,
  `banco` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios_fiscal`
--

INSERT INTO `usuarios_fiscal` (`id_usuario`, `rfc`, `cue_interbancaria`, `num_cuenta_banco`, `banco`) VALUES
(9, '1425369778241', '142569787152', '', 'BBVA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_identidad`
--

CREATE TABLE `usuarios_identidad` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellidos` varchar(50) NOT NULL,
  `curp` varchar(18) DEFAULT NULL,
  `matricula` varchar(20) DEFAULT NULL,
  `ine` varchar(20) DEFAULT NULL,
  `estado_civil` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios_identidad`
--

INSERT INTO `usuarios_identidad` (`id_usuario`, `nombre`, `apellidos`, `curp`, `matricula`, `ine`, `estado_civil`) VALUES
(9, 'Luis Daniel', 'Puente Ontiveros', '123456789101112131', '24150238', '14255S5AAVDGXV25', 'Casado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id_venta` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `id_turno` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `tipo_entrega` varchar(50) DEFAULT 'sucursal',
  `direccion` text DEFAULT NULL,
  `estado_envio` varchar(20) DEFAULT 'Pendiente',
  `paqueteria` varchar(50) DEFAULT NULL,
  `numero_guia` varchar(100) DEFAULT NULL,
  `fecha_venta` datetime DEFAULT current_timestamp(),
  `metodo_pago` varchar(50) DEFAULT 'Efectivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id_venta`, `id_usuario`, `id_turno`, `total`, `fecha`, `tipo_entrega`, `direccion`, `estado_envio`, `paqueteria`, `numero_guia`, `fecha_venta`, `metodo_pago`) VALUES
(1, 10, NULL, 499.00, '2026-07-27 23:11:47', 'domicilio', 'Calle Nadir 411', 'Enviado', 'Estafeta', '25412536544', '2026-07-27 23:11:47', 'Efectivo'),
(2, 9, 1, 7900.00, '2026-07-27 23:14:22', 'sucursal', NULL, 'Entregado', NULL, NULL, '2026-07-28 07:14:22', 'Efectivo'),
(3, 10, NULL, 1250.00, '2026-07-28 10:47:21', 'domicilio', 'jkhojlkjl', 'Enviado', 'FedEx', 'kjlññlkñlkñlkjkñljlñkjñkj', '2026-07-28 10:47:21', 'Efectivo'),
(4, 10, NULL, 499.00, '2026-07-28 17:42:05', 'sucursal', 'Recoge en Sucursal', 'Pendiente', NULL, NULL, '2026-07-28 17:42:05', 'Efectivo');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cortes_caja`
--
ALTER TABLE `cortes_caja`
  ADD PRIMARY KEY (`id_corte`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id_detalle`);

--
-- Indices de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`id_producto`);

--
-- Indices de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id_permiso`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD PRIMARY KEY (`id_rol`,`id_permiso`),
  ADD KEY `id_permiso` (`id_permiso`);

--
-- Indices de la tabla `turnos_caja`
--
ALTER TABLE `turnos_caja`
  ADD PRIMARY KEY (`id_turno`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `usuarios_contacto`
--
ALTER TABLE `usuarios_contacto`
  ADD PRIMARY KEY (`id_usuario`);

--
-- Indices de la tabla `usuarios_empleo`
--
ALTER TABLE `usuarios_empleo`
  ADD PRIMARY KEY (`id_usuario`);

--
-- Indices de la tabla `usuarios_fiscal`
--
ALTER TABLE `usuarios_fiscal`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `rfc` (`rfc`);

--
-- Indices de la tabla `usuarios_identidad`
--
ALTER TABLE `usuarios_identidad`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `curp` (`curp`),
  ADD UNIQUE KEY `matricula` (`matricula`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cortes_caja`
--
ALTER TABLE `cortes_caja`
  MODIFY `id_corte` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `inventario`
--
ALTER TABLE `inventario`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `turnos_caja`
--
ALTER TABLE `turnos_caja`
  MODIFY `id_turno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cortes_caja`
--
ALTER TABLE `cortes_caja`
  ADD CONSTRAINT `cortes_caja_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD CONSTRAINT `detalle_ventas_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_ventas_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `inventario` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `movimientos`
--
ALTER TABLE `movimientos`
  ADD CONSTRAINT `movimientos_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `inventario` (`id_producto`) ON DELETE CASCADE,
  ADD CONSTRAINT `movimientos_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL;

--
-- Filtros para la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD CONSTRAINT `rol_permisos_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE,
  ADD CONSTRAINT `rol_permisos_ibfk_2` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE SET NULL;

--
-- Filtros para la tabla `usuarios_contacto`
--
ALTER TABLE `usuarios_contacto`
  ADD CONSTRAINT `usuarios_contacto_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios_empleo`
--
ALTER TABLE `usuarios_empleo`
  ADD CONSTRAINT `usuarios_empleo_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios_fiscal`
--
ALTER TABLE `usuarios_fiscal`
  ADD CONSTRAINT `usuarios_fiscal_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios_identidad`
--
ALTER TABLE `usuarios_identidad`
  ADD CONSTRAINT `usuarios_identidad_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
