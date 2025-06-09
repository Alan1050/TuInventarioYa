<?php
session_start();

// Configuración de la base de datos
$db_host = 'localhost';
$db_name = 'stockcerca';
$db_user = 'root';
$db_pass = '';

// Establecer conexión PDO
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->exec("SET NAMES utf8");
} catch(PDOException $e) {
    $_SESSION['error'] = "Error de conexión: " . $e->getMessage();
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_GET['action']) {
            case 'get_products':
                $stmt = $conn->query("SELECT * FROM producto");
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $products ?: []]);
                break;
                
            case 'search_products':
                $term = $_GET['term'] ?? '';
                $barcode = $_GET['barcode'] ?? '';
                
                if (!empty($barcode)) {
                    $stmt = $conn->prepare("SELECT * FROM producto WHERE CodigoBarras = ?");
                    $stmt->execute([$barcode]);
                } else {
                    $stmt = $conn->prepare("SELECT * FROM producto WHERE 
                        CodigoBarras LIKE ? OR 
                        Nombre LIKE ? OR 
                        CodigoProducto LIKE ? OR
                        Marca LIKE ?");
                    $searchTerm = "%$term%";
                    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
                }
                
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $products ?: []]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error de base de datos']);
    }
    
    exit();
}

// Procesar POST (guardar/actualizar/eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['products'])) {
        // Actualización masiva de productos
        try {
            $conn->beginTransaction();
            
            foreach ($_POST['products'] as $id => $product) {
                if (empty($product['Nombre']) || empty($product['Tipo']) || !isset($product['Existencia']) || !isset($product['Precio'])) {
                    continue;
                }
                
                $stmt = $conn->prepare("UPDATE producto SET 
                    Tipo = ?, Nombre = ?, CodigoProducto = ?, CodigoPrincipal = ?, 
                    Marca = ?, Existencia = ?, Precio = ?, UltimaFecha = NOW()
                    WHERE id_Producto = ?");
                
                $stmt->execute([
                    $product['Tipo'],
                    $product['Nombre'],
                    $product['CodigoProducto'] ?? null,
                    $product['CodigoPrincipal'] ?? null,
                    $product['Marca'] ?? null,
                    $product['Existencia'],
                    $product['Precio'],
                    $id
                ]);
            }
            
            $conn->commit();
            $_SESSION['message'] = "Productos actualizados correctamente";
        } catch(PDOException $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Error al actualizar productos: " . $e->getMessage();
        }
        
        header("Location: Inventario.php");
        exit();
    }
    
    // Guardar nuevo producto individual
    if (isset($_POST['Tipo'])) {
        try {
            $stmt = $conn->prepare("INSERT INTO producto 
                (Tipo, Nombre, CodigoBarras, CodigoProducto, CodigoPrincipal, Marca, Existencia, Precio, UltimaFecha) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                
            $stmt->execute([
                $_POST['Tipo'],
                $_POST['Nombre'],
                $_POST['CodigoBarras'] ?? null,
                $_POST['CodigoProducto'] ?? null,
                $_POST['CodigoPrincipal'] ?? null,
                $_POST['Marca'] ?? null,
                $_POST['Existencia'],
                $_POST['Precio']
            ]);
            
            echo json_encode([
                'success' => true,
                'id_Producto' => $conn->lastInsertId()
            ]);
        } catch(PDOException $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error al guardar producto'
            ]);
        }
        
        exit();
    }
}

