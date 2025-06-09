<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
include '../include/conn.php';

try {
    // Verificar conexión
    if (!$conn) {
        throw new Exception("No se pudo conectar a la base de datos");
    }

    $term = $_GET['term'] ?? '';
    $barcode = $_GET['barcode'] ?? '';

    if (!empty($barcode)) {
        $query = "SELECT * FROM producto WHERE CodigoBarras = :barcode";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $stmt->bindParam(':barcode', $barcode);
    } else {
        $query = "SELECT * FROM producto WHERE 
                 CodigoBarras LIKE :term OR 
                 Nombre LIKE :term OR 
                 CodigoProducto LIKE :term OR
                 Marca LIKE :term";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $searchTerm = "%$term%";
        $stmt->bindParam(':term', $searchTerm);
    }
    
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Verificar si hay productos
    if ($products === false) {
        $products = []; // Devolver array vacío si no hay resultados
    }
    
    echo json_encode([
        'success' => true,
        'data' => $products
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>