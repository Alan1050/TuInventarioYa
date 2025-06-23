<?php
session_start();
// Incluir conexión ya establecida (debe contener $conn como conexión PostgreSQL)
include '../include/conn.php';
if (!$conn) {
    die(json_encode(['success' => false, 'error' => 'No hay conexión a la base de datos']));
}

// Procesar búsqueda de productos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_term'])) {
    $searchTerm = strtoupper($_POST['search_term']);
    // Verificar que idNegocio exista en sesión
    if (!isset($_SESSION['idNegocio'])) {
        echo json_encode(['success' => false, 'error' => 'No se encontró el negocio']);
        exit();
    }
    $Id_Negocios = intval($_SESSION['idNegocio']);
    try {
        // Consulta con paréntesis para agrupar condiciones
        $query = "SELECT * FROM producto WHERE 
            (codigobarras = $1 OR 
             nombre = $1 OR 
             codigoproducto = $1) AND 
            idnegocio = $2";
        $result = pg_query_params($conn, $query, array($searchTerm, $Id_Negocios));
        if ($result === false) {
            throw new Exception("Error al ejecutar la consulta");
        }
        $products = pg_fetch_all($result);
        echo json_encode([
            'success' => true,
            'data' => $products ?: []
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error en la búsqueda: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Procesar Venta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['venta'])) {
    try {
        // Iniciar transacción
        pg_query($conn, "BEGIN");

        // Validar sesión
        if (!isset($_SESSION['idNegocio'])) {
            throw new Exception("No se encontró el ID del negocio");
        }
        $Id_Negocios = intval($_SESSION['idNegocio']);

        // Generar folio único
        $folio = 'VEN-' . date('YmdHis');
        $total = 0;
        $productosVenta = json_decode($_POST['venta']['productos'], true);

        // Verificar productos y stock
        foreach ($productosVenta as $producto) {
            if (empty($producto['codigo_barras'])) {
                throw new Exception("Código de barras no válido en el producto: {$producto['nombre']}");
            }

            $precioUnitario = floatval($producto['precio']);
            $cantidad = floatval($producto['cantidad']);
            $total += $precioUnitario * $cantidad;

            // Verificar stock disponible
            $queryStock = "SELECT existencia FROM producto WHERE codigobarras = $1 AND idnegocio = $2";
            $resultStock = pg_query_params($conn, $queryStock, array($producto['codigo_barras'], $Id_Negocios));

            if (!$resultStock) {
                throw new Exception("Error al verificar stock del producto: {$producto['nombre']}");
            }

            $stockRow = pg_fetch_assoc($resultStock);
            $stock = $stockRow['existencia'] ?? 0;

            if ($stock < $cantidad) {
                throw new Exception("Stock insuficiente para: {$producto['nombre']} (Stock: $stock, Se requiere: $cantidad)");
            }
        }

        // Insertar venta por cada producto
        $queryVenta = "INSERT INTO ventas 
            (descripcion, preciosu, cantidades, codigosbarras, marcas, preciofinal, clavetrabajador, fecha, folio, idnegocio) 
            VALUES 
            ($1, $2, $3, $4, $5, $6, $7, NOW(), $8, $9)";
        $ClaveTrabajador = $_SESSION['ClaveTrabajador'];

        foreach ($productosVenta as $producto) {
            $descripcion = strtoupper($producto['nombre']);
            $precioUnitario = floatval($producto['precio']);
            $cantidad = floatval($producto['cantidad']);
            $codigoBarras = strtoupper($producto['codigo_barras']);
            $marca = strtoupper($producto['marca']);
            $precioFinal = $precioUnitario * $cantidad;

            // Insertar venta
            $resultVenta = pg_query_params($conn, $queryVenta, array(
                $descripcion,
                $precioUnitario,
                $cantidad,
                $codigoBarras,
                $marca,
                $precioFinal,
                $ClaveTrabajador,
                $folio,
                $Id_Negocios
            ));

            if (!$resultVenta) {
                throw new Exception("Error al guardar el producto: {$producto['nombre']} - " . pg_last_error($conn));
            }

            // Actualizar stock
            $queryUpdate = "UPDATE producto SET existencia = existencia - $1 WHERE codigobarras = $2 AND idnegocio = $3";
            $resultUpdate = pg_query_params($conn, $queryUpdate, array($cantidad, $codigoBarras, $Id_Negocios));

            if (!$resultUpdate) {
                throw new Exception("Error al actualizar stock del producto: {$producto['nombre']} - " . pg_last_error($conn));
            }
        }

        // Confirmar transacción
        pg_query($conn, "COMMIT");

        // Limpiar carrito si existe
        if (isset($_SESSION['carrito'])) {
            unset($_SESSION['carrito']);
        }

        // Respuesta exitosa en formato JSON
        echo json_encode([
            'success' => true,
            'mensaje' => "Venta realizada con éxito. Folio: $folio - Total: $" . number_format($total, 2),
            'folio' => $folio,
            'total' => number_format($total, 2)
        ]);

    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK");
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }

    exit();
}

// Procesar cotización y guardar en BD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cotizacion'])) {
    try {
        // Iniciar transacción
        pg_query($conn, "BEGIN");
        if (!isset($_SESSION['idNegocio'])) {
            throw new Exception("No se encontró el ID del negocio");
        }
        $Id_Negocios = intval($_SESSION['idNegocio']);
        $ClaveTrabajador = $_SESSION['ClaveTrabajador'];
        
        // Generar folio único
        $folio = 'COT-' . date('YmdHis');
        $total = 0;

        $productosCotizacion = json_decode($_POST['cotizacion']['productos'], true);

        $queryCotizacion = "INSERT INTO cotizaciones 
            (folio, descripcion, preciosu, cantidades, codigosbarras, marcas, preciofinal, clavetrabajador, fecha, idnegocio) 
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, NOW(), $9)";

        foreach ($productosCotizacion as $producto) {
            $descripcion = strtoupper($producto['nombre']);
            $precioUnitario = floatval($producto['precio']);
            $cantidad = floatval($producto['cantidad']);
            $codigoBarras = strtoupper($producto['codigo_barras']);
            $marca = strtoupper($producto['marca']);
            $precioFinal = $precioUnitario * $cantidad;
            $total += $precioFinal;

            $result = pg_query_params($conn, $queryCotizacion, array(
                $folio,
                $descripcion,
                $precioUnitario,
                $cantidad,
                $codigoBarras,
                $marca,
                $precioFinal,
                $ClaveTrabajador,
                $Id_Negocios
            ));
            if (!$result) {
                throw new Exception("Error al guardar el producto: {$producto['nombre']} - " . pg_last_error($conn));
            }
        }

        pg_query($conn, "COMMIT");

        echo json_encode([
            'success' => true,
            'mensaje' => "Cotización guardada. Folio: $folio",
            'folio' => $folio,
            'total' => number_format($total, 2),
            'productos' => $productosCotizacion
        ]);

    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK");
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
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

        th,
        td {
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

        .Op-1,
        .Op-2 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .Op-1 button,
        .Op-2 button {
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            background-color: var(--secondary);
            color: white;
        }

        .Op-1 button:hover,
        .Op-2 button:hover {
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

        #tablaProductosSimilares {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        #tablaProductosSimilares th, #tablaProductosSimilares td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        #tablaProductosSimilares th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        #tablaProductosSimilares tr:hover {
            background-color: #f9f9f9;
        }

        #tablaProductosSimilares button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        #tablaProductosSimilares button:hover {
            background-color: #091f5e;
        }
    </style>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php if (isset($_SESSION['venta_exitosa'])): ?>
        <script>
            Swal.fire({
                title: 'Venta Exitosa',
                text: '<?= addslashes($_SESSION['mensaje_venta']) ?>',
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Imprimir Ticket'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirigir al dashboard u otra página
                    window.location.href = 'Dashboard.php';
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    // Aquí puedes redirigir a imprimir ticket o hacer algo
                    window.location.href = 'imprimir_ticket.php?folio=<?= substr($_SESSION['mensaje_venta'], 25, 15) ?>';
                }
            });
        </script>
        <?php
        unset($_SESSION['venta_exitosa']);
        unset($_SESSION['mensaje_venta']);
        ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['venta_error'])): ?>
        <script>
            Swal.fire({
                title: 'Error',
                text: '<?= addslashes($_SESSION['venta_error']) ?>',
                icon: 'error',
                confirmButtonText: 'Entendido'
            }).then(() => {
                window.location.href = 'ventas.php'; // Puedes cambiar esto por donde quieras regresar
            });
        </script>
        <?php
        unset($_SESSION['venta_error']);
        ?>
    <?php endif; ?>
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
                <!-- Formulario para cargar cotización -->
                <form id="formCargarCotizacion" class="form-bus">
                    <input type="text" autocomplete="off" id="folioCotizacion" placeholder="Folio de Cotización" class="inBus">
                    <button type="button" id="btnCargarCotizacion" class="btn-bus">Cargar Cotización</button>
                </form>
                <form id="searchForm" class="form-bus">
                    <input type="text" autocomplete="off" id="searchInput" placeholder="Código de Barras, Nombre o Código de Producto" class="inBus" autofocus>
                    <button type="button" id="searchBtn" class="btn-bus">Buscar</button>
                </form>

                <form id="saleForm" method="post" autocomplete="off">
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
                <button onclick="window.location.href='./Catalogo/Index.php'">Catálogo</button>
                <!-- <button onclick="window.location.href='Clientes.php'">Clientes</button>-->
            </div>
            <div class="Op-2">
                <button onclick="window.location.href='Inventario.php'">Inventario</button>
                <button onclick="window.location.href='Configuraciones.php'">Configuraciones</button>
                <button onclick="window.location.href='CorteCaja.php'">Corte Caja<br>Análisis</button>
            </div>
        </div>
    </section>

    <!-- Contenido previo -->

