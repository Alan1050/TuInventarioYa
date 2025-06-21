<?php
include './include/conn.php';

// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger y sanitizar los datos del formulario
    $nombreNegocio = htmlspecialchars($_POST['nombreNegocio']);
    $nombrePropietario = htmlspecialchars($_POST['nombrePropietario']);
    $codigoPostal = htmlspecialchars($_POST['codigoPostal']);
    $telefono = htmlspecialchars($_POST['telefono']);
    $direccion = htmlspecialchars($_POST['direccion']);
    $email = htmlspecialchars($_POST['email']);
    $precioPublico = htmlspecialchars($_POST['precioPublico']);
    $tipoNegocio = htmlspecialchars($_POST['tipoNegocio']);

    // Procesar el nombre del propietario
    $nombresApellidos = explode(' ', trim($nombrePropietario));
    $primerNombre = isset($nombresApellidos[0]) ? $nombresApellidos[0] : '';
    $segundoNombre = isset($nombresApellidos[1]) ? $nombresApellidos[1] : '';
    $primerApellido = isset($nombresApellidos[2]) ? $nombresApellidos[2] : '';
    $segundoApellido = isset($nombresApellidos[3]) ? $nombresApellidos[3] : '';
    
    // Obtener iniciales
    $inicialPrimerNombre = !empty($primerNombre) ? strtoupper(substr($primerNombre, 0, 1)) : '';
    $inicialSegundoNombre = !empty($segundoNombre) ? strtoupper(substr($segundoNombre, 0, 1)) : '';
    $inicialPrimerApellido = !empty($primerApellido) ? strtoupper(substr($primerApellido, 0, 1)) : '';
    $inicialSegundoApellido = !empty($segundoApellido) ? strtoupper(substr($segundoApellido, 0, 1)) : '';
    
    // Crear array para los horarios
    $horarios = array(
        'lunes' => array(
            'inicio' => empty($_POST['lunesInicio']) ? 'Cerrado' : $_POST['lunesInicio'],
            'fin' => empty($_POST['lunesFin']) ? 'Cerrado' : $_POST['lunesFin']
        ),
        'martes' => array(
            'inicio' => empty($_POST['martesInicio']) ? 'Cerrado' : $_POST['martesInicio'],
            'fin' => empty($_POST['martesFin']) ? 'Cerrado' : $_POST['martesFin']
        ),
        'miercoles' => array(
            'inicio' => empty($_POST['miercolesInicio']) ? 'Cerrado' : $_POST['miercolesInicio'],
            'fin' => empty($_POST['miercolesFin']) ? 'Cerrado' : $_POST['miercolesFin']
        ),
        'jueves' => array(
            'inicio' => empty($_POST['juevesInicio']) ? 'Cerrado' : $_POST['juevesInicio'],
            'fin' => empty($_POST['juevesFin']) ? 'Cerrado' : $_POST['juevesFin']
        ),
        'viernes' => array(
            'inicio' => empty($_POST['viernesInicio']) ? 'Cerrado' : $_POST['viernesInicio'],
            'fin' => empty($_POST['viernesFin']) ? 'Cerrado' : $_POST['viernesFin']
        ),
        'sabado' => array(
            'inicio' => empty($_POST['sabadoInicio']) ? 'Cerrado' : $_POST['sabadoInicio'],
            'fin' => empty($_POST['sabadoFin']) ? 'Cerrado' : $_POST['sabadoFin']
        ),
        'domingo' => array(
            'inicio' => empty($_POST['domingoInicio']) ? 'Cerrado' : $_POST['domingoInicio'],
            'fin' => empty($_POST['domingoFin']) ? 'Cerrado' : $_POST['domingoFin']
        )
    );

    $HorariosArray = json_encode($horarios);

    // Modificar la consulta SQL para PostgreSQL
    $InsertDatosNegocios = "INSERT INTO negocios 
        (nombre, propietario, cp, numtelefono, ubicacion, email, tipo, horarios, estadoactividad, precioslinea) 
        VALUES (
            $1, $2, $3, $4, $5, $6, $7, $8, 'Si', $9
        ) RETURNING idnegocio";

    $result = pg_query_params($conn, $InsertDatosNegocios, array(
        $nombreNegocio, 
        $nombrePropietario,
        $codigoPostal, 
        $telefono, 
        $direccion, 
        $email, 
        $tipoNegocio, 
        $HorariosArray, 
        $precioPublico
    ));

    if ($result) {
        $row = pg_fetch_row($result);
        $idNegocio = $row[0];
    } else {
        die("Error al insertar negocio: " . pg_last_error($conn));
    }

    $codigo = '';
    for ($i = 0; $i < 5; $i++) {
        $codigo .= rand(0, 9);
    }

    $clave = $inicialPrimerNombre . $inicialSegundoNombre . $inicialPrimerApellido . $inicialSegundoApellido . $codigo;
    $Pass = $primerNombre . $codigo . $segundoNombre;
    $Nombre = $primerNombre . $segundoNombre;

    $DatosDueño = "INSERT INTO vendedor (nombre, apematerno, apepaterno, clave, pass , rol, idnegocio, email, numtelefono) 
        VALUES ($1, $2, $3, $4, $5, 'Administrador', $6, $7, $8)";
    
    $result = pg_query_params($conn, $DatosDueño, array(
        $Nombre,
        $primerApellido,
        $segundoApellido,
        $clave,
        $Pass,
        $idNegocio,
        $email,
        $telefono
    ));


    if (!$result) {
        die("Error al insertar vendedor: " . pg_last_error($conn));
    }

    // Mostrar SweetAlert2 después del registro exitoso
    echo '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Registro Exitoso</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            Swal.fire({
                title: "¡Registro Exitoso!",
                html: `El negocio <b>'.$nombreNegocio.'</b> ha sido registrado correctamente.<br><br>
                Tu prueba de 7 dias gratis inicia ahora <br><br>
                       Te llegará un correo con la clave de acceso`,
                icon: "success",
                confirmButtonText: "Aceptar",
                customClass: {
                    popup: "animated bounceIn",
                    confirmButton: "btn-success"
                },
                buttonsStyling: false,
                timer: 10000,
                timerProgressBar: true,
                willClose: () => {
                    window.location.href = "index.html";
                }
            });
        </script>
    </body>
    </html>
    ';

    echo '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Exitoso</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/emailjs-com@3/dist/email.min.js"></script> 
    <script type="text/javascript">
        (function(){
            emailjs.init("m08KhDfnF_sAY8vNM"); // Tu Public Key
        })();
    </script>
