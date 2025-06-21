<?php
include '../include/conn.php'; // Conexión PostgreSQL
session_start();

// Verificar sesión
if (!isset($_SESSION['idNegocio'])) {
    die("Error: No se encontró el ID del negocio.");
}
$idNegocio = intval($_SESSION['idNegocio']);

// Consultar datos del negocio
$Consulta = "SELECT * FROM negocios WHERE idnegocio = $1";
$query = pg_query_params($conn, $Consulta, array($idNegocio));
$Resul = pg_num_rows($query);

if ($Resul !== 1) {
    die("Error: No se encontró el negocio o hay múltiples negocios con ese ID.");
}

$DatosArrarNegocio = pg_fetch_assoc($query);
$horarios = json_decode($DatosArrarNegocio['horarios'], true);

// Mapeo de tipos de negocio
$tiposNegocio = [
    'ferreteria' => 'Ferretería',
    'electronica' => 'Electrónica',
    'telefonos_computadoras' => 'Teléfonos/Computadoras',
    'ropa' => 'Ropa',
    'supermercado' => 'Supermercado',
    'restaurante' => 'Restaurante',
    'otro' => 'Otro'
];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos
    $nombre = trim($_POST['nombre']);
    $cp = trim($_POST['cp']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) ? trim($_POST['email']) : null;
    $tipo = $_POST['tipo'];
    $preciosLinea = $_POST['precios_linea'];

    if (empty($nombre) || empty($tipo)) {
        echo "<script>alert('Por favor completa todos los campos obligatorios');</script>";
    } else {
        // Procesar horarios
        $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        $nuevosHorarios = [];

        foreach ($dias as $dia) {
            if (isset($_POST['cerrado'][$dia]) || !isset($_POST['horario'][$dia])) {
                // Si está cerrado o no se mandó, usar "Cerrado"
                $nuevosHorarios[$dia] = [
                    'inicio' => 'Cerrado',
                    'fin' => 'Cerrado'
                ];
            } else {
                $inicio = $_POST['horario'][$dia]['inicio'] ?? 'Cerrado';
                $fin = $_POST['horario'][$dia]['fin'] ?? 'Cerrado';

                $nuevosHorarios[$dia] = [
                    'inicio' => $inicio,
                    'fin' => $fin
                ];
            }
        }

        $horariosJson = json_encode($nuevosHorarios);

        // Actualizar en BD
        $updateQuery = "UPDATE negocios SET 
                        nombre = $1,
                        cp = $2,
                        numtelefono = $3,
                        ubicacion = $4,
                        email = $5,
                        tipo = $6,
                        precioslinea = $7,
                        horarios = $8
                        WHERE idnegocio = $9";

        $params = [
            $nombre,
            $cp,
            $telefono,
            $direccion,
            $email,
            $tipo,
            $preciosLinea,
            $horariosJson,
            $idNegocio
        ];

        $result = pg_query_params($conn, $updateQuery, $params);

        if ($result) {
            $DatosArrarNegocio['nombre'] = $nombre;
            $DatosArrarNegocio['cp'] = $cp;
            $DatosArrarNegocio['numtelefono'] = $telefono;
            $DatosArrarNegocio['ubicacion'] = $direccion;
            $DatosArrarNegocio['email'] = $email;
            $DatosArrarNegocio['tipo'] = $tipo;
            $DatosArrarNegocio['precioslinea'] = $preciosLinea;
            $horarios = $nuevosHorarios;

            echo "<script>alert('Datos actualizados correctamente');</script>";
        } else {
            echo "<script>alert('Error al actualizar los datos: " . pg_last_error($conn) . "');</script>";
        }
    }
}

// Obtener empleados del negocio actual
$empleados = [];

