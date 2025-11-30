<?php
session_name("PARTNERS_SESION");
session_start();

// ----------------------------------------------------
// --- 1. Inicialización de Variables y Conexión ---
// ----------------------------------------------------
$conn = null;
$talento_data = null;
$error_msg = null;

$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";

$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// 2. Verificar sesión de agencia
if (!isset($_SESSION["email_agencia"])) {
    mysqli_close($conn);
    header("Location: login.php");
    exit();
}

// Obtener datos de la agencia (para el navbar)
$email_agencia = $_SESSION["email_agencia"];
$nombre_agencia = "";
$nombre_contacto = "";

$query_agencia = "SELECT nombre_agencia, nombre_contacto FROM agencias WHERE email_agencia = ?";
$stmt_agencia = mysqli_prepare($conn, $query_agencia);
if ($stmt_agencia) {
    mysqli_stmt_bind_param($stmt_agencia, "s", $email_agencia);
    mysqli_stmt_execute($stmt_agencia);
    $result_agencia = mysqli_stmt_get_result($stmt_agencia);
    if ($result_agencia && $data = mysqli_fetch_assoc($result_agencia)) {
        $nombre_agencia = $data["nombre_agencia"];
        $nombre_contacto = $data["nombre_contacto"];
    }
    mysqli_stmt_close($stmt_agencia);
}


// 3. Obtener ID del talento de la URL
$id_talento = isset($_GET['id_talento']) ? (int)$_GET['id_talento'] : 0;

if ($id_talento === 0) {
    $error_msg = "ID de talento no proporcionado o inválido.";
} else {
    // 4. Consulta para obtener el perfil completo del talento y su CV
    $query_talento = "
        SELECT 
            t.nombre, t.apellido, t.email, t.numero_telefono, t.fecha_nacimiento,
            c.biografia, c.educacion, c.experiencia, c.habilidades, c.ciudad
        FROM talentos t
        LEFT JOIN curriculums c ON t.id_talento = c.usuario_id
        WHERE t.id_talento = ?
    ";

    $stmt_talento = mysqli_prepare($conn, $query_talento);
    if ($stmt_talento) {
        mysqli_stmt_bind_param($stmt_talento, "i", $id_talento);
        mysqli_stmt_execute($stmt_talento);
        $result_talento = mysqli_stmt_get_result($stmt_talento);

        if ($result_talento && mysqli_num_rows($result_talento) > 0) {
            $talento_data = mysqli_fetch_assoc($result_talento);
        } else {
            $error_msg = "No se encontró el perfil del talento o su currículum.";
        }
        mysqli_stmt_close($stmt_talento);
    } else {
        $error_msg = "Error al preparar la consulta de talento: " . mysqli_error($conn);
    }
}

