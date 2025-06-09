<?php

echo '
<script>
    alert("Estamos Trabjando en une nueva actualizacion para tu comodidad, te enviaremos mensaje cuando todo este bien");
    window.location.href="Dashboard.php"
</script>
'

/*
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "stockcerca";

// Conexión a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode(['error' => "Error de conexión: " . $conn->connect_error]));
}

// Procesar solicitudes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Desactivar errores HTML
    ini_set('display_errors', 0);
    error_reporting(0);
    
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'getMarcas':
                $sql = "SELECT DISTINCT Marca FROM catalogo ORDER BY Marca";
                $result = $conn->query($sql);
                if (!$result) {
                    throw new Exception("Error en consulta: " . $conn->error);
                }
                $marcas = [];
                while ($row = $result->fetch_assoc()) {
                    $marcas[] = $row['Marca'];
                }
                echo json_encode($marcas);
                break;
                
            case 'getModelos':
                if (!isset($_POST['marca'])) {
                    throw new Exception("Falta parámetro 'marca'");
                }
                $marca = $conn->real_escape_string($_POST['marca']);
                $sql = "SELECT DISTINCT Modelo FROM catalogo WHERE Marca = '$marca' ORDER BY Modelo";
                $result = $conn->query($sql);
                if (!$result) {
                    throw new Exception("Error en consulta: " . $conn->error);
                }
                $modelos = [];
                while ($row = $result->fetch_assoc()) {
                    $modelos[] = $row['Modelo'];
                }
                echo json_encode($modelos);
                break;
                
            case 'getAnos':
                if (!isset($_POST['marca']) || !isset($_POST['modelo'])) {
                    throw new Exception("Faltan parámetros 'marca' o 'modelo'");
                }
                $marca = $conn->real_escape_string($_POST['marca']);
                $modelo = $conn->real_escape_string($_POST['modelo']);
                $sql = "SELECT DISTINCT Anos FROM catalogo WHERE Marca = '$marca' AND Modelo = '$modelo'";
                $result = $conn->query($sql);
                if (!$result) {
                    throw new Exception("Error en consulta: " . $conn->error);
                }
                $anosUnicos = [];
                
                while ($row = $result->fetch_assoc()) {
                    $anosRange = $row['Anos'];
                    if (strpos($anosRange, '-') !== false) {
                        list($start, $end) = explode('-', $anosRange);
                        for ($year = $start; $year <= $end; $year++) {
                            $anosUnicos[$year] = true;
                        }
                    } else {
                        $anosUnicos[$anosRange] = true;
                    }
                }
                
                $anos = array_keys($anosUnicos);
                rsort($anos);
                echo json_encode($anos);
                break;
                
            case 'getMotores':
                if (!isset($_POST['marca']) || !isset($_POST['modelo']) || !isset($_POST['ano'])) {
                    throw new Exception("Faltan parámetros requeridos");
                }
                $marca = $conn->real_escape_string($_POST['marca']);
                $modelo = $conn->real_escape_string($_POST['modelo']);
                $ano = $conn->real_escape_string($_POST['ano']);
                
                $sql = "SELECT DISTINCT Motor FROM catalogo 
                        WHERE Marca = '$marca' AND Modelo = '$modelo' 
                        AND (
                            (Anos LIKE '%-%' AND $ano BETWEEN SUBSTRING_INDEX(Anos, '-', 1) AND SUBSTRING_INDEX(Anos, '-', -1))
                            OR Anos = '$ano'
                        )";
                
                $result = $conn->query($sql);
                if (!$result) {
                    throw new Exception("Error en consulta: " . $conn->error);
                }
                $motores = [];
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $motores[] = $row['Motor'] ?: 'Todos';
                    }
                } else {
                    $motores[] = 'Todos';
                }
                
                echo json_encode(array_unique($motores));
                break;
                
            case 'getProductos':
                if (!isset($_POST['marca']) || !isset($_POST['modelo']) || !isset($_POST['ano'])) {
                    throw new Exception("Faltan parámetros requeridos");
                }
                $marca = $conn->real_escape_string($_POST['marca']);
                $modelo = $conn->real_escape_string($_POST['modelo']);
                $ano = $conn->real_escape_string($_POST['ano']);
                $motor = isset($_POST['motor']) ? $conn->real_escape_string($_POST['motor']) : null;
                
                $sql = "SELECT * FROM catalogo 
                        WHERE Marca = '$marca' AND Modelo = '$modelo'
                        AND (
                            (Anos LIKE '%-%' AND $ano BETWEEN SUBSTRING_INDEX(Anos, '-', 1) AND SUBSTRING_INDEX(Anos, '-', -1))
                            OR Anos = '$ano'
                        )";
                
                if ($motor && $motor !== 'Todos') {
                    $sql .= " AND (Motor = '$motor' OR Motor IS NULL)";
                }
                
                $result = $conn->query($sql);
                if (!$result) {
                    throw new Exception("Error en consulta: " . $conn->error);
                }
                $productos = [];
                
                while ($row = $result->fetch_assoc()) {
                    $productos[] = $row;
                }
                
                echo json_encode($productos);
                break;
                
            case 'addCatalogo':
                if (!isset($_POST['marca']) || !isset($_POST['modelo']) || !isset($_POST['anos']) || 
                    !isset($_POST['productName']) || !isset($_POST['productCode']) || !isset($_POST['productPosition'])) {
                    throw new Exception("Faltan parámetros requeridos");
                }
                
                $marca = $conn->real_escape_string($_POST['marca']);
                $modelo = $conn->real_escape_string($_POST['modelo']);
                $anos = $conn->real_escape_string($_POST['anos']);
                $motor = !empty($_POST['motor']) ? $conn->real_escape_string($_POST['motor']) : NULL;
                
                $productNames = $_POST['productName'];
                $productCodes = $_POST['productCode'];
                $productPositions = $_POST['productPosition'];
                
                if (count($productNames) !== count($productCodes) || count($productNames) !== count($productPositions)) {
                    throw new Exception("Los arrays de productos no coinciden en tamaño");
                }
                
                $success = true;
                $conn->begin_transaction();
                
                try {
                    for ($i = 0; $i < count($productNames); $i++) {
                        $nombre = $conn->real_escape_string($productNames[$i]);
                        $codigo = $conn->real_escape_string($productCodes[$i]);
                        $posicion = $conn->real_escape_string($productPositions[$i]);
                        
                        $sql = "INSERT INTO catalogo (CodigosProducto, Modelo, Marca, Anos, Motor, id_Negocio) 
                                VALUES ('$codigo', '$modelo', '$marca', '$anos', " . ($motor ? "'$motor'" : "NULL") . ", 1)";
                        
                        if (!$conn->query($sql)) {
                            throw new Exception("Error al insertar producto: " . $conn->error);
                        }
                    }
                    
                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'Catálogo agregado correctamente']);
                } catch (Exception $e) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;
                
            case 'getCatalogoInfo':
                if (!isset($_POST['marca']) || !isset($_POST['modelo']) || !isset($_POST['anos'])) {
                    throw new Exception("Faltan parámetros requeridos");
                }
                
                $marca = $conn->real_escape_string($_POST['marca']);
                $modelo = $conn->real_escape_string($_POST['modelo']);
                $anos = $conn->real_escape_string($_POST['anos']);
                $motor = isset($_POST['motor']) ? $conn->real_escape_string($_POST['motor']) : null;
                
                $sql = "SELECT * FROM catalogo 
                        WHERE Marca = '$marca' AND Modelo = '$modelo' AND Anos = '$anos'";
                
                if ($motor) {
                    $sql .= " AND (Motor = '$motor' OR Motor IS NULL)";
                }
                
                $result = $conn->query($sql);
                if (!$result) {
                    throw new Exception("Error en consulta: " . $conn->error);
                }
                
                $productos = [];
                while ($row = $result->fetch_assoc()) {
                    $productos[] = [
                        'name' => $row['CodigosProducto'],
                        'code' => $row['CodigosProducto'],
                        'position' => $row['CodigosProducto'] // Ajustar según tu estructura real
                    ];
                }
                
                echo json_encode([
                    'marca' => $marca,
                    'modelo' => $modelo,
                    'anos' => $anos,
                    'motor' => $motor,
                    'productos' => $productos
                ]);
                break;
                
            case 'updateCatalogo':
                if (!isset($_POST['marca']) || !isset($_POST['modelo']) || !isset($_POST['anos']) || 
                    !isset($_POST['productName']) || !isset($_POST['productCode']) || !isset($_POST['productPosition'])) {
                    throw new Exception("Faltan parámetros requeridos");
                }
                
                $marca = $conn->real_escape_string($_POST['marca']);
                $modelo = $conn->real_escape_string($_POST['modelo']);
                $anos = $conn->real_escape_string($_POST['anos']);
                $motor = !empty($_POST['motor']) ? $conn->real_escape_string($_POST['motor']) : NULL;
                
                $productNames = $_POST['productName'];
                $productCodes = $_POST['productCode'];
                $productPositions = $_POST['productPosition'];
                
                if (count($productNames) !== count($productCodes) || count($productNames) !== count($productPositions)) {
                    throw new Exception("Los arrays de productos no coinciden en tamaño");
                }
                
                $success = true;
                $conn->begin_transaction();
                
                try {
                    // Primero eliminamos los productos existentes para este catálogo
                    $deleteSql = "DELETE FROM catalogo 
                                 WHERE Marca = '$marca' AND Modelo = '$modelo' AND Anos = '$anos'";
                    
                    if ($motor) {
                        $deleteSql .= " AND (Motor = '$motor' OR Motor IS NULL)";
                    }
                    
                    if (!$conn->query($deleteSql)) {
                        throw new Exception("Error al eliminar productos existentes: " . $conn->error);
                    }
                    
                    // Luego insertamos los nuevos productos
                    for ($i = 0; $i < count($productNames); $i++) {
                        $nombre = $conn->real_escape_string($productNames[$i]);
                        $codigo = $conn->real_escape_string($productCodes[$i]);
                        $posicion = $conn->real_escape_string($productPositions[$i]);
                        
                        $sql = "INSERT INTO catalogo (CodigosProducto, Modelo, Marca, Anos, Motor, id_Negocio) 
                                VALUES ('$codigo', '$modelo', '$marca', '$anos', " . ($motor ? "'$motor'" : "NULL") . ", 1)";
                        
                        if (!$conn->query($sql)) {
                            throw new Exception("Error al insertar producto: " . $conn->error);
                        }
                    }
                    
                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'Catálogo actualizado correctamente']);
                } catch (Exception $e) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;
                
            default:
                echo json_encode(['error' => 'Acción no válida']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Productos - TuInventarioYa</title>
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
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        header h1 {
            font-size: 1.8rem;
        }

        nav ul {
            display: flex;
            list-style: none;
        }

        nav ul li {
            margin-left: 1.5rem;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        nav ul li a:hover {
            color: var(--warning);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .section-title {
            color: var(--primary);
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--secondary);
            padding-bottom: 0.5rem;
        }

        .filter-section {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .filter-buttons button {
            background-color: var(--secondary);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-buttons button:hover {
            background-color: var(--primary);
            transform: translateY(-2px);
        }

        .filter-buttons button img {
            height: 20px;
            width: auto;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .product-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            height: 180px;
            background-color: var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
        }

        .product-info {
            padding: 1rem;
        }

        .product-name {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        .product-code {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .product-position {
            background-color: var(--warning);
            color: var(--dark);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            display: inline-block;
        }

        .add-catalog-btn {
            background-color: var(--success);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 1rem;
            transition: background-color 0.3s;
        }

        .edit-catalog-btn {
            background-color: var(--warning);
            color: var(--dark);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 1rem;
            margin-left: 1rem;
            transition: background-color 0.3s;
        }

        .add-catalog-btn:hover {
            background-color: #3ca035;
        }

        .edit-catalog-btn:hover {
            background-color: #e6b400;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-title {
            color: var(--primary);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: var(--dark);
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--gray);
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #081f4d;
        }

        .btn-secondary {
            background-color: var(--gray);
            color: var(--dark);
        }

        .btn-secondary:hover {
            background-color: #cacaca;
        }

        .product-array {
            margin-top: 1rem;
        }

        .array-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 0.5rem;
            align-items: center;
        }

        .array-item input {
            flex: 1;
        }

        .remove-array-item {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .add-array-item {
            background-color: var(--secondary);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 0.5rem;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-left: 10px;
            vertical-align: middle;
        }

        .brand-logo {
            max-height: 20px;
            max-width: 40px;
            margin-right: 8px;
            vertical-align: middle;
        }

        .product-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
        }

        .action-btn {
            background-color: rgba(0,0,0,0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .action-btn:hover {
            background-color: var(--primary);
            transform: scale(1.1);
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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
                <li><a href="Clientes.php">Clientes</a></li>
                <li><a href="Catalogo.php">Catálogo</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h2 class="section-title">Catálogo de Productos para Automóviles</h2>

        <div class="filter-section">
            <h3>Marcas</h3>
            <div class="filter-buttons" id="marcas">
                <!-- Las marcas se cargarán dinámicamente -->
            </div>
        </div>

        <div class="filter-section" id="modelos-section" style="display: none;">
            <h3>Modelos</h3>
            <div class="filter-buttons" id="modelos">
                <!-- Los modelos se cargarán dinámicamente -->
            </div>
        </div>

        <div class="filter-section" id="anos-section" style="display: none;">
            <h3>Años</h3>
            <div class="filter-buttons" id="anos">
                <!-- Los años se cargarán dinámicamente -->
            </div>
        </div>

        <div class="filter-section" id="motores-section" style="display: none;">
            <h3>Motores</h3>
            <div class="filter-buttons" id="motores">
                <!-- Los motores se cargarán dinámicamente -->
            </div>
        </div>

        <div id="productos-section" style="display: none;">
            <h3 class="section-title">Productos Disponibles</h3>
            <div class="products-grid" id="productos">
                <!-- Los productos se cargarán dinámicamente -->
            </div>
        </div>

        <div id="catalog-actions" style="display: none;">
            <button class="add-catalog-btn" id="addCatalogBtn">Agregar Nuevo Catálogo</button>
            <button class="edit-catalog-btn" id="editCatalogBtn">Editar Catálogo Actual</button>
        </div>
    </div>

    <!-- Modal para agregar/editar catálogo -->
    <div class="modal" id="catalogModal">
        <div class="modal-content">
            <h3 class="modal-title" id="modalTitle">Agregar Nuevo Catálogo</h3>
            <form id="catalogForm">
                <input type="hidden" id="editMode" name="editMode" value="false">
                <div class="form-group">
                    <label for="marca">Marca</label>
                    <input type="text" id="marca" name="marca" required>
                </div>
                <div class="form-group">
                    <label for="modelo">Modelo</label>
                    <input type="text" id="modelo" name="modelo" required>
                </div>
                <div class="form-group">
                    <label for="anos">Años (ej. 2001-2005)</label>
                    <input type="text" id="anos" name="anos" required>
                </div>
                <div class="form-group">
                    <label for="motor">Motor (opcional)</label>
                    <input type="text" id="motor" name="motor">
                </div>
                <div class="form-group">
                    <label>Productos (Nombre, Código, Posición/Tipo)</label>
                    <div class="product-array" id="productosArray">
                        <div class="array-item">
                            <input type="text" placeholder="Nombre" name="productName[]" required>
                            <input type="text" placeholder="Código" name="productCode[]" required>
                            <input type="text" placeholder="Posición/Tipo" name="productPosition[]" required>
                            <button type="button" class="remove-array-item">×</button>
                        </div>
                    </div>
                    <button type="button" class="add-array-item" id="addProductItem">+ Agregar Producto</button>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelCatalogBtn">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="submitCatalogBtn">
                        <span id="submitText">Guardar</span>
                        <span id="submitLoading" class="loading" style="display: none;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Función para obtener el logo de una marca desde Wikimedia Commons
        function getBrandLogo(marca) {
            // Mapeo de marcas a logos conocidos en Wikimedia
            const brandMap = {
                'Toyota': 'Toyota',
                'Honda': 'Honda',
                'Ford': 'Ford',
                'Chevrolet': 'Chevrolet',
                'Nissan': 'Nissan',
                'Volkswagen': 'Volkswagen',
                'BMW': 'BMW',
                'Mercedes-Benz': 'Mercedes-Benz',
                'Audi': 'Audi',
                'Hyundai': 'Hyundai',
                'Kia': 'Kia',
                'Mazda': 'Mazda',
                'Subaru': 'Subaru',
                'Mitsubishi': 'Mitsubishi',
                'Lexus': 'Lexus',
                'Jeep': 'Jeep',
                'Dodge': 'Dodge',
                'Chrysler': 'Chrysler',
                'Ram': 'Ram_Trucks',
                'GMC': 'GMC',
                'Buick': 'Buick',
                'Cadillac': 'Cadillac',
                'Acura': 'Acura',
                'Infiniti': 'Infiniti',
                'Volvo': 'Volvo_Cars',
                'Land Rover': 'Land_Rover',
                'Jaguar': 'Jaguar_Cars',
                'Porsche': 'Porsche',
                'Ferrari': 'Ferrari',
                'Lamborghini': 'Lamborghini',
                'Maserati': 'Maserati',
                'Alfa Romeo': 'Alfa_Romeo',
                'Fiat': 'Fiat',
                'Peugeot': 'Peugeot',
                'Renault': 'Renault',
                'Citroën': 'Citroën',
                'Opel': 'Opel',
                'Seat': 'SEAT',
                'Skoda': 'Škoda_Auto'
            };
            
            const brandName = brandMap[marca] || marca.replace(/\s/g, '_');
            return `https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/${brandName}_logo.svg&width=40`;
        }

        // Variables para el estado actual
        let currentMarca = null;
        let currentModelo = null;
        let currentAnos = null;
        let currentMotor = null;
        let isEditing = false;

        // Cargar marcas al iniciar la página
        document.addEventListener('DOMContentLoaded', function() {
            loadMarcas();
            setupEventListeners();
        });

        async function fetchData(action, data = {}) {
            try {
                const formData = new FormData();
                formData.append('action', action);
                
                // Agregar datos al FormData
                for (const key in data) {
                    if (Array.isArray(data[key])) {
                        data[key].forEach((value, index) => {
                            formData.append(`${key}[${index}]`, value);
                        });
                    } else {
                        formData.append(key, data[key]);
                    }
                }
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                
                return await response.json();
            } catch (error) {
                console.error('Error fetching data:', error);
                return { error: error.message };
            }
        }

        async function loadMarcas() {
            const marcasContainer = document.getElementById('marcas');
            marcasContainer.innerHTML = '<p>Cargando marcas...</p>';
            
            const data = await fetchData('getMarcas');
            
            if (data && !data.error && data.length > 0) {
                marcasContainer.innerHTML = '';
                
                data.forEach(marca => {
                    const button = document.createElement('button');
                    const logoUrl = getBrandLogo(marca);
                    button.innerHTML = `<img src="${logoUrl}" alt="${marca} logo" class="brand-logo" onerror="this.style.display='none'"> ${marca}`;
                    button.addEventListener('click', () => {
                        currentMarca = marca;
                        loadModelos(marca);
                        document.getElementById('modelos-section').style.display = 'block';
                        document.getElementById('anos-section').style.display = 'none';
                        document.getElementById('motores-section').style.display = 'none';
                        document.getElementById('productos-section').style.display = 'none';
                        document.getElementById('catalog-actions').style.display = 'none';
                    });
                    marcasContainer.appendChild(button);
                });
            } else {
                marcasContainer.innerHTML = '<p>No se encontraron marcas.</p>';
                if (data.error) console.error(data.error);
            }
        }

        async function loadModelos(marca) {
            const modelosContainer = document.getElementById('modelos');
            modelosContainer.innerHTML = '<p>Cargando modelos...</p>';
            
            const data = await fetchData('getModelos', { marca });
            
            if (data && !data.error && data.length > 0) {
                modelosContainer.innerHTML = '';
                
                data.forEach(modelo => {
                    const button = document.createElement('button');
                    button.textContent = modelo;
                    button.addEventListener('click', () => {
                        currentModelo = modelo;
                        loadAnos(marca, modelo);
                        document.getElementById('anos-section').style.display = 'block';
                        document.getElementById('motores-section').style.display = 'none';
                        document.getElementById('productos-section').style.display = 'none';
                        document.getElementById('catalog-actions').style.display = 'none';
                    });
                    modelosContainer.appendChild(button);
                });
            } else {
                modelosContainer.innerHTML = '<p>No se encontraron modelos para esta marca.</p>';
                if (data.error) console.error(data.error);
            }
        }

        async function loadAnos(marca, modelo) {
            const anosContainer = document.getElementById('anos');
            anosContainer.innerHTML = '<p>Cargando años...</p>';
            
            const data = await fetchData('getAnos', { marca, modelo });
            
            if (data && !data.error && data.length > 0) {
                anosContainer.innerHTML = '';
                
                data.forEach(ano => {
                    const button = document.createElement('button');
                    button.textContent = ano;
                    button.addEventListener('click', () => {
                        currentAnos = ano;
                        loadMotores(marca, modelo, ano);
                        document.getElementById('motores-section').style.display = 'block';
                        document.getElementById('productos-section').style.display = 'none';
                        document.getElementById('catalog-actions').style.display = 'none';
                    });
                    anosContainer.appendChild(button);
                });
            } else {
                anosContainer.innerHTML = '<p>No se encontraron años para este modelo.</p>';
                if (data.error) console.error(data.error);
            }
        }

        async function loadMotores(marca, modelo, ano) {
            const motoresContainer = document.getElementById('motores');
            motoresContainer.innerHTML = '<p>Cargando motores...</p>';
            
            const data = await fetchData('getMotores', { marca, modelo, ano });
            
            if (data && !data.error && data.length > 0) {
                motoresContainer.innerHTML = '';
                
                // Si solo hay un motor y es 'Todos', cargar productos directamente
                if (data.length === 1 && data[0] === 'Todos') {
                    loadProductos(marca, modelo, ano, null);
                    document.getElementById('motores-section').style.display = 'none';
                    document.getElementById('productos-section').style.display = 'block';
                    document.getElementById('catalog-actions').style.display = 'block';
                    return;
                }
                
                data.forEach(motor => {
                    const button = document.createElement('button');
                    button.textContent = motor;
                    button.addEventListener('click', () => {
                        currentMotor = motor === 'Todos' ? null : motor;
                        loadProductos(marca, modelo, ano, currentMotor);
                        document.getElementById('productos-section').style.display = 'block';
                        document.getElementById('catalog-actions').style.display = 'block';
                    });
                    motoresContainer.appendChild(button);
                });
            } else {
                motoresContainer.innerHTML = '<p>No se encontraron motores para este año.</p>';
                if (data.error) console.error(data.error);
            }
        }

        async function loadProductos(marca, modelo, ano, motor = null) {
            const productosContainer = document.getElementById('productos');
            productosContainer.innerHTML = '<p>Cargando productos...</p>';
            
            const data = await fetchData('getProductos', { marca, modelo, ano, motor });
            
            if (data && !data.error && data.length > 0) {
                productosContainer.innerHTML = '';
                
                data.forEach(producto => {
                    const card = document.createElement('div');
                    card.className = 'product-card';
                    
                    card.innerHTML = `
                        <div class="product-image">
                            ${producto.CodigosProducto}
                        </div>
                        <div class="product-info">
                            <div class="product-name">${producto.CodigosProducto}</div>
                            <div class="product-code">Código: ${producto.CodigosProducto}</div>
                            <div class="product-position">${producto.CodigosProducto}</div>
                        </div>
                    `;
                    
                    productosContainer.appendChild(card);
                });
            } else {
                productosContainer.innerHTML = '<p>No se encontraron productos para esta selección.</p>';
                if (data.error) console.error(data.error);
            }
        }

        function setupEventListeners() {
            // Modal para agregar catálogo
            const addCatalogBtn = document.getElementById('addCatalogBtn');
            const editCatalogBtn = document.getElementById('editCatalogBtn');
            const catalogModal = document.getElementById('catalogModal');
            const cancelCatalogBtn = document.getElementById('cancelCatalogBtn');
            const catalogForm = document.getElementById('catalogForm');
            const addProductItemBtn = document.getElementById('addProductItem');
            const productosArray = document.getElementById('productosArray');
            
            addCatalogBtn.addEventListener('click', () => {
                isEditing = false;
                document.getElementById('modalTitle').textContent = 'Agregar Nuevo Catálogo';
                document.getElementById('editMode').value = 'false';
                document.getElementById('marca').value = currentMarca || '';
                document.getElementById('modelo').value = currentModelo || '';
                document.getElementById('anos').value = currentAnos || '';
                document.getElementById('motor').value = currentMotor || '';
                
                // Limpiar productos existentes y agregar uno vacío
                productosArray.innerHTML = '';
                addProductItem();
                
                catalogModal.style.display = 'flex';
            });
            
            editCatalogBtn.addEventListener('click', async () => {
                isEditing = true;
                document.getElementById('modalTitle').textContent = 'Editar Catálogo';
                document.getElementById('editMode').value = 'true';
                document.getElementById('marca').value = currentMarca;
                document.getElementById('modelo').value = currentModelo;
                document.getElementById('anos').value = currentAnos;
                document.getElementById('motor').value = currentMotor || '';
                
                // Cargar información actual del catálogo
                const data = await fetchData('getCatalogoInfo', {
                    marca: currentMarca,
                    modelo: currentModelo,
                    anos: currentAnos,
                    motor: currentMotor
                });
                
                if (data && !data.error) {
                    // Limpiar productos existentes
                    productosArray.innerHTML = '';
                    
                    // Agregar productos actuales
                    if (data.productos && data.productos.length > 0) {
                        data.productos.forEach(producto => {
                            addProductItem(producto.name, producto.code, producto.position);
                        });
                    } else {
                        addProductItem();
                    }
                    
                    catalogModal.style.display = 'flex';
                } else {
                    alert(data?.error || 'Error al cargar el catálogo');
                }
            });
            
            cancelCatalogBtn.addEventListener('click', () => {
                catalogModal.style.display = 'none';
            });
            
            catalogForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const submitBtn = document.getElementById('submitCatalogBtn');
                const submitText = document.getElementById('submitText');
                const submitLoading = document.getElementById('submitLoading');
                
                submitBtn.disabled = true;
                submitText.style.display = 'none';
                submitLoading.style.display = 'inline-block';
                
                const formData = new FormData(catalogForm);
                const action = isEditing ? 'updateCatalogo' : 'addCatalogo';
                
                try {
                    const data = await fetchData(action, {
                        marca: formData.get('marca'),
                        modelo: formData.get('modelo'),
                        anos: formData.get('anos'),
                        motor: formData.get('motor'),
                        productName: formData.getAll('productName[]'),
                        productCode: formData.getAll('productCode[]'),
                        productPosition: formData.getAll('productPosition[]')
                    });
                    
                    if (data && !data.error) {
                        alert(data.message || 'Catálogo guardado con éxito');
                        catalogModal.style.display = 'none';
                        
                        // Recargar los productos
                        if (currentMarca && currentModelo && currentAnos) {
                            loadProductos(currentMarca, currentModelo, currentAnos, currentMotor);
                        }
                    } else {
                        alert(data?.error || 'Error al guardar el catálogo');
                    }
                } catch (error) {
                    alert('Error al guardar el catálogo: ' + error.message);
                } finally {
                    submitBtn.disabled = false;
                    submitText.style.display = 'inline';
                    submitLoading.style.display = 'none';
                }
            });
            
            // Agregar nuevo campo de producto
            addProductItemBtn.addEventListener('click', () => {
                addProductItem();
            });
            
            // Delegación de eventos para eliminar items de producto
            productosArray.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-array-item')) {
                    const item = e.target.closest('.array-item');
                    if (item && productosArray.children.length > 1) {
                        item.remove();
                    } else {
                        alert('Debe haber al menos un producto');
                    }
                }
            });
        }
        
        function addProductItem(name = '', code = '', position = '') {
            const productosArray = document.getElementById('productosArray');
            const item = document.createElement('div');
            item.className = 'array-item';
            
            item.innerHTML = `
                <input type="text" placeholder="Nombre" name="productName[]" value="${name}" required>
                <input type="text" placeholder="Código" name="productCode[]" value="${code}" required>
                <input type="text" placeholder="Posición/Tipo" name="productPosition[]" value="${position}" required>
                <button type="button" class="remove-array-item">×</button>
            `;
            
            productosArray.appendChild(item);
        }
    </script>
</body>
</html>

<?php
*/
?>