<?php
session_start();
include '../include/conn.php';

if (!$conn) {
    die(json_encode(['success' => false, 'error' => 'No hay conexión']));
}

$id_negocio = intval($_POST['id_negocio']);
$nombre = pg_escape_string($conn, $_POST['nombre']);
$apepaterno = pg_escape_string($conn, $_POST['apepaterno']);
$apematerno = pg_escape_string($conn, $_POST['apematerno']);
$numtelefono = pg_escape_string($conn, $_POST['numtelefono']);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ? pg_escape_string($conn, $_POST['email']) : null;
$rol = 'Empleado'; // Rol por defecto

    $primerNombre = isset($nombre[0]) ? $nombre[0] : '';
    $segundoNombre = isset($nombre[0]) ? $nombre[1] : '';

        $inicialPrimerNombre = !empty($primerNombre) ? strtoupper(substr($primerNombre, 0, 1)) : '';
    $inicialSegundoNombre = !empty($segundoNombre) ? strtoupper(substr($segundoNombre, 0, 1)) : '';
        $inicialPrimerApellido = !empty($apepaterno) ? strtoupper(substr($apepaterno, 0, 1)) : '';
    $inicialSegundoApellido = !empty($apematerno) ? strtoupper(substr($apematerno, 0, 1)) : '';

      $codigo = '';
    for ($i = 0; $i < 5; $i++) {
        $codigo .= rand(0, 9);
    }

    $clave = $inicialPrimerNombre . $inicialSegundoNombre . $inicialPrimerApellido . $inicialSegundoApellido . $codigo;
    $Pass = $primerNombre . $codigo . $segundoNombre;

$query = "INSERT INTO vendedor 
    (nombre, apepaterno, apematerno, numtelefono, email, clave, rol, idnegocio, pass)
    VALUES 
    ('$nombre', '$apepaterno', '$apematerno', '$numtelefono', " . ($email ? "'$email'" : "NULL") . ", '$clave', '$rol', $id_negocio, '$Pass')";
    
$result = pg_query($conn, $query);

if ($result) {
    echo '<script>
        window.location.href="./Configuraciones.php";
    </script>';
} else {
       echo '<script>
        window.location.href="./Configuraciones.php";
    </script>';
}