</head>
<body>
    <script>
        Swal.fire({
            title: "¡Registro Exitoso!",
            html: `El negocio <b>'.$nombreNegocio.'</b> ha sido registrado correctamente.<br><br>
                   Tu prueba de 7 días gratis inicia ahora.<br><br>
                   Te llegará un correo con la clave de acceso.`,
            icon: "success",
            confirmButtonText: "Aceptar",
            customClass: {
                popup: "animated bounceIn",
                confirmButton: "btn-success"
            },
            buttonsStyling: false,
            timer: 10000,
            timerProgressBar: true,
            willClose: () => {
                window.location.href = "index.html";
            }
        });

        // Enviar correo usando EmailJS desde frontend
        document.addEventListener("DOMContentLoaded", function() {
            const templateParams = {
                nombre: "'.$nombrePropietario.'",
                negocioName: "'.$nombreNegocio.'",
                clave: "'.$clave.'",
                pass: "'.$Pass.'",
                email: "'.$email.'"
            };

            emailjs.send("service_a2o1ztr", "template_cnfcw85", templateParams)
                .then(function(response) {
                    console.log("✅ Correo enviado:", response.status, response.text);
                }, function(error) {
                    console.error("❌ Error al enviar correo:", error);
                });
        });
    </script>
</body>
</html>
';
    
} else {
    // Si alguien intenta acceder directamente al script sin enviar el formulario
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            title: "Error",
            text: "Acceso no autorizado",
            icon: "error",
            confirmButtonText: "Aceptar"
        }).then(() => {
            window.location.href = "index.php";
        });
    </script>
    ';
}
?>