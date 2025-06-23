<?php
session_start();
include '../include/conn.php';

$folio = $_GET['folio'] ?? '';
if (empty($folio)) {
    die('Folio no válido');
}

$query = "SELECT * FROM ventas WHERE folio = $1";
$result = pg_query_params($conn, $query, array($folio));

if (!$result || pg_num_rows($result) === 0) {
    die('No se encontró la cotización');
}

$productos = pg_fetch_all($result);
$total = array_sum(array_column($productos, 'preciofinal'));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ticket de Cotización</title>
    <style>
        body {
            width: 72mm; /* Ancho común de ticket */
            margin: 0 auto;
            padding: 5px;
            font-family: monospace;
            font-size: 12px;
            line-height: 1.4;
        }
        h2 {
            text-align: center;
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }
        .header, .footer {
            text-align: center;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 10px;
        }
        th, td {
            text-align: left;
            padding: 2px 0;
            border-bottom: 1px solid #000;
        }
        .total {
            font-weight: bold;
            font-size: 12px;
            text-align: right;
            margin-top: 10px;
        }
        @media print {
            body {
                background-color: white;
            }
            @page {
                size: auto;
                margin: 0;
            }
        }
    </style>
    <script>
        window.onload = function() {
            window.print(); // Imprime automáticamente al cargar
        };
    </script>
</head>
<body>
    <div class="header">
        <h2>Venta Realizada con Exito</h2>
        <p>Folio: <?= htmlspecialchars($folio) ?></p>
        <p><?= date('d/m/Y H:i') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Precio</th>
                <th>Cant.</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars(substr($p['descripcion'], 0, 15)) ?></td>
                    <td>$<?= number_format($p['preciosu'], 2) ?></td>
                    <td><?= $p['cantidades'] ?></td>
                    <td>$<?= number_format($p['preciofinal'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total">
        Total: $<?= number_format($total, 2) ?>
    </div>

    <div class="footer" style="margin-top: 20px;">
        <p>Gracias por su preferencia</p>
    </div>
</body>
</html>