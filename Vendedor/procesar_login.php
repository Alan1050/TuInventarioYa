<?php

session_start();

include '../include/conn.php';

$Clave = $_POST['clave'];
$Pass = $_POST['contrasena'];

$Busqueda = "SELECT * FROM vendedor WHERE Clave = '$Clave' AND Pass = '$Pass'";
$queryBusqueda = mysqli_query($conn, $Busqueda);
$RowBusqueda = mysqli_num_rows($queryBusqueda);
$_SESSION['ClaveTrabajador'] = $Clave;
if ($RowBusqueda == 1) {
    $Datos = mysqli_fetch_array($queryBusqueda);
    $_SESSION['idNegocio'] = $Datos['id_Negocio'];
    $_SESSION['idVendedor'] = $Datos['id_Vendedor'];



    echo '<script>
        window.location.href = "./Dashboard.php";
    </script>';
} else {
    echo '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Usuario y/o contraseña erronea</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            Swal.fire({
                title: "¡Usuario y/o contraseña erronea!",
                html: `Oh, oh! Usuario y/o contraseña erronea`,
                icon: "error",
                confirmButtonText: "Aceptar",
                customClass: {
                    popup: "animated bounceIn",
                    confirmButton: "btn-success"
                },
                buttonsStyling: false,
                timer: 5000,
                timerProgressBar: true,
                willClose: () => {
                    window.location.href = "Login.html";
                }
            });
        </script>
    </body>
    </html>
    ';
}
