<?php
include '../include/conn.php';

session_start();
$idNegocio = $_SESSION['idNegocio'];

$Consulta = "SELECT * FROM negocios WHERE id_Negocio = $idNegocio";
$query = mysqli_query($conn, $Consulta);
$Resul = mysqli_num_rows($query);

if ($Resul === 1) {
    $DatosArrarNegocio = mysqli_fetch_assoc($query);
    
    // Decodificar el JSON de horarios
    $horarios = json_decode($DatosArrarNegocio['Horarios'], true);
    
    // Mapear tipos de negocio a nombres legibles
    $tiposNegocio = [
        'ferreteria' => 'Ferretería',
        'electronica' => 'Electrónica',
        'telefonos_computadoras' => 'Teléfonos/Computadoras',
        'ropa' => 'Ropa',
        'supermercado' => 'Supermercado',
        'restaurante' => 'Restaurante',
        'otro' => 'Otro'
    ];
    
    // Mapear opción de precios a texto legible
    $preciosPublico = [
        'si' => 'Sí',
        'no' => 'No'
    ];
}

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $cp = mysqli_real_escape_string($conn, $_POST['cp']);
    $telefono = mysqli_real_escape_string($conn, $_POST['telefono']);
    $direccion = mysqli_real_escape_string($conn, $_POST['direccion']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $tipo = mysqli_real_escape_string($conn, $_POST['tipo']);
    $preciosLinea = mysqli_real_escape_string($conn, $_POST['precios_linea']);
    
    // Procesar horarios
    $nuevosHorarios = [];
    foreach ($_POST['horario'] as $dia => $horario) {
        $nuevosHorarios[$dia] = [
            'inicio' => $horario['inicio'],
            'fin' => $horario['fin']
        ];
    }
    $horariosJson = json_encode($nuevosHorarios);
    
    // Actualizar en la base de datos
    $updateQuery = "UPDATE negocios SET 
                    Nombre = '$nombre',
                    CP = '$cp',
                    NumTelefono = '$telefono',
                    Ubicacion = '$direccion',
                    Email = '$email',
                    Tipo = '$tipo',
                    PreciosLinea = '$preciosLinea',
                    Horarios = '$horariosJson'
                    WHERE id_Negocio = $idNegocio";
    
    if (mysqli_query($conn, $updateQuery)) {
        // Actualizar los datos mostrados
        $DatosArrarNegocio = array_merge($DatosArrarNegocio, [
            'Nombre' => $nombre,
            'CP' => $cp,
            'NumTelefono' => $telefono,
            'Ubicacion' => $direccion,
            'Email' => $email,
            'Tipo' => $tipo,
            'PreciosLinea' => $preciosLinea
        ]);
        $horarios = $nuevosHorarios;
        
        echo "<script>alert('Datos actualizados correctamente');</script>";
    } else {
        echo "<script>alert('Error al actualizar los datos');</script>";
    }
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

        .aside-menu a:hover, .aside-menu a.active {
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
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1, h2 {
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

        input, select, textarea {
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

        .Trabajador img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--gray);
            margin-bottom: 0.5rem;
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

        .trabajadores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
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
    </style>
</head>
<body>
    <header>
        <h1>TuInventarioYa</h1>
        <nav>
            <ul>
                <li><a href="Dashboard.php">Dashboard</a></li>
                <li><a href="CorteCaja.php">Corte Caja</a></li>
                <li><a href="Inventario.php">Inventario</a></li>
                <li><a href="AgregarProducto.php">Agregar Producto</a></li>
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
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($DatosArrarNegocio['Nombre']); ?>" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="info-group">
                        <div class="info-label">Código Postal</div>
                        <input type="text" name="cp" value="<?php echo htmlspecialchars($DatosArrarNegocio['CP']); ?>" required>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Teléfono</div>
                        <input type="tel" name="telefono" value="<?php echo htmlspecialchars($DatosArrarNegocio['NumTelefono']); ?>" required>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">Dirección</div>
                    <textarea name="direccion" required><?php echo htmlspecialchars($DatosArrarNegocio['Ubicacion']); ?></textarea>
                </div>

                <div class="info-group">
                    <div class="info-label">Correo Electrónico</div>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($DatosArrarNegocio['Email']); ?>" required>
                </div>

                <div class="grid-2">
                    <div class="info-group">
                        <div class="info-label">Tipo de Negocio</div>
                        <select name="tipo" required>
                            <?php foreach ($tiposNegocio as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo ($DatosArrarNegocio['Tipo'] == $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Mostrar precios al público</div>
                        <select name="precios_linea" required>
                            <option value="si" <?php echo ($DatosArrarNegocio['PreciosLinea'] == 'si') ? 'selected' : ''; ?>>Sí</option>
                            <option value="no" <?php echo ($DatosArrarNegocio['PreciosLinea'] == 'no') ? 'selected' : ''; ?>>No</option>
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
                        $cerrado = ($horarios[$key]['inicio'] == 'Cerrado' || $horarios[$key]['fin'] == 'Cerrado');
                    ?>
                    <div class="dia-horario">
                        <label><?php echo $dia; ?></label>
                        <div class="horario-value">
                            <div class="checkbox-container">
                                <input type="checkbox" id="cerrado_<?php echo $key; ?>" class="cerrado-checkbox" <?php echo $cerrado ? 'checked' : ''; ?>>
                                <label for="cerrado_<?php echo $key; ?>">Cerrado</label>
                            </div>
                            <div class="time-inputs">
                                <input type="time" name="horario[<?php echo $key; ?>][inicio]" 
                                       value="<?php echo !$cerrado ? htmlspecialchars($horarios[$key]['inicio']) : ''; ?>" 
                                       <?php echo $cerrado ? 'disabled' : ''; ?>>
                                <span>a</span>
                                <input type="time" name="horario[<?php echo $key; ?>][fin]" 
                                       value="<?php echo !$cerrado ? htmlspecialchars($horarios[$key]['fin']) : ''; ?>" 
                                       <?php echo $cerrado ? 'disabled' : ''; ?>>
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
            <div class="trabajadores-grid">
                <div class="Trabajador">
                    <img src="" alt="Foto del trabajador">
                    <h2>Juan Pérez</h2>
                    <p><strong>Cargo:</strong> Gerente</p>
                    <p><strong>Horario:</strong> L-V 9am-6pm</p>
                    <p><strong>Teléfono:</strong> +52 55 1234 5678</p>
                </div>
                
                <div class="Trabajador">
                    <img src="" alt="Foto del trabajador">
                    <h2>María García</h2>
                    <p><strong>Cargo:</strong> Vendedora</p>
                    <p><strong>Horario:</strong> M-S 10am-7pm</p>
                    <p><strong>Teléfono:</strong> +52 55 8765 4321</p>
                </div>
                
                <div class="Trabajador">
                    <img src="" alt="Foto del trabajador">
                    <h2>Carlos López</h2>
                    <p><strong>Cargo:</strong> Almacenista</p>
                    <p><strong>Horario:</strong> L-S 8am-5pm</p>
                    <p><strong>Teléfono:</strong> +52 55 5555 5555</p>
                </div>
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
                <p><strong>Tu suscripción se agota el:</strong> <?php $Fecha = str_replace("-", "/", $DatosArrarNegocio['FinSuscripcion']); print_r($Fecha); ?></p>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
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

            // Manejar checkboxes de horarios
            document.querySelectorAll('.cerrado-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const timeInputs = this.closest('.horario-value').querySelectorAll('input[type="time"]');
                    timeInputs.forEach(input => {
                        input.disabled = this.checked;
                        if (this.checked) {
                            input.value = '';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>