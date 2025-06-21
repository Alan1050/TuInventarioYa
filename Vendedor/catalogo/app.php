<?php
header("Content-Type: application/json");

$host = "localhost";
$user = "root";
$password = "";
$dbname = "catalogo_carros";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) die(json_encode(["error" => "Error de conexión"]));

$accion = $_GET['accion'] ?? '';

switch ($accion) {
  case 'get_marcas':
    $sql = "SELECT * FROM marcas";
    break;

  case 'get_modelos':
    $marca_id = intval($_GET['marca_id']);
    $sql = "SELECT * FROM modelos WHERE marca_id = $marca_id";
    break;

  case 'get_modelos_all':
    $sql = "SELECT m.*, ma.nombre as marca_nombre FROM modelos m JOIN marcas ma ON m.marca_id = ma.id ORDER BY ma.nombre, m.nombre";
    break;

  case 'get_piezas':
    $modelo_id = intval($_GET['modelo_id']);
    $sql = "SELECT p.*, c.nombre as categoria FROM piezas p JOIN categorias c ON p.categoria_id = c.id WHERE p.modelo_id = $modelo_id";
    break;

  case 'get_categorias':
    $sql = "SELECT * FROM categorias";
    break;

  case 'add_marca':
    $data = json_decode(file_get_contents('php://input'), true);
    $nombre = $conn->real_escape_string($data['nombre']);
    $conn->query("INSERT INTO marcas (nombre) VALUES ('$nombre')");
    echo json_encode(["status" => "ok"]);
    exit;

  case 'add_modelo':
    $data = json_decode(file_get_contents('php://input'), true);
    $marca_id = $conn->real_escape_string($data['marca_id']);
    $nombre = $conn->real_escape_string($data['nombre']);
    $anio_inicio = $conn->real_escape_string($data['anio_inicio']);
    $anio_fin = $conn->real_escape_string($data['anio_fin']);
    $motor = $conn->real_escape_string($data['motor']);
    $conn->query("INSERT INTO modelos (marca_id, nombre, anio_inicio, anio_fin, motor) VALUES ('$marca_id','$nombre','$anio_inicio','$anio_fin','$motor')");
    echo json_encode(["status" => "ok"]);
    exit;

  case 'add_pieza':
    $data = json_decode(file_get_contents('php://input'), true);
    $modelo_id = $conn->real_escape_string($data['modelo_id']);
    $categoria_id = $conn->real_escape_string($data['categoria_id']);
    $nombre = $conn->real_escape_string($data['nombre']);
    $descripcion = $conn->real_escape_string($data['descripcion']);
    $conn->query("INSERT INTO piezas (modelo_id, categoria_id, nombre, descripcion,) VALUES ('$modelo_id','$categoria_id','$nombre','$descripcion')");
    echo json_encode(["status" => "ok"]);
    exit;

  case 'edit_pieza':
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $conn->real_escape_string($data['id']);
    $descripcion = $conn->real_escape_string($data['descripcion']);
    $conn->query("UPDATE piezas SET descripcion='$descripcion' WHERE id=$id");
    echo json_encode(["status" => "ok"]);
    exit;

  case 'delete_pieza':
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM piezas WHERE id=$id");
    echo json_encode(["status" => "ok"]);
    exit;

  default:
    echo json_encode(["error" => "Acción no válida"]);
    exit;
}

$result = $conn->query($sql);
$data = [];
while ($row = $result->fetch_assoc()) {
  $data[] = $row;
}
echo json_encode($data);
?>