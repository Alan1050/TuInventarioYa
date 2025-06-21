<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "catalogo_carros";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) die("Error de conexión");

// Manejar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'agregar_marca':
                $nombre = $conn->real_escape_string($_POST['nombre']);
                $conn->query("INSERT INTO marcas (nombre) VALUES ('$nombre')");
                break;

            case 'agregar_modelo':
                $marca_id = intval($_POST['marca_id']);
                $nombre = $conn->real_escape_string($_POST['nombre']);
                $anio_inicio = intval($_POST['anio_inicio']);
                $anio_fin = intval($_POST['anio_fin']);
                $motor = $conn->real_escape_string($_POST['motor']);
                $conn->query("INSERT INTO modelos (marca_id, nombre, anio_inicio, anio_fin, motor)
                              VALUES ($marca_id, '$nombre', $anio_inicio, $anio_fin, '$motor')");
                $_SESSION['modelo_actual'] = $conn->insert_id;
                break;

            case 'agregar_pieza':
                $modelo_id = intval($_SESSION['modelo_actual']);
                $categoria_id = intval($_POST['categoria_id']);
                $nombre = $conn->real_escape_string($_POST['nombre']);
                $descripcion = $conn->real_escape_string($_POST['descripcion']);
                $conn->query("INSERT INTO piezas (modelo_id, categoria_id, nombre, descripcion)
                              VALUES ($modelo_id, $categoria_id, '$nombre', '$descripcion')");
                break;
        }
    }

    header("Location: registro.php");
    exit;
}

// Obtener datos para selects
function obtenerMarcas($conn) {
    $result = $conn->query("SELECT id, nombre FROM marcas");
    $marcas = [];
    while ($row = $result->fetch_assoc()) $marcas[] = $row;
    return $marcas;
}

function obtenerCategorias($conn) {
    $result = $conn->query("SELECT id, nombre FROM categorias");
    $categorias = [];
    while ($row = $result->fetch_assoc()) $categorias[] = $row;
    return $categorias;
}

$marcas = obtenerMarcas($conn);
$categorias = obtenerCategorias($conn);
$modeloActualId = $_SESSION['modelo_actual'] ?? null;
$piezas = [];

if ($modeloActualId) {
    $res = $conn->query("SELECT p.nombre, p.descripcion, c.nombre AS categoria FROM piezas p
                        JOIN categorias c ON p.categoria_id = c.id
                        WHERE p.modelo_id = $modeloActualId");
    while ($row = $res->fetch_assoc()) {
        $piezas[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Registro de Carros</title>
  <link rel="stylesheet" href="css/styles.css" />
  <style>
    :root {
      --primary: #0a2463;
      --secondary: #3e92cc;
      --success: #4cb944;
      --warning: #ffc857;
      --danger: #d8315b;
      --dark: #2e2e2e;
      --light: #f5f5f5;
      --gray: #e0e0e0;
      --text-light: #7a7a7a;
    }

    * {
      padding: 0px;
      margin: 0px;
      font-family: 'Segoe UI', sans-serif;
    }

    body {
      margin: 0;
      background: var(--light);
    }

    .container {
      max-width: 1000px;
      margin: auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    h1, h2 {
      text-align: center;
      color: var(--dark);
    }

    label {
      display: block;
      margin-top: 10px;
      font-weight: bold;
      color: var(--dark);
    }

    input[type="text"],
    input[type="number"],
    select,
    textarea {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid var(--gray);
      border-radius: 6px;
      font-size: 14px;
    }

    button {
      background: var(--primary);
      color: white;
      padding: 10px 15px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      margin-top: 10px;
      transition: background 0.3s ease;
    }

    button:hover {
      background: var(--secondary);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    th, td {
      border-bottom: 1px solid var(--gray);
      padding: 10px;
    }

    .split {
      display: flex;
      gap: 20px;
      margin-top: 20px;
    }

    .left, .right {
      flex: 1;
      background: var(--light);
      padding: 15px;
      border-radius: 8px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .alert {
      background-color: var(--warning);
      color: var(--dark);
      padding: 10px;
      border-left: 4px solid var(--danger);
      margin-top: 10px;
      display: none;
    }

    .table-container {
      margin-top: 10px;
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

        header>h1{
            color: var(--light);
            margin-bottom: 1.5rem;
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
    <br>

  <div class="container">
    <h1>🔧 Registro de Carros</h1>

    <!-- Agregar Marca -->
    <div class="form-group">
      <h2>Agregar Nueva Marca</h2>
      <form method="post">
        <input type="hidden" name="accion" value="agregar_marca">
        <input type="text" name="nombre" placeholder="Nombre de la marca" required>
        <button type="submit">Agregar Marca</button>
      </form>
    </div>

    <!-- Agregar Modelo -->
    <div class="form-group">
      <h2>Agregar Nuevo Modelo</h2>
      <form method="post">
        <input type="hidden" name="accion" value="agregar_modelo">
        <select name="marca_id" required>
          <option value="">-- Selecciona una marca --</option>
          <?php foreach ($marcas as $m): ?>
            <option value="<?= $m['id'] ?>"><?= $m['nombre'] ?></option>
          <?php endforeach; ?>
        </select>
        <input type="text" name="nombre" placeholder="Nombre del modelo" required>
        <input type="number" name="anio_inicio" placeholder="Año inicio" required>
        <input type="number" name="anio_fin" placeholder="Año fin" required>
        <input type="text" name="motor" placeholder="Motor (ej: 1.6L)" required>
        <button type="submit">Guardar Modelo</button>
      </form>
    </div>

    <!-- Mostrar modelo actual -->
    <?php if ($modeloActualId): ?>
      <div class="form-group">
        <h2>Piezas</h2>
      </div>
    <?php endif; ?>

    <!-- Agregar Piezas -->
    <div class="split">
      <div class="left">
        <h2>Agregar Piezas</h2>
        <form method="post">
          <input type="hidden" name="accion" value="agregar_pieza">
          <input type="hidden" name="modelo_id" value="<?= $modeloActualId ?? '' ?>" readonly>

          <label>Código:</label>
          <input type="text" name="nombre" placeholder="Código de la pieza" required>

          <label>Descripción:</label>
          <input type="text" name="descripcion" placeholder="Descripción de la pieza">

          <label>Categoría:</label>
          <select name="categoria_id" required>
            <option value="">-- Selecciona una categoría --</option>
            <?php foreach ($categorias as $c): ?>
              <option value="<?= $c['id'] ?>"><?= $c['nombre'] ?></option>
            <?php endforeach; ?>
          </select>

          <button type="submit">Agregar Pieza</button>
        </form>

        <!-- Tabla de piezas temporales -->
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Código</th>
                <th>Categoría</th>
                <th>Descripción</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($piezas)): ?>
                <?php foreach ($piezas as $p): ?>
                  <tr>
                    <td><?= $p['nombre'] ?></td>
                    <td><?= $p['categoria'] ?></td>
                    <td><?= $p['descripcion'] ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="3" style="text-align:center;">No hay piezas registradas aún.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="right">
        <h2>Notas</h2>
        <ul>
          <li>Guarda un modelo antes de agregar piezas</li>
          <li>Las piezas se almacenan temporalmente hasta que guardes todas</li>
        </ul>
      </div>
    </div>
  </div>
</body>
</html>