// Función auxiliar para calcular la edad
function calcularEdad($fecha_nacimiento) {
    if (!$fecha_nacimiento) return 'N/A';
    $nacimiento = new DateTime($fecha_nacimiento);
    $ahora = new DateTime();
    $edad = $ahora->diff($nacimiento);
    return $edad->y;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Perfil de Talento - TalenteAR Partners</title>

    <link rel="stylesheet" type="text/css" href="../css/home-empresas.css"/> 
    <link rel="stylesheet" type="text/css" href="../css/sticky.css"/>
    <link rel="stylesheet" type="text/css" href="../css/login.css"/>
    <link rel="stylesheet" type="text/css" href="../css/postulaciones.css"/>
    
    <style>
        /* Estilos específicos para la vista de perfil */
        .perfil-header {
            text-align: center;
            padding: 20px;
            background-color: #5d2b77;
            color: white;
            border-radius: 8px 8px 0 0;
            margin-bottom: 20px;
        }
        .perfil-header h1 {
            margin: 0;
            font-size: 2.5em;
        }
        .perfil-body {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .info-contact, .seccion-cv {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .info-contact {
            flex: 1;
            min-width: 280px;
            height: fit-content;
        }
        .seccion-cv {
            flex: 2;
            min-width: 400px;
        }
        .seccion-cv h2 {
            border-bottom: 2px solid #5d2b77;
            padding-bottom: 5px;
            color: #5d2b77;
            font-size: 1.4em;
            margin-top: 20px;
        }
        .seccion-cv p, .info-contact p {
            line-height: 1.6;
        }
        .seccion-cv pre {
            white-space: pre-wrap;
            background-color: #fff;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #eee;
        }
    </style>
</head>
<body class="body-postulaciones">

<div class="navbar">
    <div class="navbar-title">
        <div class="imagenesflechas">
            <a href="postulaciones.php" class="flecha-link">
                <img class="flecha" src="../../imagenes/flecha.png" alt="volver atrás" />
                <img class="flecha-hover" src="../../imagenes/flecharellena.png" alt="volver atrás" />
            </a>
        </div>
        <a href="index-logged.php" class="navbar-title">
            <img class="maleta" src="../../imagenes/logotalentear.png" alt="TalenteAR" />
            <h2 class="title">TalenteAR Partners</h2>
        </a>
    </div>

    <div class="navbar-links">
        <a href="../cerrar_sesion.php" class="box redirect">Cerrar sesión</a>
        <div class="profile-menu">
             <button class="profile-button" onclick="toggleProfileMenu()" style="background:none;">
                <img src="../../imagenes/profilelogo.png" alt="Perfil" class="profile-logo" />
            </button>
            <div id="profileDropdown" class="dropdown-content">
                <?php echo "<p style='text-align:center'>" . htmlspecialchars($nombre_contacto) . " (" . htmlspecialchars($nombre_agencia) . ")</p>"; ?>
                <hr>
                <a href="perfil_agencia.php">Mi Perfil</a>
                <a href="configuracion_agencia.php">Configuración</a>
                <a href="../cerrar_sesion.php">Cerrar Sesión</a>
            </div>
        </div>
    </div>
</div>
<div class="container-body" style="max-width: 1000px; margin-top: 80px;">
    
    <?php if ($error_msg): ?>
        <p class='msg-error' style="text-align: center;"><?= htmlspecialchars($error_msg) ?></p>
        <div style="text-align: center; margin-top: 20px;">
            <a href="postulaciones.php" class="btn-info" style="text-decoration: none;">Volver a Postulaciones</a>
        </div>
    <?php elseif ($talento_data): ?>

        <div class="perfil-header">
            <h1><?= htmlspecialchars($talento_data['nombre'] . ' ' . $talento_data['apellido']) ?></h1>
            <p>Perfil de Talento | Edad: <?= calcularEdad($talento_data['fecha_nacimiento']) ?> años</p>
        </div>

        <div class="perfil-body">
            
            <div class="info-contact">
                <h2>Información de Contacto</h2>
                <hr style="border-top: 1px solid #ccc;">
                <p><strong><span class="icon">📧</span> Email:</strong> <?= htmlspecialchars($talento_data['email']) ?></p>
                <p><strong><span class="icon">📞</span> Teléfono:</strong> <?= htmlspecialchars($talento_data['numero_telefono'] ?: 'No especificado') ?></p>
                <p><strong><span class="icon">📍</span> Ciudad:</strong> <?= htmlspecialchars($talento_data['ciudad'] ?: 'No especificada') ?></p>
                
                <h2 style="margin-top: 30px;">Resumen</h2>
                <hr style="border-top: 1px solid #ccc;">
                <p><?= nl2br(htmlspecialchars($talento_data['biografia'] ?: 'El talento no ha escrito una biografía.')) ?></p>
            </div>

            <div class="seccion-cv">
                
                <h2>Educación y Formación</h2>
                <pre><?= htmlspecialchars($talento_data['educacion'] ?: 'Información no proporcionada.') ?></pre>

                <h2>Experiencia Profesional</h2>
                <pre><?= htmlspecialchars($talento_data['experiencia'] ?: 'Información no proporcionada.') ?></pre>

                <h2>Habilidades Clave</h2>
                <pre><?= htmlspecialchars($talento_data['habilidades'] ?: 'Información no proporcionada.') ?></pre>

                <div style="text-align: right; margin-top: 30px;">
                    <a href="postulaciones.php" class="btn-rech" style="text-decoration: none;">Volver a la Gestión</a>
                </div>
            </div>

        </div>

    <?php endif; ?>

</div>

<footer class="footer">
    </footer>

<script>
    function toggleProfileMenu() {
        const dropdown = document.getElementById('profileDropdown');
        dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';
    }
</script>

<?php
if ($conn) {
    mysqli_close($conn); 
}
?>
</body>
</html>