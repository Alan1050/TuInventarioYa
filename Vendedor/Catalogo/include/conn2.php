<?php
// Configuración de la conexión
$dbhost = "localhost";
$dbport = "5432";
$dbname = "catalogo"; // Recuerda cambiar esto si creas otra base dedicada
$dbuser = "postgres";
$dbpass = "Alan10";

// Cadena de conexión
$connection_string = "host={$dbhost} port={$dbport} dbname={$dbname} user={$dbuser} password={$dbpass}";

// Conectar a PostgreSQL
$conn = pg_connect($connection_string);

// Verificar si hay errores de conexión
if (!$conn) {
    die("Error al conectar a PostgreSQL: " . pg_last_error());
}

// Opcional: mensaje de éxito (desactivar en producción)

?>