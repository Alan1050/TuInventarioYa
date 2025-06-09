<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TuInventarioYa - Agregar Producto</title>
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

        .header-pt2 h1 {
            color: white;
            text-align: initial;
            padding-top: 28px;
            padding-left: 20px;
        }

        section {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .inBus {
            width: 100%;
            font-size: 18px;
            padding: 10px 15px;
            margin-bottom: 25px;
            border-radius: 5px;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .form-bus {
            margin-bottom: 20px;
        }

        /* Estilos para la tabla */
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

        /* Estilos para inputs y select */
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

        /* Botones */
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

        /* Mensajes */
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

        /* Responsive */
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
        <div class="header-pt1">
            <img src="../img/LogoPuroBlanco.png" alt="Logo" style="width: 60%; padding-top: 9px; margin-left: 35px;">
        </div>
        <div class="header-pt2">
            <h1>Tu Inventario Ya</h1>
        </div>
    </header>
    
    <section>
        <!-- Mostrar mensajes -->
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
        
        <form id="barcodeForm" class="form-bus">
            <input type="text" id="barcodeInput" placeholder="Escanea o ingresa el código de barras" class="inBus" autofocus>
        </form>
        
        <form id="productsForm" method="post" action="guardar_productos.php">
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
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    <!-- Las filas de productos se agregarán aquí dinámicamente -->
                </tbody>
            </table>
            
            <button type="submit" class="btn-guardar">Guardar Todos los Productos</button>
        </form>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const barcodeInput = document.getElementById('barcodeInput');
            const productsTableBody = document.getElementById('productsTableBody');
            
            // Variable para llevar el conteo de productos agregados
            let productCount = 0;
            
            // Escuchar el evento de entrada en el campo de código de barras
// Por esta versión que espera el Enter:
barcodeInput.addEventListener('keypress', async function(e) {
    // Verificar si se presionó Enter (código 13)
    if (e.key === 'Enter' || e.keyCode === 13) {
        e.preventDefault(); // Prevenir comportamiento por defecto
        
        if (e.target.value.length > 0) {
            const barcode = e.target.value.trim();
            
            // Verificar si el código ya existe
            const exists = await checkBarcodeExists(barcode);
            
            if (!exists) {
                addProductRow(barcode);
                e.target.value = '';
                e.target.focus();
            } else {
                alert('Este código de barras ya está registrado en el sistema');
                e.target.value = '';
            }
        }
    }
});
            
            // Función para verificar si un código de barras ya existe
            async function checkBarcodeExists(barcode) {
                try {
                    const response = await fetch(`check_barcode.php?code=${encodeURIComponent(barcode)}`);
                    const data = await response.json();
                    return data.exists;
                } catch (error) {
                    console.error('Error al verificar código:', error);
                    return false;
                }
            }
            
            // Función para agregar una nueva fila de producto
            function addProductRow(barcode) {
                productCount++;
                
                const newRow = document.createElement('tr');
                newRow.className = 'product-row';
                newRow.dataset.productId = productCount;
                
                newRow.innerHTML = `
                    <td>
                        <select name="products[${productCount}][Tipo]" required>
                            <option value="">Seleccione...</option>
                            <option value="Producto">Producto</option>
                            <option value="Servicio">Servicio</option>
                            <option value="Repuesto">Repuesto</option>
                        </select>
                    </td>
                    <td><input type="text" name="products[${productCount}][Nombre]" required></td>
                    <td><input type="text" name="products[${productCount}][CodigoBarras]" value="${barcode}" readonly></td>
                    <td><input type="text" name="products[${productCount}][CodigoProducto]"></td>
                    <td><input type="text" name="products[${productCount}][CodigoPrincipal]"></td>
                    <td><input type="text" name="products[${productCount}][Marca]"></td>
                    <td><input type="number" name="products[${productCount}][Existencia]" required min="0" value="1"></td>
                    <td><input type="number" step="0.01" name="products[${productCount}][Precio]" required min="0"></td>
                    <td><button type="button" class="btn-eliminar" onclick="removeProductRow(${productCount})">Eliminar</button></td>
                `;
                
                productsTableBody.appendChild(newRow);
                
                // Enfocar el primer campo editable
                newRow.querySelector('select[name*="[Tipo]"]').focus();
            }
            
            // Función global para eliminar filas
            window.removeProductRow = function(productId) {
                const row = document.querySelector(`tr[data-product-id="${productId}"]`);
                if (row) {
                    if (confirm('¿Estás seguro de eliminar este producto?')) {
                        row.remove();
                    }
                }
            };
        });
    </script>
</body>
</html>