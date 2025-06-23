<?php

session_start();
include 'include/conn2.php';
$idNegocio = $_SESSION['idNegocio'];

// Recibir la marca desde GET
$Marca = $_GET['marca'];
$Modelo = $_GET['modelo'];


if (!$conn) {
    die("Error de conexión: " . pg_last_error());
}

// Consulta SQL para obtener los rangos de años de esa marca
$query = "SELECT anos FROM carro WHERE marca = '$Marca' AND modelo = '$Modelo'";
$result = pg_query($conn, $query);

if (!$result) {
    die("Error en consulta: " . pg_last_error());
}

// Array para guardar todos los años únicos
$allYears = [];

// Procesamos cada rango obtenido
while ($row = pg_fetch_assoc($result)) {
    $range = $row['anos'];
    if (strpos($range, '-') !== false) {
        list($start, $end) = explode('-', $range);
        $start = intval($start);
        $end = intval($end);

        if ($start <= $end) {
            for ($year = $start; $year <= $end; $year++) {
                $allYears[$year] = $year; // Usamos clave asociativa para evitar duplicados
            }
        }
    }
}

// Ordenar y limpiar índices
ksort($allYears);
$uniqueYears = array_values($allYears);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CATALOGO</title>
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

        .btn{
            background-color: var(--dark);
            text-decoration: none;
            color: var(--light);
            font-size: 25px;
            cursor: pointer;
            padding: 10px;
            border: 0px;
            border-radius: 5px;
        }

        section>div{
            width: 90%;
            margin-left: 5%;
            text-align: center;
            margin-top: 20px;
        }

        section>div>h1{
            font-size: 25px;
        }

                .buttons{
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr 1fr ;
            text-align: center;
            gap: 20px;
        }

    </style>
</head>
<body>
    <header>
        <h1>TuInventarioYa</h1>
        <nav>
            <ul>
                <li><a href="../Dashboard.php">Dashboard</a></li>
                <li><a href="../CorteCaja.php">Corte Caja</a></li>
                <li><a href="../Inventario.php">Inventario</a></li>
                                <li><a href="javascript:history.back()">Volver</a></li>
            </ul>
        </nav>
    </header>

    <section>
        <div>
            <h1>Seleccione el año</h1> <br> <br>

        <div class="buttons">
            <?php foreach ($uniqueYears as $year): ?>
                <a href="./motor.php?marca=<?= urlencode($Marca) ?>&modelo=<?= $Modelo ?>&year=<?= $year ?>" class="btn"><?= $year ?></a>
            <?php endforeach; ?>
        </div>
        </div>
    </section>

</body>
</html>