<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
include '../include/conn.php'; // Debe contener una conexión válida a PostgreSQL

try {
    // Verificar conexión
    if (!$conn) {
        throw new Exception("No se pudo conectar a la base de datos");
    }

    // Consulta SQL para PostgreSQL
    $query = "SELECT * FROM producto";

    // Ejecutar consulta usando pg_query
    $result = pg_query($conn, $query);

    if (!$result) {
        throw new Exception("Error al ejecutar la consulta: " . pg_last_error($conn));
    }

    // Obtener todos los productos
    $products = pg_fetch_all($result);

    // Devolver respuesta en JSON
    echo json_encode([
        'success' => true,
        'data' => $products ?: []
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}