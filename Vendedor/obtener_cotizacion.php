<?php
session_start();
include '../include/conn.php';

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'No hay conexión a la base de datos']);
    exit();
}

$folio = $_GET['folio'] ?? '';
if (empty($folio)) {
    echo json_encode(['success' => false, 'error' => 'Folio vacío']);
    exit();
}

try {
    // Obtener los productos de la cotización
    $query = "SELECT * FROM cotizaciones WHERE folio = $1";
    $result = pg_query_params($conn, $query, array($folio));

    if ($result && pg_num_rows($result) > 0) {
        $productos = pg_fetch_all($result);

        // Si tenemos productos, obtenemos la existencia actual de cada uno
        foreach ($productos as &$p) {
            $codigobarras = $p['codigosbarras'];
            $idnegocio = $_SESSION['idNegocio'];

            // Consultar la existencia actual del producto
            $queryExistencia = "SELECT existencia FROM producto WHERE codigobarras = $1 AND idnegocio = $2";
            $resExistencia = pg_query_params($conn, $queryExistencia, array($codigobarras, $idnegocio));

            if ($resExistencia && pg_num_rows($resExistencia) > 0) {
                $row = pg_fetch_assoc($resExistencia);
                $p['existencia'] = $row['existencia'];
            } else {
                $p['existencia'] = 0; // Valor por defecto si no encuentra el producto
            }
        }

        echo json_encode([
            'success' => true,
            'productos' => $productos
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No se encontró la cotización'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al buscar cotización: ' . $e->getMessage()
    ]);
}
?>