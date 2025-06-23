<?php
session_start();
$IDNegocio = intval($_SESSION['idNegocio']);

// Incluir conexión
include_once "../include/conn.php";

// Obtener fecha actual para el corte (ayer)
$fecha_actual = date('Y-m-d',strtotime('-1 day'));

// Consulta para obtener total de ventas del día
$query_ventas_dia = "SELECT SUM(preciofinal) as total FROM ventas WHERE fecha = '$fecha_actual' AND idnegocio = '$IDNegocio'";
$result_ventas_dia = pg_query($conn, $query_ventas_dia);
$total_ventas = 0;
if ($row = pg_fetch_assoc($result_ventas_dia)) {
    $total_ventas = $row['total'] ?? 0;
}

// Consulta para producto más vendido del día
$query_producto_mas_vendido = "SELECT descripcion, SUM(cantidades) as totalvendido 
                              FROM ventas 
                              WHERE fecha = '$fecha_actual' AND idnegocio = '$IDNegocio'
                              GROUP BY descripcion
                              ORDER BY totalvendido DESC
                              LIMIT 1";

$result_producto = pg_query($conn, $query_producto_mas_vendido);
if (!$result_producto) {
    die("Error en consulta producto más vendido: " . pg_last_error($conn));
}
$producto_mas_vendido = pg_fetch_assoc($result_producto);

// Consulta para vendedor con más ventas del día
$query_vendedor_top = "SELECT clavetrabajador, SUM(preciofinal) as total_ventas 
                       FROM ventas 
                       WHERE fecha = '$fecha_actual' AND idnegocio = '$IDNegocio'
                       GROUP BY clavetrabajador 
                       ORDER BY total_ventas DESC 
                       LIMIT 1";
$result_vendedor = pg_query($conn, $query_vendedor_top);
$vendedor_top = pg_fetch_assoc($result_vendedor);

// Detalle de ventas
$query_detalle = "SELECT folio, descripcion, preciosu, cantidades, preciofinal, clavetrabajador 
                  FROM ventas 
                  WHERE fecha = '$fecha_actual' AND idnegocio = '$IDNegocio'
                  ORDER BY folio";