<!-- Modal de Productos Similares -->
<div id="modalProductosSimilares" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
    <div style="background:white; margin:5% auto; padding:20px; width:60%; max-height:70vh; overflow-y:auto; border-radius:8px;">
        <h3>Productos Similares Encontrados</h3>
        <table id="tablaProductosSimilares" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Código Producto</th>
                    <th>Precio</th>
                    <th>Existencia</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <br>
        <button onclick="cerrarModal()" style="float:right;">Cerrar</button>
    </div>
</div>

<!-- Aquí va tu script -->
<script>
    // Variables globales
    const productsTableBody = document.getElementById('productsTableBody');
    const cart = {};
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const productsTableBody = document.getElementById('productsTableBody');
    const btnVenta = document.getElementById('btnVenta');
    const btnCotizacion = document.getElementById('btnCotizacion');
    const totalAmount = document.getElementById('totalAmount');

    // Objeto para almacenar los productos en el carrito
    const cart = {};



        // Función para agregar producto al carrito
    window.addProductToCart = function(product) {
    const existencia = parseFloat(product.existencia);
    const precio = parseFloat(product.precio);

    cart[product.codigobarras] = {
        nombre: product.nombre,
        precio: precio,
        cantidad: 1.0,
        stock: existencia,
        codigo_barras: product.codigobarras,
        marca: product.marca || ''
    };

    const row = document.createElement('tr');
    row.className = 'product-row';
    row.dataset.barcode = product.codigobarras;

    row.innerHTML = `
        <td>${product.nombre}</td>
        <td>$${precio.toFixed(2)}</td>
        <td>
            <input type="number" name="cantidad" value="1.00" min="0.01" step="0.01" max="${existencia.toFixed(2)}"
                   onchange="updateQuantity('${product.codigobarras}', this.value)">
        </td>
        <td>${existencia.toFixed(2)}</td>
        <td class="subtotal">$${precio.toFixed(2)}</td>
        <td><button class="btn-eliminar" onclick="removeProduct('${product.codigobarras}')">Eliminar</button></td>
    `;

    productsTableBody.appendChild(row);
    updateTotal(); // Esta línea es importante
}

    // Abre el modal con productos similares
    window.abrirModal = function(productos) {
        const tbody = document.querySelector('#tablaProductosSimilares tbody');
        tbody.innerHTML = '';

        productos.forEach(prod => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${prod.nombre}</td>
                <td>${prod.codigoproducto}</td>
                <td>$${parseFloat(prod.precio).toFixed(2)}</td>
                <td>${prod.existencia}</td>
                <td><button onclick='seleccionarProducto(${JSON.stringify(prod)})'>Seleccionar</button></td>`;
            tbody.appendChild(tr);
        });

        document.getElementById('modalProductosSimilares').style.display = 'block';
    }

    // Selecciona producto del modal y lo agrega al carrito
    window.seleccionarProducto = function(producto) {
        addProductToCart(producto);
        cerrarModal();
    }

    // Cierra el modal
    window.cerrarModal = function() {
        document.getElementById('modalProductosSimilares').style.display = 'none';
    }

    // Elimina producto del carrito
    window.removeProduct = function(codigoBarras) {
        delete cart[codigoBarras];
        const row = document.querySelector(`tr[data-barcode="${codigoBarras}"]`);
        if (row) row.remove();
        updateTotal();
    }


    // Cargar cotización por folio
document.getElementById('btnCargarCotizacion').addEventListener('click', async () => {
    const folio = document.getElementById('folioCotizacion').value.trim();
    if (!folio) {
        alert('Por favor ingrese un folio válido');
        return;
    }

    try {
        const response = await fetch(`obtener_cotizacion.php?folio=${encodeURIComponent(folio)}`);
        const data = await response.json();

        if (data.success) {
            // Limpiar carrito actual
            clearCart();

            // Agregar productos a la tabla
            data.productos.forEach(p => {
                const prod = {
                    nombre: p.descripcion,
                    precio: parseFloat(p.preciosu),
                    cantidad: parseFloat(p.cantidades),
                    codigobarras: p.codigosbarras,
                    marca: p.marcas,
                    existencia: parseFloat(p.existencia) || 0
                };
                addProductToCart(prod);
            });

            Swal.fire({
                title: 'Cotización Cargada',
                text: `Folio: ${folio}`,
                icon: 'success',
                confirmButtonText: 'Aceptar'
            });

        } else {
            Swal.fire('Error', data.error || 'Cotización no encontrada', 'error');
        }
    } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Hubo un problema al cargar la cotización.', 'error');
    }
});

async function searchProduct() {
    const searchTerm = searchInput.value.trim();
    if (!searchTerm) {
        alert('Por favor ingrese un término de búsqueda');
        return;
    }

    try {
        const response = await fetch('Dashboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `search_term=${encodeURIComponent(searchTerm)}`
        });

        const result = await response.json();

        if (result.success && result.data.length > 0) {
            let matches = result.data;

            // Buscar coincidencias exactas por código de barras o código de producto
            const matchByBarcode = matches.find(p => p.codigobarras === searchTerm);
            const matchByProductCode = matches.find(p => p.codigoproducto === searchTerm);

            let productToUse = matchByBarcode || matchByProductCode || matches[0];

            // Verificar si todos son iguales en codigobarras y codigoproducto
            const allSameBarcode = matches.every(p => p.codigobarras === matches[0].codigobarras);
            const allSameProductCode = matches.every(p => p.codigoproducto === matches[0].codigoproducto);

            // Si todos son iguales en ambos campos, tratar como único
            if (allSameBarcode && allSameProductCode && matches.length > 1) {
                productToUse = matches[0];
            }

            // Filtrar si ya está en el carrito
            if (cart[productToUse.codigobarras]) {
                const row = document.querySelector(`tr[data-barcode="${productToUse.codigobarras}"]`);
                const inputCantidad = row.querySelector('input[name="cantidad"]');
                let currentQuantity = parseFloat(inputCantidad.value);
                const maxStock = productToUse.existencia;

                if (currentQuantity + 1 > maxStock) {
                    alert(`No hay suficiente stock para ${productToUse.nombre}. Máximo disponible: ${maxStock}`);
                    return;
                }

                inputCantidad.value = (currentQuantity + 1).toFixed(2);
                cart[productToUse.codigobarras].cantidad = currentQuantity + 1;
                updateSubtotal(row, productToUse.precio);

            } else {
                // Si hay más de uno con distintos datos, abre el modal
                if (matches.length > 1 && (!allSameBarcode || !allSameProductCode)) {
                    abrirModal(matches);
                } else {
                    addProductToCart(productToUse);
                }
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

    // Escuchar eventos de búsqueda
    searchBtn.addEventListener('click', searchProduct);
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchProduct();
        }
    });

    // Función para agregar producto al carrito
function addProductToCart(product) {
    const existencia = parseFloat(product.existencia);
    const precio = parseFloat(product.precio);

    // Asegúrate de que todas las propiedades necesarias estén presentes
    cart[product.codigobarras] = {
        nombre: product.nombre,
        precio: precio,
        cantidad: 1.0,
        stock: existencia,
        codigo_barras: product.codigobarras,
        marca: product.marca || ''
    };

    // Crear fila en la tabla del carrito
    const row = document.createElement('tr');
    row.className = 'product-row';
    row.dataset.barcode = product.codigobarras;

    row.innerHTML = `
        <td>${product.nombre}</td>
        <td>$${precio.toFixed(2)}</td>
        <td>
            <input type="number" name="cantidad" value="1.00" min="0.01" step="0.01" max="${existencia.toFixed(2)}"
                   onchange="updateQuantity('${product.codigobarras}', this.value)">
        </td>
        <td>${existencia.toFixed(2)}</td>
        <td class="subtotal">$${precio.toFixed(2)}</td>
        <td>
            <button class="btn-eliminar" onclick="removeProduct('${product.codigobarras}')">Eliminar</button>
        </td>
    `;

    // Agregar fila a la tabla
    productsTableBody.appendChild(row);

    // Actualizar total
    updateTotal();
}

// Selecciona producto del modal y lo agrega al carrito
function seleccionarProducto(producto) {
    addProductToCart(producto);
    cerrarModal();
}

// Cierra el modal
function cerrarModal() {
    document.getElementById('modalProductosSimilares').style.display = 'none';
}


    // Actualizar cantidad
    window.updateQuantity = function(barcode, quantity) {
        quantity = parseFloat(quantity);
        if (isNaN(quantity) || quantity <= 0) {
            alert('Por favor ingrese un número válido mayor que cero');
            return;
        }
        if (cart[barcode]) {
            const maxStock = cart[barcode].stock;
            quantity = Math.min(quantity, maxStock);
            cart[barcode].cantidad = quantity;
            const row = document.querySelector(`tr[data-barcode="${barcode}"]`);
            const input = row.querySelector('input[name="cantidad"]');
            input.value = quantity.toFixed(2);
            const precio = cart[barcode].precio;
            updateSubtotal(row, precio);
        }
    };

    // Actualizar subtotal
    function updateSubtotal(row, precio) {
        const cantidadInput = row.querySelector('input[name="cantidad"]');
        const cantidad = parseFloat(cantidadInput.value);
        if (isNaN(cantidad)) {
            cantidadInput.value = '1.00';
            return;
        }
        const barcode = row.dataset.barcode;
        if (cart[barcode]) {
            cart[barcode].cantidad = cantidad;
        }
        const subtotal = (precio * cantidad).toFixed(2);
        row.querySelector('.subtotal').textContent = `$${subtotal}`;
        updateTotal();
    }

    // Eliminar producto
    window.removeProduct = function(barcode) {
        if (confirm('¿Eliminar este producto del carrito?')) {
            delete cart[barcode];
            document.querySelector(`tr[data-barcode="${barcode}"]`).remove();
            updateTotal();
        }
    };

    // Calcular total
    window.updateTotal = function() {
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

    // Realizar venta (con SweetAlert2)
    btnVenta.addEventListener('click', function () {
        if (Object.keys(cart).length === 0) {
            Swal.fire('Oops!', 'No hay productos en el carrito.', 'warning');
            return;
        }

        Swal.fire({
            title: '¿Confirmar venta?',
            text: "Una vez confirmada, no podrás deshacer esta acción.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, vender',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('venta[productos]', JSON.stringify(Object.values(cart)));

                fetch('Dashboard.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mostrar mensaje de éxito con SweetAlert2
                        Swal.fire({
                            title: 'Venta Exitosa',
                            text: data.mensaje,
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: 'Aceptar',
                            cancelButtonText: 'Imprimir Ticket'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                clearCart(); // Limpia el carrito visualmente
                            } else if (result.dismiss === Swal.DismissReason.cancel) {
                                window.open('imprimir_ticket.php?folio=' + data.folio, '_blank');
                                clearCart(); // Limpia el carrito visualmente
                            }
                        });
                    } else {
                        Swal.fire('Error', data.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Hubo un problema al procesar la venta.', 'error');
                });
            }
        });
    });

    // Realizar cotización (opcional)
btnCotizacion.addEventListener('click', function () {
    if (Object.keys(cart).length === 0) {
        Swal.fire('Oops!', 'No hay productos en el carrito.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('cotizacion[productos]', JSON.stringify(Object.values(cart)));

    fetch('Dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar SweetAlert con opciones
            Swal.fire({
                title: 'Cotización Guardada',
                html: `
                    <p><strong>Folio:</strong> ${data.folio}</p>
                    <p><strong>Total:</strong> $${data.total}</p>
                `,
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Imprimir Ticket'
            }).then((result) => {
                if (result.isConfirmed) {
                    clearCart(); // Limpia el carrito visualmente
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    // Redirigir a imprimir ticket
                    window.open(`imprimir_cotizacion.php?folio=${data.folio}`, '_blank');
                    clearCart(); // Limpia el carrito visualmente
                }
            });
        } else {
            Swal.fire('Error', data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Hubo un problema al generar la cotización.', 'error');
    });
});

    // Limpiar carrito visual
    function clearCart() {
        productsTableBody.innerHTML = '';
        totalAmount.textContent = '0.00';
        Object.keys(cart).forEach(key => delete cart[key]);
    }
});

//

// Abre el modal con productos similares
function abrirModal(productos) {
    const tbody = document.querySelector('#tablaProductosSimilares tbody');
    tbody.innerHTML = '';

    productos.forEach(prod => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${prod.nombre}</td>
            <td>${prod.codigoproducto}</td>
            <td>$${parseFloat(prod.precio).toFixed(2)}</td>
            <td>${prod.existencia}</td>
            <td><button onclick='seleccionarProducto(${JSON.stringify(prod)})'>Seleccionar</button></td>`;
        tbody.appendChild(tr);
    });

    document.getElementById('modalProductosSimilares').style.display = 'block';
}

</script>
</body>

</html>