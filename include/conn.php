<?php
$dbhost = "aws-0-us-east-1.pooler.supabase.com"; // Nuevo host
$dbport = "5432"; // Puerto de PostgreSQL
$dbname = "postgres"; // Nombre de la base de datos
$dbuser = "postgres.qghixtzvsjyokajkfrmw"; // Usuario con formato correcto
$dbpass = "AEMD100!a10"; // Tu contraseña

$conn = pg_connect("host=$dbhost port=$dbport dbname=$dbname user=$dbuser password=$dbpass");

if (!$conn) {
    die("Error de conexión: " . pg_last_error());
}

?>