$result_detalle = pg_query($conn, $query_detalle);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Corte de Caja - TuInventarioYa</title>
     <style>
        :root {
          --primary: #0a2463;
          --secondary: #3e92cc;
          --success: #4cb944;
          --warning: #ffc857;
          --danger: #d8315b;
          --dark: #2e2e2e;
          --light: #f5f5f5;
          --gray: #a0a0a0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background-color: var(--light);
            color: var(--dark);
        }
        
        header {
            background-color: var(--primary);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        header h1 {
            font-size: 1.8rem;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            margin: 0 0.2rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        nav ul li a:hover {
            background-color: var(--secondary);
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card h2 {
            color: var(--primary);
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--secondary);
            padding-bottom: 0.5rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .info-box {
            background-color: var(--light);
            padding: 1.5rem;
            border-radius: 6px;
            border-left: 4px solid var(--secondary);
        }
        
        .info-box h3 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .info-box p {
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .highlight {
            color: var(--success);
            font-size: 2rem;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--secondary);
        }
        
        .btn-print {
            background-color: var(--warning);
            color: var(--dark);
        }
        
        .btn-print:hover {
            background-color: #e6b740;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-3 {
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <header>
        <h1>TuInventarioYa</h1>
        <nav>
            <ul>
                <li><a href="Dashboard.php">Dashboard</a></li>
                <li><a href="CorteCaja.php">Corte Caja</a></li>
                <li><a href="Inventario.php">Inventario</a></li>
            </ul>
        </nav>
    </header>
    
<!-- Modal con scroll vertical -->
<div id="modalProductosAgotarse" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:9999;">
    <!-- Contenido del modal con scroll -->
    <div style="
        background-color:white;
        margin:5% auto;
        padding:2rem;
        border-radius:8px;
        width:90%;
        max-width:800px;
        max-height:80vh; /* Máximo 80% de la altura de la pantalla */
        overflow-y:auto; /* Habilita scroll si el contenido es muy largo */
        box-shadow:0 4px 10px rgba(0,0,0,0.2);">
        
        <!-- Botón cerrar -->
        <span 
            onclick="document.getElementById('modalProductosAgotarse').style.display='none'" 
            style="float:right; cursor:pointer; font-size:1.5rem;">&times;</span>
        
        <!-- Título -->
        <h2>Productos por Agotarse (Existencia menor a 3)</h2>
        
        <!-- Tabla de productos -->
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background-color:#f0f0f0;">
                    <th style="padding:10px; text-align:left;">Código</th>
                    <th style="padding:10px; text-align:left;">Producto</th>
                    <th style="padding:10px; text-align:center;">Existencia</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Consulta para obtener productos vendidos del día
                $query_codigos_vendidos = "SELECT DISTINCT codigosbarras FROM ventas WHERE fecha = '$fecha_actual' AND idnegocio = '$IDNegocio'";
                $result_codigos_vendidos = pg_query($conn, $query_codigos_vendidos);

                if ($result_codigos_vendidos && pg_num_rows($result_codigos_vendidos) > 0) {
                    while ($row_codigo = pg_fetch_assoc($result_codigos_vendidos)) {
                        $codigo = $row_codigo['codigosbarras'];

                        // Consultar existencia actual en inventario
                        $query_existencia = "SELECT nombre, existencia FROM producto WHERE codigobarras = '$codigo' AND idnegocio = '$IDNegocio'";
                        $result_existencia = pg_query($conn, $query_existencia);
                        if ($result_existencia && pg_num_rows($result_existencia) > 0) {
                            $row_existencia = pg_fetch_assoc($result_existencia);
                            if ($row_existencia['existencia'] <= 3) {
                                echo "<tr style='border-bottom:1px solid #ddd;'>";
                                echo "<td style='padding:8px;'>$codigo</td>";
                                echo "<td style='padding:8px;'>" . htmlspecialchars($row_existencia['nombre']) . "</td>";
                                echo "<td style='padding:8px; text-align:center; color:" . ($row_existencia['existencia'] == 0 ? "red" : "orange") . ";'>"
                                    . $row_existencia['existencia'] . "</td>";
                                echo "</tr>";
                            }
                        }
                    }
                } else {
                    echo "<tr><td colspan='3' style='text-align:center; padding:10px;'>No hay productos vendidos hoy</td></tr>";
                }
                ?>
            </tbody>
        </table>
        
        <!-- Botón Cerrar -->
        <br>
        <button class="btn" onclick="document.getElementById('modalProductosAgotarse').style.display='none'">Cerrar</button>
    </div>
</div>

    <?php
    $fecha_actual2 = date('d-m-Y', strtotime('-1 day'));
    $Fecha = str_replace("-", "/", $fecha_actual2);
    ?>
    
    <div class="container">
        <div class="card">
            <h2>Corte de Caja - <?php echo $Fecha; ?></h2>
            <div class="info-grid">
                <div class="info-box">
                    <h3>Total de Ventas del Día</h3>
                    <p class="highlight">$<?php echo number_format($total_ventas, 2); ?></p>
                </div>
                <div class="info-box">
                    <h3>Producto Más Vendido</h3>
                    <?php if ($producto_mas_vendido): ?>
                        <p><?php echo htmlspecialchars($producto_mas_vendido['descripcion']); ?></p>
                        <p class="highlight"><?php echo $producto_mas_vendido['totalvendido']; ?> unidades</p>
                    <?php else: ?>
                        <p>No hay ventas registradas hoy</p>
                    <?php endif; ?>
                </div>
                <div class="info-box">
                    <h3>Vendedor Destacado</h3>
                    <?php if ($vendedor_top): ?>
                        <p>Vendedor: <?php echo htmlspecialchars($vendedor_top['clavetrabajador']); ?></p>
                        <p class="highlight">$<?php echo number_format($vendedor_top['total_ventas'], 2); ?></p>
                    <?php else: ?>
                        <p>No hay ventas registradas hoy</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-center mt-3">
                <button class="btn btn-print" onclick="window.print()">Imprimir Corte</button>
                <button class="btn" onclick="document.getElementById('modalProductosAgotarse').style.display='block'">Ver productos por agotarse de hoy</button>
            </div>
        </div>
        <div class="card">
            <h2>Detalle de Ventas del Día</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: var(--primary); color: white;">
                        <th style="padding: 10px; text-align: left;">Folio</th>
                        <th style="padding: 10px; text-align: left;">Producto</th>
                        <th style="padding: 10px; text-align: right;">Precio Unitario</th>
                        <th style="padding: 10px; text-align: center;">Cantidad</th>
                        <th style="padding: 10px; text-align: right;">Total</th>
                        <th style="padding: 10px; text-align: left;">Vendedor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (pg_num_rows($result_detalle) > 0) {
                        while($row = pg_fetch_assoc($result_detalle)) {
                            echo "<tr style='border-bottom: 1px solid var(--gray);'>";
                            echo "<td style='padding: 10px;'>" . htmlspecialchars($row['folio']) . "</td>";
                            echo "<td style='padding: 10px;'>" . htmlspecialchars($row['descripcion']) . "</td>";
                            echo "<td style='padding: 10px; text-align: right;'>$" . number_format($row['preciosu'], 2) . "</td>";
                            echo "<td style='padding: 10px; text-align: center;'>" . $row['cantidades'] . "</td>";
                            echo "<td style='padding: 10px; text-align: right;'>$" . number_format($row['preciofinal'], 2) . "</td>";
                            echo "<td style='padding: 10px;'>" . htmlspecialchars($row['clavetrabajador']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='padding: 10px; text-align: center;'>No hay ventas registradas hoy</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>