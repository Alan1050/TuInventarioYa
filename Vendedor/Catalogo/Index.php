<?php

session_start();
include 'include/conn2.php';
$idNegocio = $_SESSION['idNegocio'];

// Consulta SQL
$query = "SELECT marca FROM carro GROUP BY marca";

// Ejecutar consulta
$result = pg_query($conn, $query);

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

        section{
            width: 90%;
            margin-left: 5%;
            display: grid;
            grid-template-columns: 1.7fr .3fr;
            margin-top: 15px;
        }

        section>.pt1>h1{
            text-align: center;
            font-size: 25px;
        }

        section>div>div{
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
            text-align: center;
            gap: 20px;
        }

        section>button{
            width: 100%;
            font-size: 20px;
            background-color: var(--primary);
            color: var(--light);
            height: 100px;
            border-radius: 10px;
            margin-top: 50px;
            cursor: pointer;
            border: 0px;
        }
        section>div>div>a:hover{
            background-color: var(--secondary);
            color: var(--dark);
            font-size: 26px;
            transition: .5s linear;
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
            </ul>
        </nav>
    </header>

    <section>
        <div class="pt1">
            <h1>Seleccione una marca</h1> <br> <br>

            <div id="containerMarcas" class="Marcas">
                <?php while ($row = pg_fetch_assoc($result)): ?>
                    <a href='./modelo.php?marca=<?= htmlspecialchars($row['marca']) ?>' class="btn"><?= htmlspecialchars($row['marca']) ?></a>
                <?php endwhile; ?>
            </div>
        </div>
        <button onclick="window.location.href='./registrarCatalogo.php'">Agregar Nueva Coleccion</button>
    </section>




</body>
</html>