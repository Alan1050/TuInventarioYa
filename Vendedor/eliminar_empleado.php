<?php
session_start();
include '../include/conn.php';

if (!$conn) {
    die(json_encode(['success' => false, 'error' => 'No hay conexión']));
}

$id = intval($_GET['id']);

$query = "DELETE FROM vendedor WHERE idvendedor = $1";
$result = pg_query_params($conn, $query, array($id));

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => pg_last_error($conn)]);
}