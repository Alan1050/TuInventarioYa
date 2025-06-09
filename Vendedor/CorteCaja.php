<?php

session_start();

$IDNegocio = $_SESSION['idNegocio'];

// Conexión a la base de datos (ajusta según tu configuración)
$conexion = new mysqli("localhost", "root", "", "stockcerca");

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener fecha actual para el corte
$fecha_actual = date('Y-m-d', strtotime('-1 day'));

// Consulta para obtener total de ventas del día
$query_ventas_dia = "SELECT SUM(PrecioFinal) as total FROM ventas WHERE Fecha = '$fecha_actual' AND id_Negocio = '$IDNegocio'";
$result_ventas_dia = $conexion->query($query_ventas_dia);
$total_ventas = $result_ventas_dia->fetch_assoc()['total'] ?? 0;

// Consulta para producto más vendido del día
$query_producto_mas_vendido = "SELECT Descripcion, SUM(Cantidades) as total_vendido 
                              FROM ventas 
                              WHERE Fecha = '$fecha_actual' AND id_Negocio = '$IDNegocio'
                              GROUP BY Descripcion 
                              ORDER BY total_vendido DESC 
                              LIMIT 1";
$result_producto = $conexion->query($query_producto_mas_vendido);
$producto_mas_vendido = $result_producto->fetch_assoc();

// Consulta para vendedor con más ventas del día
$query_vendedor_top = "SELECT ClaveTrabajador, SUM(PrecioFinal) as total_ventas 
                       FROM ventas 
                       WHERE Fecha = '$fecha_actual' AND id_Negocio = '$IDNegocio'
                       GROUP BY ClaveTrabajador 
                       ORDER BY total_ventas DESC 
                       LIMIT 1";
$result_vendedor = $conexion->query($query_vendedor_top);
$vendedor_top = $result_vendedor->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                <li><a href="AgregarProducto.php">Agregar Producto</a></li>
                <!--<li><a href="Clientes.php">Clientes</a></li>
                <li><a href="Catalogo.php">Catalogo</a></li>-->
            </ul>
        </nav>
    </header>
    

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
                        <p><?php echo htmlspecialchars($producto_mas_vendido['Descripcion']); ?></p>
                        <p class="highlight"><?php echo $producto_mas_vendido['total_vendido']; ?> unidades</p>
                    <?php else: ?>
                        <p>No hay ventas registradas hoy</p>
                    <?php endif; ?>
                </div>
                
                <div class="info-box">
                    <h3>Vendedor Destacado</h3>
                    <?php if ($vendedor_top): ?>
                        <p>Vendedor: <?php echo htmlspecialchars($vendedor_top['ClaveTrabajador']); ?></p>
                        <p class="highlight">$<?php echo number_format($vendedor_top['total_ventas'], 2); ?></p>
                    <?php else: ?>
                        <p>No hay ventas registradas hoy</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <button class="btn btn-print" onclick="window.print()">Imprimir Corte</button>
                <a href="Dashboard.php" class="btn">Volver al Dashboard</a>
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
                    $query_detalle = "SELECT Folio, Descripcion, PreciosU, Cantidades, PrecioFinal, ClaveTrabajador 
                                      FROM ventas 
                                      WHERE Fecha = '$fecha_actual' 
                                      ORDER BY Folio";
                    $result_detalle = $conexion->query($query_detalle);
                    
                    if ($result_detalle->num_rows > 0) {
                        while($row = $result_detalle->fetch_assoc()) {
                            echo "<tr style='border-bottom: 1px solid var(--gray);'>";
                            echo "<td style='padding: 10px;'>" . htmlspecialchars($row['Folio']) . "</td>";
                            echo "<td style='padding: 10px;'>" . htmlspecialchars($row['Descripcion']) . "</td>";
                            echo "<td style='padding: 10px; text-align: right;'>$" . number_format($row['PreciosU'], 2) . "</td>";
                            echo "<td style='padding: 10px; text-align: center;'>" . $row['Cantidades'] . "</td>";
                            echo "<td style='padding: 10px; text-align: right;'>$" . number_format($row['PrecioFinal'], 2) . "</td>";
                            echo "<td style='padding: 10px;'>" . htmlspecialchars($row['ClaveTrabajador']) . "</td>";
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
<?php
$conexion->close();
?>