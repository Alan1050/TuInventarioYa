<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
include '../include/conn.php';

$data = $_POST;

try {
    if (empty($data['Tipo']) || empty($data['Nombre']) || !isset($data['Existencia']) || !isset($data['Precio'])) {
        throw new Exception("Faltan campos obligatorios");
    }
    
    // Insertar nuevo producto
    $query = "INSERT INTO producto 
              (Tipo, Nombre, CodigoBarras, CodigoProducto, CodigoPrincipal, Marca, Existencia, Precio, UltimaFecha, id_Negocio) 
              VALUES 
              (:Tipo, :Nombre, :CodigoBarras, :CodigoProducto, :CodigoPrincipal, :Marca, :Existencia, :Precio, NOW(), 1)";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':Tipo' => $data['Tipo'],
        ':Nombre' => $data['Nombre'],
        ':CodigoBarras' => $data['CodigoBarras'] ?? null,
        ':CodigoProducto' => $data['CodigoProducto'] ?? null,
        ':CodigoPrincipal' => $data['CodigoPrincipal'] ?? null,
        ':Marca' => $data['Marca'] ?? null,
        ':Existencia' => intval($data['Existencia']),
        ':Precio' => floatval($data['Precio'])
    ]);
    
    $id = $conn->lastInsertId();
    
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