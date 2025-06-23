<?php
session_start();
include 'include/conn2.php';
$idNegocio = $_SESSION['idNegocio'];

// Recibir parámetros de la URL
$Marca = isset($_GET['marca']) ? pg_escape_string($conn, $_GET['marca']) : null;

// Consulta SQL
$query = "SELECT modelo FROM carro WHERE marca = '$Marca' GROUP BY modelo";

// Ejecutar consulta
$result = pg_query($conn, $query);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo - <?= htmlspecialchars($Marca) ?></title>
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

        .buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 2rem;
        }

        .btn {
            background-color: var(--dark);
            text-decoration: none;
            color: var(--light);
            font-size: 16px;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
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
                <!-- Mostrar modelos del año seleccionado -->
        <h1>Modelos:</h1> <br> <br>
        <div class="buttons">
            <?php while ($row = pg_fetch_assoc($result)): ?>
                <a class="btn" href="años.php?marca=<?= urlencode($Marca) ?>&modelo=<?= $row['modelo'] ?>"><?= htmlspecialchars($row['modelo']) ?></a>
            <?php endwhile; ?>
        </div>
        </div>
</section>

</body>
</html>