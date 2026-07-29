<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login");
    exit();
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
    
    if (isset($_SESSION['rol']) && $_SESSION['rol'] == 4) {
        header("Location: indexcliente");
        exit();
    } 
    else {
        header("Location: index");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Empleado - PruebaTla</title>
    <link rel="stylesheet" href="Diseñoestilo.css">
</head>
<body>
    <div class="registro-usuario-container">
        <h2>Registrar Nuevo Empleado</h2>
        
        <form action="procesar_registro" method="POST">
            
            <div class="seccion-form">
                <h3>1. Datos de Acceso al Sistema</h3>
                <div class="grid-3">
                    <div>
                        <label for="username">Nombre de Usuario:</label>
                        <input type="text" id="username" name="username" placeholder="Ej. harold_admin" required>
                    </div>
                    <div>
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>
                    <div>
                        <label for="id_rol">Rol del usuario:</label>
                        <select id="id_rol" name="id_rol" required>
                            <option value="2">Empleado (Inventario / Caja)</option>
                            <option value="1">Dueño (Control total)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="seccion-form">
                <h3>2. Datos de Identidad</h3>
                <div class="grid-2">
                    <div>
                        <label>Nombre(s):</label>
                        <input type="text" name="nombre" placeholder="Nombre completo" required>
                    </div>
                    <div>
                        <label>Apellidos:</label>
                        <input type="text" name="apellidos" placeholder="Apellidos" required>
                    </div>
                    <div>
                        <label>CURP:</label>
                        <input type="text" name="curp" maxlength="18" placeholder="18 caracteres" required>
                    </div>
                    <div>
                        <label>Matrícula / ID Interno:</label>
                        <input type="text" name="matricula" placeholder="Ej. 24150239" required>
                    </div>
                    <div>
                        <label>INE:</label>
                        <input type="text" name="ine" placeholder="Clave de elector" required>
                    </div>
                    <div>
                        <label>Estado Civil:</label>
                        <select name="ecivil" required>
                            <option value="Soltero">Soltero/a</option>
                            <option value="Casado">Casado/a</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="seccion-form">
                <h3>3. Datos de Empleo y Finanzas</h3>
                <div class="grid-2">
                    <div>
                        <label>Sueldo Mensual ($ MXN):</label>
                        <input type="number" step="0.01" name="sueldo" placeholder="0.00" required>
                    </div>
                    <div>
                        <label>NSS (Seguro Social):</label>
                        <input type="text" name="nss" placeholder="Número de Seguridad Social" required>
                    </div>
                    <div>
                        <label>Área de Contrato:</label>
                        <input type="text" name="area" placeholder="Ej. Sistemas / Logística" required>
                    </div>
                    <div>
                        <label>RFC:</label>
                        <input type="text" name="rfc" maxlength="13" placeholder="Registro Federal de Contribuyentes" required>
                    </div>
                    <div>
                        <label>Fecha de Inicio:</label>
                        <input type="date" name="fecha_inicio" required>
                    </div>
                    <div>
                        <label>Fecha de Fin de Contrato (Opcional):</label>
                        <input type="date" name="fecha_fin">
                    </div>
                    <div>
                        <label>Banco:</label>
                        <input type="text" name="banco" placeholder="Ej. BBVA / Bancomer" required>
                    </div>
                    <div>
                        <label>Cuenta / CLABE:</label>
                        <input type="text" name="clabe" placeholder="18 dígitos CLABE">
                        <input type="hidden" name="cuenta" value=""> 
                    </div>
                </div>
            </div>

            <div class="seccion-form">
                <h3>4. Ubicación y Contacto</h3>
                <div class="grid-2">
                    <div>
                        <label>Teléfono:</label>
                        <input type="text" name="telefono" placeholder="10 dígitos" required>
                    </div>
                    <div>
                        <label>Email Personal:</label>
                        <input type="email" name="email" placeholder="correo@ejemplo.com" required>
                    </div>
                    <div style="grid-column: span 2;">
                        <label>Domicilio Completo:</label>
                        <input type="text" name="domicilio" placeholder="Calle, número, colonia, ciudad" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-registro-usuario">Guardar Registro Completo</button>
        </form>
        
        <div style="text-align: center; margin-top: 25px;">
            <a href="index" class="volver-link-usuario" onclick="return confirm('¿Seguro que quieres salir? Perderás los datos capturados.');">← Volver a la lista principal</a>
        </div>
    </div>
</body>
</html>