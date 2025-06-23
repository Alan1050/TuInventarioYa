<?php
session_start();
include 'include/conn2.php';
$idNegocio = $_SESSION['idNegocio'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Datos del carro
    $marca = pg_escape_string($conn, strtoupper($_POST['marca']));
    $modelo = pg_escape_string($conn, strtoupper($_POST['modelo']));
    $anos = pg_escape_string($conn, $_POST['anos']);
    $motor = pg_escape_string($conn, $_POST['motor']);

    // Insertar carro
    $query_carro = "INSERT INTO carro (marca, modelo, anos, motor) VALUES ('$marca', '$modelo', '$anos', '$motor') RETURNING idcarro";
    $result_carro = pg_query($conn, $query_carro);

    if (!$result_carro) {
        die("Error al guardar carro: " . pg_last_error());
    }

    $idcarro = pg_fetch_result($result_carro, 0, 'idcarro');

    // Guardar piezas
    if (!empty($_POST['piezas']) && is_array($_POST['piezas'])) {
        foreach ($_POST['piezas'] as $pieza) {
            // Validar que todos los campos estén presentes y no vacíos
            if (
                empty($pieza['codigo']) ||
                empty($pieza['descripcion']) ||
                empty($pieza['categoria'])
            ) {
                die("Error: Todos los campos de las piezas son obligatorios.");
            }

            $codigo = pg_escape_string($conn, strtoupper($pieza['codigo']));
            $descripcion = pg_escape_string($conn, strtoupper($pieza['descripcion']));
            $categoria = pg_escape_string($conn, $pieza['categoria']);

            $query_pieza = "INSERT INTO piezas (codigo, descripcion, categoria, idcarro) 
                            VALUES ('$codigo', '$descripcion', '$categoria', $idcarro)";
            $result_pieza = pg_query($conn, $query_pieza);

            if (!$result_pieza) {
                die("Error al guardar pieza: " . pg_last_error());
            }
        }
    }

    echo "<script>alert('Carro y piezas registrados exitosamente'); window.location.href='registrarCatalogo.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Catálogo - TuInventarioYa</title>
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

        main {
            max-width: 1000px;
            margin: auto;
        }

        form {
            background-color: var(--light);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        h2 {
            margin-bottom: 1rem;
            color: var(--primary);
        }

        label {
            display: block;
            margin-top: 1rem;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"],
        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 0.5rem;
            margin-top: 0.3rem;
            border: 1px solid var(--gray);
            border-radius: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        table th,
        table td {
            border: 1px solid var(--gray);
            padding: 0.5rem;
            text-align: left;
        }

        table th {
            background-color: var(--secondary);
            color: white;
        }

        button[type="button"] {
            background-color: var(--success);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 1rem;
        }

        button[type="submit"] {
            background-color: var(--primary);
            margin-top: 2rem;
            color: var(--light);
            font-size: 15px;
            border: 0px;
            border-radius: 4px;
            padding: 0.5rem 1rem;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .actions button {
            font-size: 0.9rem;
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
            <li><a href="javascript:history.back()">Volver</a></li>
        </ul>
    </nav>
</header>

<main>
    <form method="post">
        <h2>Registrar Carro</h2>

        <label for="marca">Marca:</label>
        <input type="text" name="marca" required autocomplete="off">

        <label for="modelo">Modelo:</label>
        <input type="text" name="modelo" required autocomplete="off">

        <label for="anos">Años (ej. 2000-2005):</label>
        <input type="text" name="anos" placeholder="2000-2005" required autocomplete="off">

        <label for="motor">Motor:</label>
        <input type="text" name="motor" required placeholder="1.5" autocomplete="off">

        <h2>Piezas del Carro</h2>

        <table id="tablaPiezas">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Categoría</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- Las filas se añadirán dinámicamente aquí -->
            </tbody>
        </table>

        <button type="button" onclick="agregarFila()">+ Agregar Pieza</button>

        <button type="submit">Guardar Catálogo</button>
    </form>
</main>

<script>
    let filaIndex = 0;

    function agregarFila() {
        const tbody = document.querySelector("#tablaPiezas tbody");
        const tr = document.createElement("tr");

        tr.innerHTML = `
            <td><input type="text" name="piezas[${filaIndex}][codigo]" required autocomplete="off"></td>
            <td><input type="text" name="piezas[${filaIndex}][descripcion]" required autocomplete="off"></td>
            <td>
                <select name="piezas[${filaIndex}][categoria]" required>
                    <option value="">Selecciona...</option>
                    <option value="Afinacion">Afinación</option>
                    <option value="Diferencial">Diferencial</option>
                    <option value="Embrague">Embrague</option>
                    <option value="Enfriamiento">Enfriamiento</option>
                    <option value="Frenos">Frenos</option>
                    <option value="Ignicion">Ignición</option>
                    <option value="Lubricante">Lubricante</option>
                    <option value="Combustible">Combustible</option>
                    <option value="Motor">Motor</option>
                    <option value="Suspencion">Suspensión</option>
                    <option value="Traccion">Tracción</option>
                    <option value="Transmicion">Transmisión</option>
                    <option value="Miscelanea">Miscelánea</option>
                </select>
            </td>
            <td class="actions">
                <button type="button" onclick="this.closest('tr').remove()" style="background-color: var(--danger);">Eliminar</button>
            </td>
        `;

        tbody.appendChild(tr);
        filaIndex++;
    }

    // Validación antes de enviar (opcional)
    document.querySelector('form').addEventListener('submit', function(event) {
        const rows = document.querySelectorAll('#tablaPiezas tbody tr');
        let isValid = true;

        rows.forEach(row => {
            const codigoInput = row.querySelector('[name^="piezas"][name*="[codigo]"]');
            const descripcionInput = row.querySelector('[name^="piezas"][name*="[descripcion]"]');
            const categoriaSelect = row.querySelector('[name^="piezas"][name*="[categoria]"]');

            if (!codigoInput.value || !descripcionInput.value || !categoriaSelect.value) {
                alert('Todos los campos son obligatorios.');
                isValid = false;
            }
        });

        if (!isValid) {
            event.preventDefault();
        }
    });
</script>

</body>
</html>