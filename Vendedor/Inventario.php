<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include '../include/conn.php';

$ID_Negocio = intval($_SESSION['idNegocio']);
if (!$conn) {
    die(json_encode(['success' => false, 'error' => 'No hay conexión a la base de datos']));
}
// Procesar acciones GET (búsquedas)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    try {
        switch ($_GET['action']) {
            case 'get_products':
                $result = pg_query($conn, "SELECT * FROM producto WHERE idnegocio = $ID_Negocio");
                if (!$result) throw new Exception(pg_last_error($conn));
                $products = pg_fetch_all($result);
                echo json_encode(['success' => true, 'data' => $products ?: []]);
                break;
            case 'search_products':
                $term = $_GET['term'] ?? '';
                $barcode = $_GET['barcode'] ?? '';
                if (!empty($barcode)) {
                    // Buscar por código de barras exacto
                    $query = "SELECT * FROM producto 
                              WHERE codigobarras = $1 AND idnegocio = $2";
                    $params = array($barcode, $ID_Negocio);
                } else if (!empty($term)) {
                    // Buscar por término en varios campos
                    $searchTerm = "%$term%";
                    $query = "SELECT * FROM producto 
                              WHERE( codigobarras LIKE $1 OR 
                                    nombre LIKE $1 OR 
                                    codigoproducto LIKE $1 OR 
                                    marca LIKE $1 OR 
                                    codigoprincipal LIKE $1 OR
                                    segundocodigo LIKE $1) AND idnegocio = $ID_Negocio";
                    $params = array($searchTerm);
                } else {
                    echo json_encode(['success' => false, 'error' => 'No hay término de búsqueda']);
                    break;
                }
                $result = pg_query_params($conn, $query, $params);
                if (!$result) {
                    throw new Exception("Error en la consulta");
                }
                $products = pg_fetch_all($result);
                echo json_encode(['success' => true, 'data' => $products ?: []]);
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
                break;
        }
    } catch (Exception $e) {
         json_encode(['success' => false, 'error' => 'Error en la búsqueda']);
    }
    exit();
}
// Procesar POST: Guardar o actualizar productos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['products'])) {
        try {
            // Iniciar transacción
            pg_query($conn, "BEGIN");
            foreach ($_POST['products'] as $id => $product) {
                // Validación básica
                if (empty($product['nombre']) || empty($product['tipo']) ||
                    !isset($product['existencia']) || !isset($product['precio'])) {
                    continue;
                }
                // Convertir valores
                $existencia = intval($product['existencia']);
                $precio = floatval($product['precio']);
                // Preparar campos para update
                $params = array(
                    $product['tipo'],
                    $product['nombre'],
                    $product['codigoproducto'] ?? null,
                    $product['codigoprincipal'] ?? null,
                    $product['segundocodigo'] ?? null,
                    $product['marca'] ?? null,
                    $existencia,
                    $precio,
                    $id
                );
                // Ejecutar actualización
                $query = "UPDATE producto SET 
                            tipo = $1, 
                            nombre = $2, 
                            codigoproducto = $3, 
                            codigoprincipal = $4, 
                            segundocodigo = $5,
                            marca = $6, 
                            existencia = $7, 
                            precio = $8, 
                            ultimafecha = NOW() 
                          WHERE idproducto = $9";
                $result = pg_query_params($conn, $query, $params);
                if (!$result) {
                    // No mostrar errores al usuario, solo continuar
                    continue;
                }
            }
            pg_query($conn, "COMMIT");
            json_encode(['success' => true]);
            echo'
                <script>
                    window.location.href="./Inventario.php";
                </script>
            ';
        } catch (Exception $e) {
            pg_query($conn, "ROLLBACK");
            echo json_encode(['success' => false]);
            echo'
                <script>
                    window.location.href="./Inventario.php";
                </script>
            ';
        }
        echo'
            <script>
                window.location.href="./Inventario.php";
            </script>
        ';
        exit();
    }
    // Guardar nuevo producto individual
