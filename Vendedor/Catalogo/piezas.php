<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'include/conn2.php';
$idNegocio = $_SESSION['idNegocio'];

// Obtener idCarro de la URL
$idCarro = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$idCarro) {
    die("ID de carro no válido");
}

// Categorías
$categorias = [
    'Afinacion',
    'Diferencial',
    'Embrague',
    'Enfriamiento',
    'Frenos',
    'Ignicion',
    'Lubricante',
    'Combustible',
    'Motor',
    'Suspencion',
    'Traccion',
    'Transmicion',
    'Miscelanea'
];

$resultados = [];

// Buscar piezas por categoría
foreach ($categorias as $categoria) {
    $query = "SELECT * FROM piezas WHERE idcarro = $1 AND categoria = $2";
    $result = pg_query_params($conn, $query, [$idCarro, $categoria]);

    if (!$result) {
        die("Error en consulta para $categoria: " . pg_last_error());
    }

    $resultados[$categoria] = pg_fetch_all($result);
}

// Obtener TODAS las piezas del carro SIN filtro de categoría
$query_todas_piezas = "SELECT * FROM piezas WHERE idcarro = $1";
$result_todas_piezas = pg_query_params($conn, $query_todas_piezas, [$idCarro]);

if (!$result_todas_piezas) {
    die("Error al obtener todas las piezas: " . pg_last_error());
}

$todasLasPiezas = pg_fetch_all($result_todas_piezas); // Esta sí contiene todas

// Obtener datos del carro
$DatosCarro = "SELECT * FROM carro WHERE idcarro = $1";
$resultDatosCarro = pg_query_params($conn, $DatosCarro, [$idCarro]);

if (!$resultDatosCarro) {
    die("Error al obtener datos del carro: " . pg_last_error());
}

$carroData = pg_fetch_assoc($resultDatosCarro);

if (!$carroData) {
    die("No se encontró ningún carro con ese ID.");
}

