<?php
session_start();

/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
var_dump($_SESSION); // Verifica si la sesión se crea */

// Configuración de la base de datos
$db_host = 'localhost';
$db_name = 'stockcerca';
$db_user = 'root';
$db_pass = '';

// Establecer conexión PDO
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->exec("SET NAMES utf8");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    $_SESSION['error'] = "Error de conexión: " . $e->getMessage();
}

// Procesar búsqueda de productos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_term'])) {
    $searchTerm = $_POST['search_term'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM producto WHERE 
            CodigoBarras = ? OR 
            Nombre = ? OR 
            CodigoProducto = ?");
        $searchParam = "$searchTerm";
        $stmt->execute([$searchParam, $searchParam, $searchParam]);
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $products]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error en la búsqueda']);
    }
    exit();
}

// Procesar venta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['venta'])) {
    try {
        $conn->beginTransaction();
        
        // Generar folio único
        $folio = 'VEN-' . date('YmdHis');
        $total = 0;
        $productosVenta = json_decode($_POST['venta']['productos'], true);
        
        // Primero: Calcular el total y validar stock
        foreach ($productosVenta as $producto) {
            $precioUnitario = floatval($producto['precio']);
            $cantidad = floatval($producto['cantidad']);
            $total += $precioUnitario * $cantidad;
            
            // Verificar stock disponible
            $stmtStock = $conn->prepare("SELECT Existencia FROM producto WHERE CodigoBarras = ?");
            $stmtStock->execute([$producto['codigo_barras']]);
            $stock = $stmtStock->fetchColumn();
            
            if ($stock < $cantidad) {
                throw new Exception("Stock insuficiente para: {$producto['nombre']} (Stock: $stock, Se requiere: $cantidad)");
            }
        }
        
        // Segundo: Insertar venta (solo una vez)
// Segundo: Insertar venta (solo una vez)
$stmtVenta = $conn->prepare("INSERT INTO ventas 
    (Descripcion, PreciosU, Cantidades, CodigosBarras, Marcas, PrecioFinal, ClaveTrabajador, Fecha, Folio, id_Negocio) 
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
        
        // Tercero: Procesar cada producto vendido
foreach ($productosVenta as $producto) {
    $descripcion = $producto['nombre'];
    $precioUnitario = floatval($producto['precio']);
    $cantidad = floatval($producto['cantidad']);
    $codigoBarras = $producto['codigo_barras'];
    $marca = $producto['marca'];
    $precioFinal = $precioUnitario * $cantidad;
    $ClaveTrabajador = $_SESSION['ClaveTrabajador'];
    $Id_Negocios = intval($_SESSION['idNegocio']);
    
    // Insertar detalle de venta
    $stmtVenta->execute([
        $descripcion,
        $precioUnitario,
        $cantidad,
        $codigoBarras,
        $marca,
        $precioFinal,
        $ClaveTrabajador,
        $folio,
        $Id_Negocios  // id_Negocio
    ]);
            
            // Actualizar stock
            $stmtUpdate = $conn->prepare("UPDATE producto SET Existencia = Existencia - ? WHERE CodigoBarras = ?");
            $stmtUpdate->execute([$cantidad, $codigoBarras]);
        }
        
        $conn->commit();
        $_SESSION['message'] = "Venta realizada con éxito. Folio: $folio - Total: $" . number_format($total, 2);
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error al procesar la venta: " . $e->getMessage();
    } catch(Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: Dashboard.php");
    exit();
}

// Procesar cotización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cotizacion'])) {
    try {
        // Generar folio único para cotización
        $folio = 'COT-' . date('YmdHis');
        $total = 0;
        $productos = [];
        
        // Preparar datos de la cotización
        foreach ($_POST['cotizacion']['productos'] as $producto) {
            $precio = floatval($producto['precio']);
            $cantidad = floatval($producto['cantidad']);
            $subtotal = $precio * $cantidad;
            
            $productos[] = [
                'nombre' => $producto['nombre'],
                'precio' => $precio,
                'cantidad' => $cantidad,
                'subtotal' => $subtotal
            ];
            $total += $subtotal;
        }
        
        $_SESSION['cotizacion'] = [
            'folio' => $folio,
            'productos' => $productos,
            'total' => $total,
            'fecha' => date('Y-m-d H:i:s')
        ];
        
        $_SESSION['message'] = "Cotización generada. Folio: $folio - Total: $" . number_format($total, 2);
    } catch(Exception $e) {
        $_SESSION['error'] = "Error al generar cotización: " . $e->getMessage();
    }
    
    header("Location: Dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TuInventarioYa - Dashboard Vendedor</title>
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
            display: grid;
            grid-template-columns: 0.2fr 1.8fr;
            background-color: var(--primary);
            width: 100%;
            margin-top: 0;
            padding: 10px 0;
        }

        .header-pt1 img {
            width: 60%;
            padding-top: 9px;
            margin-left: 35px;
        }

        .header-pt2 h1 {
            color: white;
            text-align: initial;
            padding-top: 28px;
            padding-left: 20px;
        }

        section {
            display: grid;
            grid-template-columns: 1.7fr 0.3fr;
            padding: 20px;
        }

        .Data-pt1 {
            margin-bottom: 20px;
        }

        .form-bus {
            display: flex;
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

        .btn-bus {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-left: 10px;
        }

        .btn-bus:hover {
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

        .product-row input {
            width: 80px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }

        .btn-eliminar {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }

        .btn-eliminar:hover {
            background-color: #c02a50;
        }

        .Data-pt2 {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding-left: 20px;
        }

        .Op-1, .Op-2 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .Op-1 button, .Op-2 button {
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            background-color: var(--secondary);
            color: white;
        }

        .Op-1 button:hover, .Op-2 button:hover {
            opacity: 0.9;
        }

        .total-section {
            margin-top: 20px;
            padding: 15px;
            background-color: var(--light);
            border-radius: 5px;
            font-weight: bold;
            font-size: 18px;
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
    </style>
</head>
<body>
    <header>
        <div class="header-pt1">
            <img src="../img/LogoPuroBlanco.png" alt="Logo">
        </div>
        <div class="header-pt2">
            <h1>Tu Inventario Ya</h1>
        </div>
    </header>
    
    <section>
        <div class="Data">
            <div class="Data-pt1">
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
                
                <form id="searchForm" class="form-bus">
                    <input type="text" id="searchInput" placeholder="Código de Barras, Nombre o Código de Producto" class="inBus" autofocus>
                    <button type="button" id="searchBtn" class="btn-bus">Buscar</button>
                </form>
                
                <form id="saleForm" method="post">
                    <table id="productsTable">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Precio</th>
                                <th>Cantidad</th>
                                <th>Stock</th>
                                <th>Subtotal</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <!-- Las filas de productos se agregarán aquí -->
                        </tbody>
                    </table>
                    
                    <div class="total-section">
                        Total a Pagar: $<span id="totalAmount">0.00</span>
                    </div>
                </form>
            </div>
        </div>

        <div class="Data-pt2">
            <div class="Op-1">
                <button id="btnVenta">Realizar venta</button>
                <button id="btnCotizacion">Realizar Cotización</button>
                <button onclick="window.location.href='AgregarProducto.php'">Agregar Productos</button>
                <!-- <button onclick="window.location.href='Catalogo.php'">Catálogo</button>
                <button onclick="window.location.href='Clientes.php'">Clientes</button>-->
            </div>
            <div class="Op-2">
                <button onclick="window.location.href='Inventario.php'">Inventario</button>
                <button onclick="window.location.href='Configuraciones.php'">Configuraciones</button>
                <button onclick="window.location.href='CorteCaja.php'">Corte Caja<br>Análisis</button>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const searchBtn = document.getElementById('searchBtn');
            const productsTableBody = document.getElementById('productsTableBody');
            const btnVenta = document.getElementById('btnVenta');
            const btnCotizacion = document.getElementById('btnCotizacion');
            const totalAmount = document.getElementById('totalAmount');
            
            // Objeto para almacenar los productos en el carrito
            const cart = {};
            
            // Función para buscar productos
            async function searchProduct() {
                const searchTerm = searchInput.value.trim();
                
                if (searchTerm.length === 0) {
                    alert('Por favor ingrese un término de búsqueda');
                    return;
                }
                
                try {
                    const response = await fetch('Dashboard.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `search_term=${encodeURIComponent(searchTerm)}`
                    });
                    
                    const result = await response.json();
                    
                    if (result.success && result.data.length > 0) {
                        // Tomar el primer producto encontrado
                        const product = result.data[0];
                        
                        // Verificar si el producto ya está en el carrito
                        if (cart[product.CodigoBarras]) {
                            // Si ya existe, aumentar la cantidad
                            const row = document.querySelector(`tr[data-barcode="${product.CodigoBarras}"]`);
                            const inputCantidad = row.querySelector('input[name="cantidad"]');
                            inputCantidad.value = (parseFloat(inputCantidad.value) + 1).toFixed(2);
                            
                            // Actualizar subtotal
                            updateSubtotal(row, product.Precio);
                        } else {
                            // Si no existe, agregarlo al carrito
                            addProductToCart(product);
                        }
                        
                        searchInput.value = '';
                        searchInput.focus();
                    } else {
                        alert('No se encontraron productos con ese criterio');
                    }
                } catch (error) {
                    console.error('Error al buscar producto:', error);
                    alert('Error al buscar producto');
                }
            }
            
            // Escuchar el evento de búsqueda
            searchBtn.addEventListener('click', searchProduct);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchProduct();
                }
            });
            
            // Función para agregar producto al carrito
           function addProductToCart(product) {
    // Convertir existencia a float por si acaso
    const existencia = parseFloat(product.Existencia);
    
    // Agregar al objeto cart
    cart[product.CodigoBarras] = {
        nombre: product.Nombre,
        precio: parseFloat(product.Precio),
        cantidad: 1.0, // Iniciar con valor decimal
        stock: existencia,
        codigo_barras: product.CodigoBarras,
        marca: product.Marca
    };
    
    // Crear fila en la tabla
    const row = document.createElement('tr');
    row.className = 'product-row';
    row.dataset.barcode = product.CodigoBarras;
    
    row.innerHTML = `
        <td>${product.Nombre}</td>
        <td>$${parseFloat(product.Precio).toFixed(2)}</td>
        <td>
            <input type="number" name="cantidad" value="1.00" min="0.01" step="0.01" max="${existencia}" 
                onchange="updateQuantity('${product.CodigoBarras}', this.value)">
        </td>
        <td>${existencia.toFixed(2)}</td>
        <td class="subtotal">$${parseFloat(product.Precio).toFixed(2)}</td>
        <td>
            <button class="btn-eliminar" onclick="removeProduct('${product.CodigoBarras}')">Eliminar</button>
        </td>
    `;
    
    productsTableBody.appendChild(row);
    updateTotal();
}
            
            // Función para actualizar la cantidad de un producto