// Guardar nuevo producto individual
if (isset($_POST['Tipo'])) {
    try {
        // Validación de campos obligatorios
        if (empty($_POST['Nombre']) || !isset($_POST['Existencia']) || !isset($_POST['Precio'])) {
            throw new Exception("Faltan campos obligatorios");
        }

        $codigoBarras = !empty($_POST['CodigoBarras']) ? $_POST['CodigoBarras'] : null;
        $params = array(
            $_POST['Tipo'],
            $_POST['Nombre'],
            $codigoBarras,
            $_POST['CodigoProducto'] ?? null,
            $_POST['CodigoPrincipal'] ?? null,
            $_POST['SegundaReferencia'] ?? null,
            $_POST['Marca'] ?? null,
            intval($_POST['Existencia']),
            floatval($_POST['Precio']),
            intval($ID_Negocio)
        );

        // Consulta INSERT con RETURNING para obtener el ID generado
        $query = "INSERT INTO producto 
                    (tipo, nombre, codigobarras, codigoproducto, codigoprincipal, segundocodigo, marca, existencia, precio, ultimafecha, idnegocio) 
                  VALUES 
                    ($1, $2, $3, \$4, $5, $6, $7, $8, $9, NOW(), $10) 
                  RETURNING idproducto";

        $result = pg_query_params($conn, $query, $params);

        if (!$result) {
            throw new Exception("Error al ejecutar la consulta: " . pg_last_error($conn));
        }

        $row = pg_fetch_assoc($result);
        echo json_encode([
            'success' => true,
            'idproducto' => $row['idproducto']
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}
}
// Procesar DELETE: Eliminar producto
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $deleteParams);
    $id = $deleteParams['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['success' => false]);
        exit();
    }
    try {
        $query = "DELETE FROM producto WHERE idproducto = $1";
        $result = pg_query_params($conn, $query, array($id));
        if (!$result) {
            echo json_encode(['success' => false]);
            exit();
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false]);
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
            max-width: 98%;
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
                <!--<li><a href="Clientes.php">Clientes</a></li>
                <li><a href="Catalogo.php">Catalogo</a></li>-->
            </ul>
        </nav>
    </header>
    
    <section>
        
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Buscar producto por código o nombre" class="inBus">
            <button type="button" id="searchBtn" class="btn-buscar">Buscar</button>
        </div>
        
        <form id="barcodeForm" class="form-bus">
            <input type="text" id="barcodeInput" placeholder="Escanea o ingresa el código de barras para agregar producto" class="inBus" autofocus>
        </form>
        
        <form id="productsForm" method="post" action="Inventario.php">
            <button type="submit" class="btn-guardar">Guardar Cambios</button>
            <table id="productsTable">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Nombre</th>
                        <th>Código Barras</th>
                        <th>Código Producto</th>
                        <th>Código Principal</th>
                        <th>Otra Referencia</th>
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
            
        </form>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const barcodeInput = document.getElementById('barcodeInput');
            const searchInput = document.getElementById('searchInput');
            const searchBtn = document.getElementById('searchBtn');
            const productsTableBody = document.getElementById('productsTableBody');
            const productsForm = document.getElementById('productsForm');

            loadAllProducts();

            searchBtn.addEventListener('click', searchProducts);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchProducts();
                }
            });

            barcodeInput.addEventListener('keypress', async function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (e.target.value.length > 0) {
                        const barcode = e.target.value.trim();
                        await searchProductByBarcode(barcode);
                        e.target.value = '';
                    }
                }
            });