try {
    $queryEmpleados = "SELECT idvendedor, nombre, apepaterno, apematerno, numtelefono, email, clave, rol 
                       FROM vendedor 
                       WHERE idnegocio = $1 AND rol != 'Administrador'";
    $resultEmpleados = pg_query_params($conn, $queryEmpleados, array($idNegocio));

    if ($resultEmpleados) {
        $empleados = pg_fetch_all($resultEmpleados);
    }
} catch (Exception $e) {
    echo "<script>alert('Error al cargar empleados: " . addslashes($e->getMessage()) . "');</script>";
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuraciones - TuInventarioYa</title>
    <style>
        :root {
            --primary: #0a2463;
            --secondary: #3e92cc;
            --success: #4cb944;
            --warning: #ffc857;
            --danger: #d8315b;
            --dark: #2e2e2e;
            --light: #f5f5f5;
            --gray: #a0a0a0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: var(--dark);
            line-height: 1.6;
            display: grid;
            grid-template-areas:
                "header header"
                "aside main";
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        header {
            grid-area: header;
            background-color: var(--primary);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: white;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        nav a:hover {
            color: var(--warning);
        }

        aside {
            grid-area: aside;
            background-color: white;
            border-right: 1px solid #eee;
            padding: 1.5rem 0;
        }

        .aside-menu {
            list-style: none;
        }

        .aside-menu li {
            margin-bottom: 0.5rem;
        }

        .aside-menu a {
            display: block;
            padding: 0.8rem 1.5rem;
            color: var(--dark);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .aside-menu a:hover,
        .aside-menu a.active {
            background-color: rgba(10, 36, 99, 0.05);
            border-left: 3px solid var(--primary);
            color: var(--primary);
        }

        main {
            grid-area: main;
            padding: 2rem;
        }

        .content-section {
            display: none;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.3s ease-in-out;
        }

        .content-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1,
        h2 {
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        h1 {
            font-size: 1.5rem;
        }

        h2 {
            font-size: 1.3rem;
        }

        .info-group {
            margin-bottom: 1.2rem;
            padding: 1rem;
            background-color: var(--light);
            border-radius: 4px;
        }

        .info-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }

        .info-value {
            font-size: 1.1rem;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            margin-top: 0.3rem;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .section-title {
            font-size: 1.2rem;
            color: var(--primary);
            margin: 1.5rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .horario-container {
            display: grid;
            gap: 1rem;
        }

        .dia-horario {
            display: grid;
            grid-template-columns: 100px 1fr;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem;
            background-color: var(--light);
            border-radius: 4px;
        }

        .horario-value {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cerrado-badge {
            padding: 0.3rem 0.8rem;
            background-color: var(--danger);
            color: white;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .cerrado-badge.abierto {
            background-color: var(--success);
        }

        .Trabajador {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 1rem;
            max-width: 300px;
            background-color: var(--light);
        }

        #sugerencias {
            min-height: 120px;
            margin-top: 1rem;
            width: 100%;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn-submit {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 1.5rem;
        }

        .btn-submit:hover {
            background-color: #0b2b7a;
        }


        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .checkbox-container input[type="checkbox"] {
            width: auto;
        }

        .time-inputs {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .time-inputs input {
            width: 100px;
        }

        @media (max-width: 768px) {
            body {
                grid-template-areas:
                    "header"
                    "main";
                grid-template-columns: 1fr;
            }

            aside {
                display: none;
            }

            nav ul {
                flex-wrap: wrap;
                gap: 1rem;
            }

            .grid-2 {
                grid-template-columns: 1fr;
            }

            .dia-horario {
                grid-template-columns: 1fr;
            }
        }

        .trabajadores-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.Trabajador {
    background-color: var(--light);
    padding: 1rem;
    border-radius: 6px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.Trabajador:hover {
    transform: translateY(-5px);
}


    </style>
</head>

<body>

<!-- Modal para registrar nuevo empleado -->
<div id="modalEmpleado" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background: white; margin: 3% auto; padding: 20px; border-radius: 8px; width: 50%;">
        <span onclick="closeEmpleadoModal()" style="float: right; cursor: pointer; font-weight: bold;">&times;</span>
        <h3>Agregar Empleado</h3>
        <form id="formEmpleado" method="post" action="guardar_empleado.php">
            <input type="hidden" name="id_negocio" value="<?= $idNegocio ?>">
            <label>Nombre:
                <input type="text" name="nombre" required style="width: 100%; margin-bottom: 10px;">
            </label>
            <label>Apellido Paterno:
                <input type="text" name="apepaterno" required style="width: 100%; margin-bottom: 10px;">
            </label>
            <label>Apellido Materno:
                <input type="text" name="apematerno" style="width: 100%; margin-bottom: 10px;">
            </label>
            <label>Teléfono:
                <input type="text" name="numtelefono" style="width: 100%; margin-bottom: 10px;">
            </label>
            <label>Email:
                <input type="email" name="email" style="width: 100%; margin-bottom: 10px;">
            </label>
            <button type="submit" style="background-color: var(--primary); color: white; border: none; padding: 10px; width: 100%; border-radius: 4px; cursor: pointer;">
                Guardar
            </button>
        </form>
    </div>
</div>

    <header>
        <h1>TuInventarioYa</h1>
        <nav>
            <ul>
                <li><a href="Dashboard.php">Dashboard</a></li>
                <li><a href="CorteCaja.php">Corte Caja</a></li>
                <li><a href="Inventario.php">Inventario</a></li>
                <!--<li><a href="Clientes.php">Clientes</a></li>
                <li><a href="Catalogo.php">Catalogo</a></li>-->
            </ul>
        </nav>
    </header>

    <aside>
        <ul class="aside-menu">
            <li><a href="#" class="active" data-section="negocio">Datos del Negocio</a></li>
            <li><a href="#" data-section="trabajadores">Trabajadores</a></li>
            <li><a href="#" data-section="sugerencias">Cajón de Sugerencias</a></li>
            <li><a href="#" data-section="suscripcion">Suscripción</a></li>
        </ul>
    </aside>

    <main>
        <form method="POST" action="">
            <section id="negocio" class="content-section active">
                <h1>Datos del Negocio</h1>

                <div class="grid-2">
                    <div class="info-group">
                        <div class="info-label">Nombre del Negocio</div>
                        <input type="text" name="nombre" value="<?= htmlspecialchars($DatosArrarNegocio['nombre']) ?>" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="info-group">
                        <div class="info-label">Código Postal</div>
                        <input type="text" name="cp" value="<?= htmlspecialchars($DatosArrarNegocio['cp']) ?>" required>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Teléfono</div>
                        <input type="text" name="telefono" value="<?= htmlspecialchars($DatosArrarNegocio['numtelefono']) ?>" required>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">Dirección</div>
                    <textarea name="direccion"><?= htmlspecialchars($DatosArrarNegocio['ubicacion']) ?></textarea>
                </div>

                <div class="info-group">
                    <div class="info-label">Correo Electrónico</div>
                    <input type="email" name="email" value="<?= htmlspecialchars($DatosArrarNegocio['email']) ?>">
                </div>

                <div class="grid-2">
                    <div class="info-group">
                        <div class="info-label">Tipo de Negocio</div>
                        <select name="tipo" required>
                            <?php foreach ($tiposNegocio as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $DatosArrarNegocio['tipo'] === $value ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Mostrar precios al público</div>
                        <select name="precios_linea" required>
                            <option value="si" <?= $DatosArrarNegocio['precioslinea'] === 'si' ? 'selected' : '' ?>>Sí</option>
                            <option value="no" <?= $DatosArrarNegocio['precioslinea'] === 'no' ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                </div>

                <div class="section-title">Horario de Atención</div>

                <div class="horario-container">
                    <?php
                    $dias = [
                        'lunes' => 'Lunes',
                        'martes' => 'Martes',
                        'miercoles' => 'Miércoles',
                        'jueves' => 'Jueves',
                        'viernes' => 'Viernes',
                        'sabado' => 'Sábado',
                        'domingo' => 'Domingo'
                    ];
 foreach ($dias as $key => $dia): 
    // Verificar si el día está cerrado
    $cerrado = isset($horarios[$key]) && ($horarios[$key]['inicio'] == 'Cerrado' || $horarios[$key]['fin'] == 'Cerrado');
?>
<div class="dia-horario">
    <label><?= $dia ?></label>
    <div class="horario-value">
        <!-- Checkbox Cerrado -->
        <div class="checkbox-container">
            <input type="checkbox" name="cerrado[<?= $key ?>]" id="cerrado_<?= $key ?>" <?= $cerrado ? 'checked' : '' ?> onchange="toggleHorario(this, '<?= $key ?>')">
            <label for="cerrado_<?= $key ?>">Cerrado</label>
        </div>

        <!-- Campos de horario -->
        <div class="time-inputs">
            <input type="text" name="horario[<?= $key ?>][inicio]" id="<?= $key ?>_inicio"
                   value="<?= !$cerrado ? htmlspecialchars($horarios[$key]['inicio']) : '' ?>"
                   <?= $cerrado ? 'disabled' : '' ?> placeholder="09:00">
            <span>a</span>
            <input type="text" name="horario[<?= $key ?>][fin]" id="<?= $key ?>_fin"
                   value="<?= !$cerrado ? htmlspecialchars($horarios[$key]['fin']) : '' ?>"
                   <?= $cerrado ? 'disabled' : '' ?> placeholder="18:00">
        </div>
    </div>
</div>
<?php endforeach; ?>
                </div>

                <button type="submit" class="btn-submit">Guardar Cambios</button>
            </section>
        </form>

<section id="trabajadores" class="content-section">
    <h1>Trabajadores</h1>

    <!-- Botón para agregar nuevo empleado -->
    <button onclick="openEmpleadoModal()" style="margin-bottom: 1rem; padding: 0.5rem 1rem; background-color: var(--primary); color: white; border: none; border-radius: 4px; cursor: pointer;">+</button>

    <!-- Lista dinámica de empleados -->
    <div class="trabajadores-grid" id="empleadosList">
        <?php if (!empty($empleados)): ?>
            <?php foreach ($empleados as $empleado): ?>
                <div class="Trabajador" data-id="<?= htmlspecialchars($empleado['idvendedor']) ?>">
                    <h2><?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apepaterno'] . ' ' . $empleado['apematerno']) ?></h2>
                    <p><strong>Cargo:</strong> <?= htmlspecialchars($empleado['rol']) ?></p>
                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($empleado['numtelefono']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($empleado['email']) ?></p>
                    <p><strong>Clave:</strong> <?= htmlspecialchars($empleado['clave']) ?></p>
                    <button onclick="eliminarEmpleado(<?= $empleado['idvendedor'] ?>)" style="background-color: var(--danger); color: white; border: none; border-radius: 3px; padding: 5px 10px; cursor: pointer;">
                        Eliminar
                    </button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay empleados registrados aún.</p>
        <?php endif; ?>
    </div>
</section>

        <section id="sugerencias" class="content-section">
            <h1>Cajón de Sugerencias</h1>
            <p>Tiene una idea de mejora o tienes un problema? Déjanos un mensaje:</p>
            <textarea name="sugerencias" id="sugerencias" placeholder="Escribe tus sugerencias aquí..."></textarea>
            <button class="btn-submit">Enviar Sugerencia</button>
        </section>

        <section id="suscripcion" class="content-section">
            <div id="yes">
                <h1>Suscripción</h1>
                <p>Ya formas parte de nuestra comunidad premium</p>
                <p><strong>Tu suscripción se agota el:</strong> <?php $Fecha = str_replace("-", "/", $DatosArrarNegocio['finsuscripcion']);
                                                                print_r($Fecha); ?></p>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Función para cambiar entre secciones
            const menuLinks = document.querySelectorAll('.aside-menu a');

            menuLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Remover clase active de todos los links
                    menuLinks.forEach(item => item.classList.remove('active'));

                    // Agregar clase active al link clickeado
                    this.classList.add('active');

                    // Ocultar todas las secciones
                    document.querySelectorAll('.content-section').forEach(section => {
                        section.classList.remove('active');
                    });

                    // Mostrar la sección correspondiente
                    const sectionId = this.getAttribute('data-section');
                    document.getElementById(sectionId).classList.add('active');
                });
            });

        });
    </script>

    <script>
        function toggleHorario(checkbox, dia) {
            const inicio = document.getElementById(dia + '_inicio');
            const fin = document.getElementById(dia + '_fin');

            inicio.disabled = checkbox.checked;
            fin.disabled = checkbox.checked;

            if (checkbox.checked) {
                inicio.value = '';
                fin.value = '';
            }
        }
    </script>

    <script>
function openEmpleadoModal() {
    document.getElementById("modalEmpleado").style.display = "block";
}

function closeEmpleadoModal() {
    document.getElementById("modalEmpleado").style.display = "none";
    document.getElementById("formEmpleado").reset();
}

function eliminarEmpleado(idVendedor) {
    if (confirm("¿Estás seguro de eliminar a este empleado?")) {
        fetch('eliminar_empleado.php?id=' + idVendedor, { method: 'GET' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Recargar página
                } else {
                    alert("Error al eliminar: " + data.error);
                }
            });
    }
}
</script>
</body>

</html>