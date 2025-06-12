<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
include '../include/conn.php'; // Debe contener una conexión válida usando pg_connect()
session_start();

try {
    // Verificar conexión
    if (!$conn) {
        throw new Exception("No se pudo conectar a la base de datos");
    }

    $term = $_GET['term'] ?? '';
    $barcode = $_GET['barcode'] ?? '';
    $Id_Negocios2 = intval($_SESSION['idNegocio']);

    if (!empty($barcode)) {
        $query = "SELECT * FROM producto WHERE codigobarras = $1 AND idnegocio = $2";
        $result = pg_query_params($conn, $query, array($barcode, $Id_Negocios2));
    } else {
        $searchTerm = "%$term%";
        $query = "SELECT * FROM producto WHERE 
                     (codigobarras = $1 OR 
                     nombre = $1 OR 
                     codigoproducto = $1) AND idnegocio = $2";
        $result = pg_query_params($conn, $query, array($searchTerm, $Id_Negocios2));
    }

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
        'error' => $e->getMessage()
    ]);
}