async function loadAllProducts() {
    try {
        const response = await fetch('Inventario.php?action=get_products');
        const text = await response.text(); // Recibe como texto plano
        console.log("Respuesta sin parsear:", text); // Mira esto en consola
        const result = JSON.parse(text); // Ahora intenta parsear
        if (!result.success) throw new Error(result.error || 'Error al cargar productos');
        renderProducts(result.data);
    } catch (error) {
        console.error('Error:', error);
        alert('Error al cargar productos');
        productsTableBody.innerHTML = '<tr><td colspan="10">Error al cargar productos</td></tr>';
    }
}
            async function searchProducts() {
                const searchTerm = searchInput.value.trim();
                if (searchTerm.length === 0) return loadAllProducts();
                try {
                    const response = await fetch(`Inventario.php?action=search_products&term=${encodeURIComponent(searchTerm)}`);
                    const result = await response.json();
                    if (!result.success) throw new Error(result.error || 'Error al buscar productos');
                    renderProducts(result.data);
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error al buscar productos');
                }
            }

            async function searchProductByBarcode(barcode) {
                try {
                    const response = await fetch(`Inventario.php?action=search_products&barcode=${encodeURIComponent(barcode)}`);
                    const result = await response.json();
                    if (!result.success) throw new Error(result.error || 'Error al buscar producto');
                    if (result.data.length > 0) {
                        renderProducts(result.data);
                    } else {
                        addProductRow({
                            idproducto: '',
                            tipo: '',
                            nombre: '',
                            codigobarras: barcode,
                            codigoproducto: '',
                            codigoprincipal: '',
                            segundocodigo: '',
                            marca: '',
                            existencia: 1,
                            precio: 0
                        }, false);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error al buscar producto');
                }
            }

            function renderProducts(products) {
                productsTableBody.innerHTML = '';
                if (products.length === 0) {
                    productsTableBody.innerHTML = '<tr><td colspan="10">No se encontraron productos</td></tr>';
                    return;
                }
                products.forEach(product => {
                    addProductRow(product, true);
                });
            }

            function addProductRow(product, isExisting) {
                const row = document.createElement('tr');
                row.className = 'product-row';
                row.dataset.productId = product.idproducto || 'new_' + Date.now();
                row.innerHTML = `
                    <td>
                        <select name="products[${row.dataset.productId}][tipo]" required>
                            <option value="">Seleccione...</option>
                            <option value="Producto" ${['Producto', 'producto', 'PRODUCTO'].includes(product.tipo || '') ? 'selected' : ''}>Producto</option>
                            <option value="Servicio" ${['Servicio', 'servicio', 'SERVICIO'].includes(product.tipo || '') ? 'selected' : ''}>Servicio</option>
                            <option value="Repuesto" ${['Repuesto', 'repuesto', 'REPUESTO'].includes(product.tipo || '') ? 'selected' : ''}>Repuesto</option>
                        </select>
                        <input type="hidden" name="products[${row.dataset.productId}][idproducto]" value="${product.idproducto || ''}">
                    </td>
                    <td><input type="text" name="products[${row.dataset.productId}][nombre]" value="${product.nombre || ''}" required></td>
                    <td><input type="text" name="products[${row.dataset.productId}][codigobarras]" value="${product.codigobarras || ''}" ${isExisting ? 'readonly' : ''}></td>
                    <td><input type="text" name="products[${row.dataset.productId}][codigoproducto]" value="${product.codigoproducto || ''}"></td>
                    <td><input type="text" name="products[${row.dataset.productId}][codigoprincipal]" value="${product.codigoprincipal || ''}"></td>
                    <td><input type="text" name="products[${row.dataset.productId}][segundocodigo]" value="${product.segundocodigo || ''}"></td>
                    <td><input type="text" name="products[${row.dataset.productId}][marca]" value="${product.marca || ''}"></td>
                    <td><input type="number" name="products[${row.dataset.productId}][existencia]" value="${product.existencia || 1}" required min="0"></td>
                    <td><input type="number" step="0.01" name="products[${row.dataset.productId}][precio]" value="${product.precio || 0}" required min="0"></td>
                    <td>
                        ${isExisting ? 
                            `<button type="button" class="btn-eliminar" onclick="deleteProduct('${product.idproducto}', this)">Eliminar</button>` : 
                            `<button type="button" class="btn-actualizar" onclick="saveNewProduct(this)">Guardar</button>`
                        }
                    </td>
                `;
                productsTableBody.prepend(row);
            }

            window.deleteProduct = async function(productId, button) {
                if (confirm('¿Estás seguro de eliminar este producto?')) {
                    try {
                        const response = await fetch('Inventario.php', {
                            method: 'DELETE',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `id=${productId}`
                        });
                        const result = await response.json();
                        if (result.success) {
                            button.closest('tr').remove();
                            alert('Producto eliminado correctamente');
                        } else {
                            throw new Error(result.error || 'Error al eliminar el producto');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error al eliminar el producto');
                    }
                }
            };

window.saveNewProduct = async function(button) {
    const row = button.closest('tr');
    const formData = new FormData();
    
    const getInputValue = (name) => {
        const input = row.querySelector(`input[name*="[${name}]"], select[name*="[${name}]"]`);
        return input ? input.value.trim() : '';
    };

    formData.append('Tipo', getInputValue('tipo'));
    formData.append('Nombre', getInputValue('nombre'));
    formData.append('CodigoBarras', getInputValue('codigobarras'));
    formData.append('CodigoProducto', getInputValue('codigoproducto'));
    formData.append('CodigoPrincipal', getInputValue('codigoprincipal'));
    formData.append('SegundaReferencia', getInputValue('segundocodigo'));
    formData.append('Marca', getInputValue('marca'));
    formData.append('Existencia', getInputValue('existencia'));
    formData.append('Precio', getInputValue('precio'));

    try {
        const response = await fetch('Inventario.php', { method: 'POST', body: formData });

        // Solo leemos el cuerpo UNA VEZ
        const text = await response.text(); // Primera y única lectura

        let result;
        try {
            result = JSON.parse(text);
        } catch (jsonError) {
            console.error("Texto de respuesta:", text); // Verifica qué recibiste
            throw new Error("La respuesta no es JSON válido");
        }

        if (result.success) {
            alert('Producto guardado correctamente');
            const hiddenInput = row.querySelector('input[name*="[idproducto]"]');
            if (hiddenInput) hiddenInput.value = result.idproducto;
            button.outerHTML = `
                <button type="button" class="btn-eliminar" onclick="deleteProduct('${result.idproducto}', this)">
                    Eliminar
                </button>
            `;
        } else {
            throw new Error(result.error || 'Error al guardar el producto');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al guardar el producto. Revisa la consola para más detalles.');
    }
};
        });
    </script>
</body>
</html>