<?php
session_start();

// Configuración de la base de datos
include '../include/conn.php';

// Verificar conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Obtener el id_Negocio de la sesión (ajusta según tu sistema)
$id_Negocio = $_SESSION['id_Negocio'] ?? 1; // Valor por defecto si no existe

if (isset($_POST['products'])) {
    $savedCount = 0;
    $errors = [];
    
    // Preparar la consulta SQL
    $stmt = $conn->prepare("
        INSERT INTO producto 
        (Tipo, Nombre, CodigoBarras, CodigoProducto, CodigoPrincipal, Marca, Existencia, Precio, UltimaFecha, id_Negocio) 
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
    ");
    
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    
    // Vincular parámetros
    $stmt->bind_param("ssssssids", 
        $tipo, 
        $nombre, 
        $codigoBarras, 
        $codigoProducto, 
        $codigoPrincipal, 
        $marca, 
        $existencia, 
        $precio, 
        $id_Negocio
    );
    
    foreach ($_POST['products'] as $index => $product) {
        // Validación básica
        if (empty($product['Nombre']) || empty($product['Tipo']) || 
            !isset($product['Existencia']) || !isset($product['Precio'])) {
            $errors[] = "Producto #$index: Faltan campos obligatorios";
            continue;
        }
        
        try {
            // Asignar valores a las variables vinculadas
            $tipo = $product['Tipo'];
            $nombre = trim($product['Nombre']);
            $codigoBarras = !empty($product['CodigoBarras']) ? trim($product['CodigoBarras']) : null;
            $codigoProducto = !empty($product['CodigoProducto']) ? trim($product['CodigoProducto']) : null;
            $codigoPrincipal = !empty($product['CodigoPrincipal']) ? trim($product['CodigoPrincipal']) : null;
            $marca = !empty($product['Marca']) ? trim($product['Marca']) : null;
            $existencia = intval($product['Existencia']);
            $precio = floatval($product['Precio']);
            
            // Ejecutar la consulta
            if (!$stmt->execute()) {
                $errors[] = "Producto #$index: " . $stmt->error;
            } else {
                $savedCount++;
            }
        } catch(Exception $e) {
            $errors[] = "Producto #$index: " . $e->getMessage();
        }
    }
    
    if ($savedCount > 0) {
        $_SESSION['message'] = "Se guardaron $savedCount productos correctamente.";
        if (!empty($errors)) {
            $_SESSION['message'] .= "<br>Errores: " . implode(", ", $errors);
        }
    } else {
        $_SESSION['error'] = "No se pudo guardar ningún producto. Errores: " . implode(", ", $errors);
    }
    
    $stmt->close();
} else {
    $_SESSION['error'] = "No se recibieron productos para guardar.";
}

$conn->close();
header('Location: AgregarProducto.php');
exit();
?>