window.updateQuantity = function(barcode, quantity) {
    quantity = parseFloat(quantity);
    
    if (isNaN(quantity)) {
        alert('Por favor ingrese un número válido');
        return;
    }
    
    if (quantity <= 0) {
        alert('La cantidad debe ser mayor que cero');
        return;
    }
    
    if (cart[barcode]) {
        // Validar que no supere el stock
        const maxStock = cart[barcode].stock;
        quantity = Math.min(quantity, maxStock);
        
        // Actualizar en el carrito
        cart[barcode].cantidad = quantity;
        
        // Actualizar el input en caso de que se haya ajustado
        const row = document.querySelector(`tr[data-barcode="${barcode}"]`);
        const input = row.querySelector('input[name="cantidad"]');
        input.value = quantity.toFixed(2);
        
        // Actualizar subtotal y total
        const precio = cart[barcode].precio;
        updateSubtotal(row, precio);
    }
};
            
            // Función para actualizar subtotal
function updateSubtotal(row, precio) {
    const cantidadInput = row.querySelector('input[name="cantidad"]');
    const cantidad = parseFloat(cantidadInput.value);
    const barcode = row.dataset.barcode;
    
    if (isNaN(cantidad)) {
        cantidadInput.value = '1.00';
        return;
    }
    
    // Actualizar la cantidad en el objeto cart
    if (cart[barcode]) {
        cart[barcode].cantidad = cantidad;
    }
    
    const subtotal = (precio * cantidad).toFixed(2);
    row.querySelector('.subtotal').textContent = `$${subtotal}`;
    updateTotal();
}
            
            // Función para eliminar producto
            window.removeProduct = function(barcode) {
                if (confirm('¿Eliminar este producto del carrito?')) {
                    delete cart[barcode];
                    document.querySelector(`tr[data-barcode="${barcode}"]`).remove();
                    updateTotal();
                }
            };
            
            // Función para calcular el total