// Eliminar producto
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $deleteParams);
    $id = $deleteParams['id'] ?? '';
    
    try {
        $stmt = $conn->prepare("DELETE FROM producto WHERE id_Producto = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al eliminar producto']);
    }
    
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TuInventarioYa - Gestión de Productos</title>
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
            font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: #fafafa;
            line-height: 1.6;
            padding: 0;
            min-height: 100vh;
        }

        header {
            grid-area: header;
            background-color: var(--primary);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: white;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        nav a:hover {
            color: var(--warning);
        }

        section {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .inBus {
            width: 100%;
            font-size: 18px;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-buscar {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-buscar:hover {
            background-color: #091f5e;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 16px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dddddd;
        }

        th {
            background-color: #0a2463;
            color: white;
            font-weight: bold;
            position: sticky;
            top: 0;
        }

        tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .product-row input, 
        .product-row select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .product-row input:read-only {
            background-color: #f0f0f0;
        }

        .btn-guardar {
            background-color: var(--success);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            font-size: 16px;
            font-weight: bold;
            display: block;
            margin-left: auto;
        }

        .btn-guardar:hover {
            background-color: #3ca035;
        }

        .btn-actualizar {
            background-color: var(--warning);
            color: var(--dark);
            border: none;
            padding: 8px 12px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-actualizar:hover {
            background-color: #e6b840;
        }

        .btn-eliminar {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-eliminar:hover {
            background-color: #c02a50;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media screen and (max-width: 768px) {
            table {
                font-size: 14px;
            }

            th, td {
                padding: 8px 10px;
            }
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
    
    <section>
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Buscar producto por código o nombre" class="inBus">
            <button type="button" id="searchBtn" class="btn-buscar">Buscar</button>
        </div>
        
        <form id="barcodeForm" class="form-bus">
            <input type="text" id="barcodeInput" placeholder="Escanea o ingresa el código de barras" class="inBus" autofocus>
        </form>
        
        <form id="productsForm" method="post" action="Inventario.php">
            <table id="productsTable">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Nombre</th>
                        <th>Código Barras</th>
                        <th>Código Producto</th>
                        <th>Código Principal</th>
                        <th>Marca</th>
                        <th>Existencia</th>
                        <th>Precio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    <!-- Las filas de productos se cargarán aquí -->
                </tbody>
            </table>
            
            <button type="submit" class="btn-guardar">Guardar Cambios</button>
        </form>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const barcodeInput = document.getElementById('barcodeInput');
            const searchInput = document.getElementById('searchInput');
            const searchBtn = document.getElementById('searchBtn');
            const productsTableBody = document.getElementById('productsTableBody');
            const productsForm = document.getElementById('productsForm');
            
            // Cargar todos los productos al inicio
            loadAllProducts();
            
            // Escuchar el evento de búsqueda
            searchBtn.addEventListener('click', searchProducts);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchProducts();
                }
            });
            
            // Escanear código de barras
            barcodeInput.addEventListener('keypress', async function(e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    
                    if (e.target.value.length > 0) {
                        const barcode = e.target.value.trim();
                        await searchProductByBarcode(barcode);
                        e.target.value = '';
                    }
                }
            });
            
            // Función para cargar todos los productos
            async function loadAllProducts() {
                try {
                    const response = await fetch('Inventario.php?action=get_products');
                    
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }
                    
                    const result = await response.json();
                    
                    if (!result.success) {
                        throw new Error(result.error || 'Error al cargar productos');
                    }
                    
                    renderProducts(result.data);
                } catch (error) {
                    console.error('Error al cargar productos:', error);
                    alert('Error al cargar productos: ' + error.message);
                    productsTableBody.innerHTML = '<tr><td colspan="9">Error al cargar productos</td></tr>';
                }
            }
            
            // Función para buscar productos
            async function searchProducts() {
                const searchTerm = searchInput.value.trim();
                
                if (searchTerm.length === 0) {
                    loadAllProducts();
                    return;
                }
                
                try {
                    const response = await fetch(`Inventario.php?action=search_products&term=${encodeURIComponent(searchTerm)}`);
                    
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }
                    
                    const result = await response.json();
                    
                    if (!result.success) {
                        throw new Error(result.error || 'Error al buscar productos');
                    }
                    
                    renderProducts(result.data);
                } catch (error) {
                    console.error('Error al buscar productos:', error);
                    alert('Error al buscar productos: ' + error.message);
                }
            }
            
            // Función para buscar por código de barras
            async function searchProductByBarcode(barcode) {
                try {
                    const response = await fetch(`Inventario.php?action=search_products&barcode=${encodeURIComponent(barcode)}`);
                    
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }
                    
                    const result = await response.json();
                    
                    if (!result.success) {
                        throw new Error(result.error || 'Error al buscar producto');
                    }
                    
                    if (result.data.length > 0) {
                        renderProducts(result.data);
                    } else {
                        // Si no existe, agregar como nuevo producto
                        addProductRow({
                            id_Producto: '',
                            Tipo: '',
                            Nombre: '',
                            CodigoBarras: barcode,
                            CodigoProducto: '',
                            CodigoPrincipal: '',
                            Marca: '',
                            Existencia: 1,
                            Precio: 0
                        }, false);
                    }
                } catch (error) {
                    console.error('Error al buscar producto:', error);
                    alert('Error al buscar producto: ' + error.message);
                }
            }
            
            // Función para renderizar productos
            function renderProducts(products) {
                productsTableBody.innerHTML = '';
                
                if (products.length === 0) {
                    productsTableBody.innerHTML = '<tr><td colspan="9">No se encontraron productos</td></tr>';
                    return;
                }
                
                products.forEach(product => {
                    addProductRow(product, true);
                });
            }
            
            // Función para agregar una fila de producto
            function addProductRow(product, isExisting) {
                const row = document.createElement('tr');
                row.className = 'product-row';
                row.dataset.productId = product.id_Producto || 'new_' + Date.now();
                
                row.innerHTML = `
                    <td>
                        <select name="products[${row.dataset.productId}][Tipo]" required>
                            <option value="">Seleccione...</option>
                            <option value="Producto" ${product.Tipo === 'Producto' ? 'selected' : ''}>Producto</option>
                            <option value="Servicio" ${product.Tipo === 'Servicio' ? 'selected' : ''}>Servicio</option>
                            <option value="Repuesto" ${product.Tipo === 'Repuesto' ? 'selected' : ''}>Repuesto</option>
                        </select>
                        <input type="hidden" name="products[${row.dataset.productId}][id_Producto]" value="${product.id_Producto || ''}">
                    </td>
                    <td><input type="text" name="products[${row.dataset.productId}][Nombre]" value="${product.Nombre || ''}" required></td>
                    <td><input type="text" name="products[${row.dataset.productId}][CodigoBarras]" value="${product.CodigoBarras || ''}" ${isExisting ? 'readonly' : ''}></td>
                    <td><input type="text" name="products[${row.dataset.productId}][CodigoProducto]" value="${product.CodigoProducto || ''}"></td>
                    <td><input type="text" name="products[${row.dataset.productId}][CodigoPrincipal]" value="${product.CodigoPrincipal || ''}"></td>
                    <td><input type="text" name="products[${row.dataset.productId}][Marca]" value="${product.Marca || ''}"></td>
                    <td><input type="number" name="products[${row.dataset.productId}][Existencia]" value="${product.Existencia || 1}" required min="0"></td>
                    <td><input type="number" step="0.01" name="products[${row.dataset.productId}][Precio]" value="${product.Precio || 0}" required min="0"></td>
                    <td>
                        ${isExisting ? 
                            `<button type="button" class="btn-eliminar" onclick="deleteProduct('${product.id_Producto}', this)">Eliminar</button>` : 
                            `<button type="button" class="btn-actualizar" onclick="saveNewProduct(this)">Guardar</button>`
                        }
                    </td>
                `;
                
                productsTableBody.appendChild(row);
            }
            
            // Función global para eliminar productos
            window.deleteProduct = async function(productId, button) {
                if (confirm('¿Estás seguro de eliminar este producto?')) {
                    try {
                        const response = await fetch('Inventario.php', {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `id=${productId}`
                        });
                        
                        if (!response.ok) {
                            throw new Error(`Error HTTP: ${response.status}`);
                        }
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            button.closest('tr').remove();
                            alert('Producto eliminado correctamente');
                        } else {
                            throw new Error(result.error || 'Error al eliminar el producto');
                        }
                    } catch (error) {
                        console.error('Error al eliminar producto:', error);
                        alert('Error al eliminar el producto: ' + error.message);
                    }
                }
            };
            
            // Función global para guardar nuevos productos
            window.saveNewProduct = async function(button) {
                const row = button.closest('tr');
                const formData = new FormData();
                
                // Recopilar datos del formulario
                formData.append('Tipo', row.querySelector('[name*="[Tipo]"]').value);
                formData.append('Nombre', row.querySelector('[name*="[Nombre]"]').value);
                formData.append('CodigoBarras', row.querySelector('[name*="[CodigoBarras]"]').value);
                formData.append('CodigoProducto', row.querySelector('[name*="[CodigoProducto]"]').value);
                formData.append('CodigoPrincipal', row.querySelector('[name*="[CodigoPrincipal]"]').value);
                formData.append('Marca', row.querySelector('[name*="[Marca]"]').value);
                formData.append('Existencia', row.querySelector('[name*="[Existencia]"]').value);
                formData.append('Precio', row.querySelector('[name*="[Precio]"]').value);
                
                try {
                    const response = await fetch('Inventario.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Producto guardado correctamente');
                        // Actualizar la fila con el ID real del producto
                        row.dataset.productId = result.id_Producto;
                        row.querySelector('[name*="[id_Producto]"]').value = result.id_Producto;
                        // Cambiar el botón a "Eliminar"
                        button.outerHTML = `<button type="button" class="btn-eliminar" onclick="deleteProduct('${result.id_Producto}', this)">Eliminar</button>`;
                    } else {
                        throw new Error(result.error || 'Error al guardar el producto');
                    }
                } catch (error) {
                    console.error('Error al guardar producto:', error);
                    alert('Error al guardar el producto: ' + error.message);
                }
            };
        });
    </script>
</body>
</html>