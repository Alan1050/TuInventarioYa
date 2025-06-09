<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
include '../include/conn.php';

$id = $_GET['id'] ?? '';

try {
    if (empty($id)) {
        throw new Exception("ID de producto no proporcionado");
    }
    
    $query = "DELETE FROM producto WHERE id_Producto = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Producto eliminado correctamente'
    ]);
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>