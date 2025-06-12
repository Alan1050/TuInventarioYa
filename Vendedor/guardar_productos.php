<?php
session_start();

// Configuración de la base de datos
include '../include/conn.php'; // Debe contener una conexión PostgreSQL válida

// Verificar conexión
if (!$conn) {
    die("Error: No se pudo conectar a la base de datos");
}

// Obtener el id_Negocio de la sesión
$id_Negocio = $_SESSION['id_Negocio'] ?? 1; // Puedes ajustarlo según tu sistema

$savedCount = 0;
$errors = [];

if (!isset($_POST['products']) || !is_array($_POST['products'])) {
    $_SESSION['error'] = "No se recibieron productos para guardar.";
    header('Location: AgregarProducto.php');
    exit();
}

// Preparar consulta única
$query = "INSERT INTO producto 
    (tipo, nombre, codigobarras, codigoproducto, codigoprincipal, marca, existencia, precio, ultimafecha, idnegocio) 
    VALUES 
    ($1, $2, $3, $4, $5, $6, $7, $8, NOW(), $9)";

foreach ($_POST['products'] as $index => $product) {
    if (empty($product['Nombre']) || empty($product['Tipo']) ||
        !isset($product['Existencia']) || !isset($product['Precio'])) {
        $errors[] = "Producto #$index: Faltan campos obligatorios";
        continue;
    }

    try {
        // Limpiar y formatear los campos de texto a mayúsculas, reemplazando '
        $tipo = cleanText($product['Tipo']);
        $nombre = cleanText($product['Nombre']);
        $codigoBarras = cleanText($product['CodigoBarras'] ?? '');
        $codigoProducto = cleanText($product['CodigoProducto'] ?? '');
        $codigoPrincipal = cleanText($product['CodigoPrincipal'] ?? '');
        $marca = cleanText($product['Marca'] ?? '');

        $existencia = intval($product['Existencia']);
        $precio = floatval($product['Precio']);

        // Ejecutar consulta parametrizada
        $result = pg_query_params($conn, $query, array(
            $tipo,
            $nombre,
            $codigoBarras,
            $codigoProducto,
            $codigoPrincipal,
            $marca,
            $existencia,
            $precio,
            $id_Negocio
        ));

        if ($result === false) {
            $errorMsg = pg_last_error($conn);
            $errors[] = "Producto #$index: Error al guardar - $errorMsg";
        } else {
            $savedCount++;
        }
    } catch (Exception $e) {
        $errors[] = "Producto #$index: " . $e->getMessage();
    }
}

// Mensajes de resultado
if ($savedCount > 0) {
    $_SESSION['message'] = "Se guardaron $savedCount productos correctamente.";
    if (!empty($errors)) {
        $_SESSION['message'] .= "<br>Errores: " . implode("<br>", $errors);
    }
} else {
    $_SESSION['error'] = "No se pudo guardar ningún producto. Errores: " . implode("<br>", $errors);
}

header('Location: AgregarProducto.php');
exit();

// ----------------------------
// FUNCIÓN AUXILIAR
// ----------------------------
function cleanText($text) {
    if (empty($text)) return null;
    // Reemplazar apóstrofes y otros caracteres problemáticos
    $text = str_replace("'", "-", $text);
    // Convertir a mayúsculas
    return strtoupper(trim($text));
}