function updateTotal() {
    let total = 0;
    const rows = document.querySelectorAll('.product-row');
    
    rows.forEach(row => {
        const barcode = row.dataset.barcode;
        if (cart[barcode]) {
            const precio = cart[barcode].precio;
            const cantidad = cart[barcode].cantidad;
            total += precio * cantidad;
        }
    });
    
    totalAmount.textContent = total.toFixed(2);
}
            
// Función para realizar venta
btnVenta.addEventListener('click', function() {
    if (Object.keys(cart).length === 0) {
        alert('No hay productos en el carrito');
        return;
    }
    
    if (confirm('¿Confirmar venta?')) {
        const formData = new FormData();
        // Convertir el objeto cart a array y luego a JSON
        const productosArray = Object.values(cart);
        formData.append('venta[productos]', JSON.stringify(productosArray));
        
        fetch('Dashboard.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la venta');
        });
    }
});
            
            // Función para realizar cotización
            btnCotizacion.addEventListener('click', function() {
                if (Object.keys(cart).length === 0) {
                    alert('No hay productos en el carrito');
                    return;
                }
                
                if (confirm('¿Generar cotización?')) {
                    const formData = new FormData();
                    formData.append('cotizacion[productos]', JSON.stringify(Object.values(cart)));
                    
                    fetch('Dashboard.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al generar cotización');
                    });
                }
            });
        });
    </script>
</body>
</html>