// Manejo de edición o agregado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        if ($_POST['accion'] === 'agregar_pieza') {
            $descripcion = pg_escape_string($conn, $_POST['descripcion']);
            $codigo = pg_escape_string($conn, $_POST['codigo']);
            $categoria = pg_escape_string($conn, $_POST['categoria']);

            $query = "INSERT INTO piezas (idcarro, descripcion, codigo, categoria)
                      VALUES ($1, $2, $3, $4)";
            $res = pg_query_params($conn, $query, [$idCarro, $descripcion, $codigo, $categoria]);
            echo json_encode(['mensaje' => $res ? 'Pieza agregada' : 'Error']);
            exit;
        } elseif ($_POST['accion'] === 'eliminar_pieza') {
            $idpieza = intval($_POST['idpieza']);
            $query = "DELETE FROM piezas WHERE idpiezas = $1";
            $res = pg_query_params($conn, $query, [$idpieza]);
            echo json_encode(['mensaje' => $res ? 'Pieza eliminada' : 'Error']);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CATÁLOGO</title>
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

        body {
            background-color: var(--light);
            color: var(--dark);
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: var(--primary);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 10px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
        }

        nav ul li a:hover {
            background-color: var(--secondary);
        }

        main {
            width: 90%;
            margin: 20px auto;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        /* Botón de categoría */
        .categoria-header {
            background-color: var(--dark);
            color: var(--light);
            cursor: pointer;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            font-size: 1.1rem;
            transition: background-color 0.3s;
        }

        .categoria-header:hover {
            background-color: var(--secondary);
        }

        .categoria-body {
            display: none;
            padding-left: 10px;
            padding-right: 10px;
            margin-bottom: 30px;
            border-left: 3px solid var(--secondary);
        }

        .op {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }

        .pieza-card {
            background-color: var(--secondary);
            color: var(--light);
            padding: 1rem;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .btn-grande {
            display: block;
            width: 100%;
            max-width: 200px;
            margin: 30px auto;
            background-color: var(--primary);
            color: var(--light);
            font-size: 20px;
            padding: 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-grande:hover {
            background-color: var(--secondary);
        }

        /* Modal */
        #modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.6);
            z-index: 9999;
            overflow-y: auto;
        }

        #modal .contenido {
            background-color: var(--light);
            margin: 5% auto;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        thead {
            background-color: var(--primary);
            color: white;
        }

        th, td {
            padding: 10px;
            border: 1px solid var(--gray);
            text-align: center;
        }

        form {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-top: 20px;
        }

        form label {
            display: flex;
            flex-direction: column;
            font-size: 14px;
        }

        form button {
            grid-column: span 3;
            justify-self: start;
        }

        .cerrar-modal {
            margin-top: 20px;
            background-color: var(--danger);
        }

        .cerrar-modal:hover {
            background-color: #c00;
        }

        .btn-eliminar {
    background-color: var(--danger);
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-eliminar:hover {
    background-color: #c00;
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

<main>
    <h2>Piezas disponibles para el Carro: <?= htmlspecialchars($carroData['marca']) ?> | <?= htmlspecialchars($carroData['modelo']) ?> | <?= htmlspecialchars($carroData['anos']) ?> | <?= htmlspecialchars($carroData['motor']) ?></h2>

    <!-- Botón grande para editar todas -->
    <button class="btn-grande" onclick="abrirModal()">Editar Catálogo</button>

    <?php foreach ($categorias as $categoria): ?>
        <?php ${$categoria} = $resultados[$categoria] ?? []; ?>

        <div class="categoria">
            <div class="categoria-header" onclick="toggleCategoria('<?= $categoria ?>')">
                <?= htmlspecialchars($categoria ." (Presione para mostrar)") ?>
            </div>
            <div class="categoria-body" id="<?= $categoria ?>">
                <?php if (!empty(${$categoria})): ?>
                    <div class="op">
                        <?php foreach (${$categoria} as $pieza): ?>
                            <div class="pieza-card">
                                <?= htmlspecialchars($pieza['descripcion'] ?? '') ?> -<br>
                                <strong><?= htmlspecialchars($pieza['codigo']) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No hay piezas registradas en esta categoría.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</main>

<!-- Modal -->
<div id="modal">
    <div class="contenido">
        <h2>Datos del Carro</h2>
        <p><strong>Marca:</strong> <?= htmlspecialchars($carroData['marca']) ?></p>
        <p><strong>Modelo:</strong> <?= htmlspecialchars($carroData['modelo']) ?></p>
        <p><strong>Año:</strong> <?= htmlspecialchars($carroData['anos']) ?></p>
        <p><strong>Motor:</strong> <?= htmlspecialchars($carroData['motor']) ?></p>

        <h3>Todas las Piezas Registradas</h3>
        <table>
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Descripción</th>
                    <th>Código</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-piezas-modal">
                <!-- Piezas dinámicas aquí -->
            </tbody>
        </table>

        <h3>Agregar Nueva Pieza</h3>
        <form id="form-agregar">
            <label>Descripción<input type="text" name="descripcion" required></label>
            <label>Código<input type="text" name="codigo" required></label>
            <label>Categoría
                <select name="categoria" required>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn-grande">Agregar Pieza</button>
        </form>

        <button onclick="cerrarModal()" class="btn-grande" style="background-color: var(--danger);">Cerrar</button>
    </div>
</div>

<script>

    // Recuperar todas las piezas del carro
    const todasLasPiezas = <?= json_encode($todasLasPiezas) ?>;

    function toggleCategoria(id) {
        const elemento = document.getElementById(id);
        const all = document.querySelectorAll('.categoria-body');
        // Ocultar todos los demás
        all.forEach(el => {
            if (el.id !== id) el.style.display = 'none';
        });
        // Mostrar u ocultar el seleccionado
        elemento.style.display = (elemento.style.display === 'block') ? 'none' : 'block';
    }

function abrirModal() {
    const tbody = document.getElementById('tabla-piezas-modal');
    tbody.innerHTML = '';
    todasLasPiezas.forEach(p => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${p.categoria}</td>
            <td>${p.descripcion}</td>
            <td>${p.codigo}</td>
            <td><button class="btn-eliminar" onclick="eliminarPieza(${p.idpieza}, this)">Eliminar</button></td>
        `;
        tbody.appendChild(tr);
    });
    document.getElementById('modal').style.display = 'block';
}

    function cerrarModal() {
        document.getElementById('modal').style.display = 'none';
    }

    document.getElementById('form-agregar')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('accion', 'agregar_pieza');

        fetch('', {
            method: 'POST',
            body: formData
        }).then(res => res.json())
         .then(data => {
             alert(data.mensaje);
             location.reload();
         });
    });

    function eliminarPieza(idpieza, boton) {
    if (!confirm("¿Estás seguro de que deseas eliminar esta pieza?")) return;

    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'accion=eliminar_pieza&idpieza=' + idpieza
    })
    .then(res => res.json())
    .then(data => {
        if (data.mensaje === 'Pieza eliminada') {
            // Eliminar la fila de la tabla
            const row = boton.closest('tr');
            row.remove();
            alert('Pieza eliminada correctamente');
        } else {
            alert('Error al eliminar la pieza');
        }
    });
}
</script>

</body>
</html>