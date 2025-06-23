<?php
session_start();
include 'include/conn2.php';
$idNegocio = $_SESSION['idNegocio'];

// Recibir parámetros de la URL
$Marca = isset($_GET['marca']) ? pg_escape_string($conn, $_GET['marca']) : null;
$Modelo = isset($_GET['modelo']) ? pg_escape_string($conn, $_GET['modelo']) : null;
$year = isset($_GET['year']) && is_numeric($_GET['year']) ? intval($_GET['year']) : null;

if (!$conn) {
    die("Error de conexión: " . pg_last_error());
}

if (!$Marca || !$Modelo || !$year) {
    die("Faltan parámetros requeridos.");
}

// Consulta SQL para obtener los motores + idcarro
$query = "
    SELECT DISTINCT motor, idcarro 
    FROM carro 
    WHERE marca = '$Marca'
      AND modelo = '$Modelo'
      AND $year BETWEEN 
        substring(anos from 1 for strpos(anos, '-') - 1)::int AND 
        substring(anos from strpos(anos, '-') + 1)::int;
";

$result = pg_query($conn, $query);

if (!$result) {
    die("Error al obtener motores: " . pg_last_error());
}

$motoresConID = [];
while ($row = pg_fetch_assoc($result)) {
    $motoresConID[] = [
        'motor' => htmlspecialchars($row['motor']),
        'id' => $row['idcarro']
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selecciona el motor - <?= htmlspecialchars($Marca) ?> <?= htmlspecialchars($Modelo) ?></title>
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
            margin-bottom: 2rem;
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

        section > div {
            width: 90%;
            margin-left: 5%;
            text-align: center;
            margin-top: 20px;
        }

        section > div > h1 {
            font-size: 25px;
        }

        .buttons {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 2rem;
        }

        .btn-motor {
            background-color: var(--dark);
            color: var(--light);
            text-decoration: none;
            padding: 15px;
            border-radius: 5px;
            font-size: 18px;
            transition: background-color 0.3s ease;
        }

        .btn-motor:hover {
            background-color: var(--secondary);
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
        <h1>Seleccione un motor para <?= htmlspecialchars($Marca) ?> <?= htmlspecialchars($Modelo) ?> (<?= $year ?>)</h1>
        <br><br>

        <?php if (!empty($motoresConID)): ?>
            <div class="buttons">
                <?php foreach ($motoresConID as $item): ?>
                    <a href="./piezas.php?id=<?= $item['id'] ?>" class="btn-motor"><?= $item['motor'] ?></a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No hay motores registrados para <?= htmlspecialchars($Marca) ?> <?= htmlspecialchars($Modelo) ?> en el año <?= $year ?>.</p>
        <?php endif; ?>
    </div>
</section>

</body>
</html>