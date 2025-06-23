<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
include '../include/conn.php'; // Debe devolver una conexión pg_connect()

$data = $_POST;

try {
    if (
        empty($data['Tipo']) ||
        empty($data['Nombre']) ||
        !isset($data['Existencia']) ||
        !isset($data['Precio'])
    ) {
        throw new Exception("Faltan campos obligatorios");
    }

    $params = array(
        $data['Tipo'],
        $data['Nombre'],
        $data['CodigoBarras'] ?? null,
        $data['CodigoProducto'] ?? null,
        $data['CodigoPrincipal'] ?? null,
        $data['Marca'] ?? null,
        intval($data['Existencia']),
        floatval($data['Precio']),
        1 // id_Negocio
    );

    $query = "INSERT INTO producto 
                (Tipo, Nombre, CodigoBarras, CodigoProducto, CodigoPrincipal, Marca, Existencia, Precio, UltimaFecha, id_Negocio) 
              VALUES 
                ($1, $2, $3, $4, $5, $6, $7, $8, NOW(), $9)
              RETURNING id_Producto";

    $result = pg_query_params($conn, $query, $params);

    if (!$result) {
        throw new Exception("Error al guardar producto: " . pg_last_error($conn));
    }

    $row = pg_fetch_assoc($result);
    $id = $row['id_producto'];

    echo json_encode([
        'success' => true,
        'id_Producto' => $id,
        'message' => 'Producto guardado correctamente'
    ]);
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>