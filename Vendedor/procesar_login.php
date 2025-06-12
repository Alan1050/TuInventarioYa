<?php

session_start();

include 'https://tu-inventario-ya.vercel.app/include/conn.php'; // Asegúrate de que $conn es una conexión válida a PostgreSQL

if (!isset($_POST['clave']) || !isset($_POST['contrasena'])) {
    die("Faltan datos de inicio de sesión.");
}

$Clave = $_POST['clave'];
$Pass = $_POST['contrasena'];

// Consulta segura usando pg_query_params
$query = "SELECT * FROM vendedor WHERE clave = $1 AND pass = $2";
$result = pg_query_params($conn, $query, array($Clave, $Pass));

if (!$result) {
    die("Error en la consulta: " . pg_last_error($conn));
}

$rowCount = pg_num_rows($result);

if ($rowCount == 1) {
    $Datos = pg_fetch_assoc($result);
    $_SESSION['idNegocio'] = $Datos['idnegocio'];
    $_SESSION['idVendedor'] = $Datos['idvendedor'];
    $_SESSION['ClaveTrabajador'] = $Clave;

    // Redirigir con JavaScript
    echo '<script>
        window.location.href = "./Dashboard.php";
    </script>';
} else {
    // Mostrar alerta de error y redirección
    echo '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error de inicio</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>   
    </head>
    <body>
        <script>
            Swal.fire({
                title: "¡Usuario y/o contraseña incorrectos!",
                text: "Oh, oh! Usuario y/o contraseña erronea",
                icon: "error",
                confirmButtonText: "Aceptar",
                buttonsStyling: false,
                timer: 5000,
                timerProgressBar: true,
                willClose: () => {
                    window.location.href = "Login.html";
                }
            });
        </script>
    </body>
    </html>';
}