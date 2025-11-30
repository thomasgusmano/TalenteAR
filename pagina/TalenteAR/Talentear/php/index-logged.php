<?php
session_name("TALENTEAR_SESION");
session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    // Es mejor manejar el error de conexión de manera directa
    error_log("Error al conectar a la base de datos: " . mysqli_connect_error());
    header("Location: error.php?code=500");
    exit();
}

// Validar sesión por email
if (!isset($_SESSION["email"])) {
    header("Location: error.php?code=401");
    exit();
}
$email = $_SESSION["email"];

// Obtener id_talento, nombre, estado premium y contador de postulaciones
$query = "SELECT id_talento, nombre, es_premium, postulaciones_mes FROM talentos WHERE email = ?";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $nombre = "";
    $id_talento = 0;
    $es_premium = false;
    $postulaciones_mes = 0;
    $LIMITE_GRATUITO = 5; // Límite de postulación gratuito

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $nombre = $row["nombre"];
        $id_talento = (int)$row["id_talento"];
        $es_premium = (bool)$row['es_premium'];
        $postulaciones_mes = (int)$row['postulaciones_mes']; 
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        header("Location: login.php");
        exit();
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Error en la consulta de datos del talento: " . mysqli_error($conn));
    header("Location: error.php?code=500");
    exit();
}

$limite_alcanzado = (!$es_premium && $postulaciones_mes >= $LIMITE_GRATUITO);


// Obtener el ÚNICO CV del usuario y sus datos
$query_cv = "SELECT curriculum_id, biografia, educacion, ciudad, experiencia, habilidades 
             FROM curriculums WHERE usuario_id = $id_talento LIMIT 1";
$result_cv = mysqli_query($conn, $query_cv);
$curriculum_data = [
    'biografia' => '', 'educacion' => '', 'ciudad' => '', 
    'experiencia' => '', 'habilidades' => ''
];
$curriculum_id_usuario = 0; // 0 si no tiene CV
$cv_tiene_contenido = false; // Nueva variable para la lógica de la vista

if ($result_cv && mysqli_num_rows($result_cv) > 0) {
    $curriculum_data = mysqli_fetch_assoc($result_cv);
    $curriculum_id_usuario = (int) $curriculum_data['curriculum_id'];

    // LÓGICA CORREGIDA: Determinar si el CV existe Y TIENE DATOS NO VACÍOS
    if (
        !empty(trim($curriculum_data['biografia'])) || 
        !empty(trim($curriculum_data['educacion'])) || 
        !empty(trim($curriculum_data['ciudad'])) || 
        !empty(trim($curriculum_data['experiencia'])) ||
        !empty(trim($curriculum_data['habilidades']))
    ) {
        $cv_tiene_contenido = true;
    }
}

// MANEJO DE ELIMINACIÓN DE CV
$delete_error = null;
$delete_success = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_curriculum"]) && $curriculum_id_usuario > 0) {
    // 1. Eliminar notificaciones asociadas primero (por Foreign Key)
    // Se usa sentencia preparada para mayor seguridad
    $delete_notif_query = "DELETE FROM notificaciones WHERE curriculum_id = ?";
    $stmt_notif = mysqli_prepare($conn, $delete_notif_query);
    mysqli_stmt_bind_param($stmt_notif, "i", $curriculum_id_usuario);
    
    if (!mysqli_stmt_execute($stmt_notif)) {
        $delete_error = "Error al eliminar notificaciones: " . mysqli_error($conn);
    } else {
        // 2. Eliminar el CV
        $delete_query = "DELETE FROM curriculums WHERE curriculum_id = ? LIMIT 1";
        $stmt_cv = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt_cv, "i", $curriculum_id_usuario);
        
        if (mysqli_stmt_execute($stmt_cv)) {
            $delete_success = "Currículum eliminado correctamente.";
            // Redirigir para limpiar el POST y actualizar el estado del CV
            header("Location: index-logged.php?status=deleted");
            exit();
        } else {
            $delete_error = "Error al eliminar el currículum: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt_cv);
    }
    mysqli_stmt_close($stmt_notif);
}


// Obtener ofertas de Casting
$queryOffers = "SELECT o.casting_id, o.agencia_id, o.NombrePuesto, o.DescripcionPuesto, o.CantVacantes, o.NivelExperiencia, d.nombre_agencia 
                 FROM castings o 
                 INNER JOIN agencias d ON o.agencia_id = d.id_agencia
                 ORDER BY o.casting_id DESC"; // Ordenar por ID para ver los nuevos primero
$resultOffers = mysqli_query($conn, $queryOffers);

// ---------- NOTIFICACIONES ----------
$notifCountQuery = "
    SELECT COUNT(*) AS total
    FROM notificaciones n
    JOIN curriculums c ON n.curriculum_id = c.curriculum_id
    WHERE c.usuario_id = $id_talento 
    AND n.leida = 0
    AND n.estado IN ('aceptada', 'rechazada', 'invitacion') 
";
$notifCountResult = mysqli_query($conn, $notifCountQuery);
$notifCount = 0;
if ($notifCountResult && mysqli_num_rows($notifCountResult) > 0) {
    $notifCount = (int) mysqli_fetch_assoc($notifCountResult)['total'];
}

$notifQuery = "
    SELECT n.notificacion_id, n.fecha, n.leida, n.estado, e.nombre_agencia, e.email_agencia, n.casting_id
    FROM notificaciones n
    JOIN curriculums c ON n.curriculum_id = c.curriculum_id
    JOIN agencias e ON n.agencia_id = e.id_agencia
    WHERE c.usuario_id = $id_talento
    AND n.estado IN ('aceptada', 'rechazada', 'invitacion') 
    ORDER BY n.fecha DESC
";
$notifResult = mysqli_query($conn, $notifQuery);
$notifError = $notifResult === false ? mysqli_error($conn) : null;
// ------------------------------------


// LÓGICA: MANEJO DEL MENSAJE DE ESTADO (CV, Suscripción, Límite)
$status_message = '';
$status_class = '';

if (isset($_GET['status'])) { 
    if ($_GET['status'] === 'created') {
        $status_message = "¡Currículum creado con éxito! Ahora puedes postularte a ofertas.";
        $status_class = 'success';
    } elseif ($_GET['status'] === 'updated') {
        $status_message = "¡Currículum actualizado con éxito!";
        $status_class = 'success';
    } elseif ($_GET['status'] === 'deleted') {
        $status_message = "Currículum eliminado correctamente. Debes crear uno nuevo para postularte.";
        $status_class = 'success'; 
    } elseif ($_GET['status'] === 'premium_activated') { 
        $status_message = "🚀 ¡Felicidades! Has activado el **Plan Premium**. Ahora puedes postularte ILIMITADAMENTE.";
        $status_class = 'success';
    } elseif ($_GET['status'] === 'error') {
        $msg_code = isset($_GET['msg']) ? $_GET['msg'] : 'unknown_error';
        
        // MANEJO DE ERROR DEL LÍMITE
        if ($msg_code === 'limit_reached') {
             $status_message = "🚫 **Límite de postulación alcanzado.** Has usado **{$postulaciones_mes}** de tus **{$LIMITE_GRATUITO}** postulaciones gratuitas este mes. ¡Hazte Premium para postular ilimitadamente!";
        } else {
             $status_message = "Error al procesar el CV o la postulación. Código: $msg_code.";
        }
        
        $status_class = 'error';
    }
}
// FIN LÓGICA
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bienvenido</title>
    <link rel="stylesheet" type="text/css" href="../css/home-usuarios.css"/>
    <link rel="stylesheet" type="text/css" href="../css/sticky.css"/>
    <style>
     /* Estilos para el Tag PREMIUM en el Navbar */
     .premium-tag {
        background-color: #ffc107; /* Amarillo */
        color: #333;
        padding: 4px 8px;
        border-radius: 15px;
        font-weight: bold;
        font-size: 12px;
        margin-left: 10px;
        border: 1px solid #ff9800;
        vertical-align: middle;
        text-shadow: 0 1px 1px rgba(255, 255, 255, 0.5);
     }
     /* Contenedor Flex para los botones */
     .form-actions {
         display: flex; 
         gap: 10px; 
         margin-top: 20px;
         align-items: center; 
         width: 100%;
         justify-content: center; 
     }
     
     /* Estilos compartidos para botones */
     .boton-eliminar, .boton-publicar, .boton-editar {
         flex-grow: 1; 
         text-align: center; 
         box-sizing: border-box; 
         height: 40px; 
         font-size: 16px; 
         display: block; 
         border-radius: 5px;
         padding: 10px 15px;
         cursor: pointer;
         font-weight: bold;
         border: none;
         color: white;
     }

     .boton-editar {
         background-color: #3e8e41; /* Verde */
     }
     
     .boton-eliminar {
         background-color: #d9534f; /* Rojo */
     }

     input.boton-publicar {
         background-color: #4863a0; /* Azul */
         flex-grow: 1; 
     }
     
     /* Estilo para campos de solo lectura (modo visualización) */
     .DescPuesto[readonly], input[readonly] {
         background-color: #f5f5f5; /* Fondo gris para campos no editables */
         cursor: default;
     }

     /* ESTILO CORREGIDO: Mensaje de Alerta dentro del contenedor */
     .status-alert {
         padding: 15px;
         margin-top: 10px;
         margin-bottom: 20px;
         border-radius: 4px;
         font-weight: bold;
         text-align: center;
         width: 100%; 
         box-sizing: border-box;
     }
     .status-alert.success {
         background-color: #d4edda;
         color: #155724;
         border: 1px solid #c3e6cb;
     }
     .status-alert.error {
         background-color: #f8d7da;
         color: #721c24;
         border: 1px solid #f5c6cb;
     }
    </style>
    <script src="../js/profile-menu.js"></script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body>

<div class="navbar">
    <div class="navbar-title">
        <a href="../php/index-logged.php" class="navbar-title">
            <img class="maleta" src="../../imagenes/logotalentear.png" alt="Logo TalenteAR"/>
            <h2 class="title">TalenteAR</h2>
        </a>
        <?php if ($es_premium): ?>
            <span class="premium-tag">⭐ PREMIUM</span>
        <?php endif; ?>
    </div>
    <div class="navbar-links">
        
        <a href="solicitudes.php" class="box redirect">📬 Solicitudes</a>
        <a href="planes.php" class="box redirect" style="color: #ffc107; font-weight: bold;">Planes</a>
        <a href="cerrar_sesion.php" class="box redirect">Cerrar sesión</a>

        <div class="notif-container">
            <button class="notif-button" onclick="toggleNotificaciones()">
                <img src="../../imagenes/campanita.png" alt="Notificaciones" class="campanita">
                <?php if ($notifCount > 0): ?>
                    <span id="notifCount" class="numerito"><?= $notifCount ?></span>
                <?php endif; ?>
            </button>

            <div id="notifDropdown" class="notif-dropdown">
                <h4>Notificaciones</h4>
                <hr>
                <?php
                if ($notifError) {
                    echo "<p style='color:red; padding: 15px;'>Error cargando notificaciones: $notifError</p>";
                } else {
                    if ($notifResult && mysqli_num_rows($notifResult) > 0) {
                        while ($notif = mysqli_fetch_assoc($notifResult)) {
                            $estado = isset($notif['estado']) ? $notif['estado'] : 'pendiente';
                            $casting_id = $notif['casting_id'] ?? 0; // Se obtiene el casting_id
                            
                            // CORRECCIÓN CRÍTICA: Ocultar la notificación si el Talento ya la respondió (es una invitación)
                            if (($estado === 'aceptada' || $estado === 'rechazada') && ($casting_id === 0 || $casting_id === NULL)) {
                                continue; 
                            }
                            
                            echo "<a href='solicitudes.php' class='notificacion' style='text-decoration:none; color:inherit;'>"; 
                            
                            if ($estado === 'aceptada') {
                                echo "<p>¡Tu postulación fue **ACEPTADA** por **" . htmlspecialchars($notif['nombre_agencia']) . "**! 🎉</p>";
                                echo "<p class='notif-estado estado-aceptada'>Solicitud aceptada ✅</p>";
                            } elseif ($estado === 'rechazada') {
                                echo "<p>Tu postulación fue **RECHAZADA** por **" . htmlspecialchars($notif['nombre_agencia']) . "**.</p>";
                                echo "<p class='notif-estado estado-rechazada'>Solicitud rechazada ❌</p>";
                            } elseif ($estado === 'invitacion') { // NUEVO ESTADO
                                // Lógica para la invitación de la Agencia
                                echo "<p><strong>" . htmlspecialchars($notif['nombre_agencia']) . "</strong> te envió una **INVITACIÓN**.</p>";
                                echo "<p class='notif-estado estado-pendiente'>Nueva Invitación 📬</p>";
                                echo "<span class='boton-ver-notif'>Gestionar Solicitud</span>";
                            } 
                            
                            echo "<p class='notif-fecha'>" . htmlspecialchars($notif['fecha']) . "</p>";
                            echo "</a>";

                        }
                    } else {
                        echo "<p style='text-align:center; color:gray; padding: 15px;'>No tienes notificaciones.</p>";
                    }
                }
                ?>
            </div>
        </div>

        <div class="profile-menu">
            <span class="welcome-text-navbar"><?= htmlspecialchars($nombre) ?></span>
            <button class="profile-button" onclick="toggleProfileMenu()">
                <img src="../../imagenes/profilelogo.png" alt="Perfil" class="profile-logo" />
            </button>
            <div id="profileDropdown" class="dropdown-content">
                <p style='text-align:center'>¡Hola, <?= htmlspecialchars($nombre) ?>!</p>
                <hr>
                <a href="perfil.php">Mi Perfil</a>
                <a href="configuracion.php">Configuración</a>
                <a href="cerrar_sesion.php">Cerrar Sesión</a>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle notificaciones
function toggleNotificaciones() {
    const dropdown = document.getElementById('notifDropdown');
    const count = document.getElementById('notifCount');
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileDropdown) profileDropdown.style.display = 'none';
    dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';

    if (count && dropdown.style.display === 'block') {
        fetch('notisleidas.php', { method: 'POST' })
            .then(() => count.remove())
            .catch(err => console.error('Error al marcar notificaciones:', err));
    }
}

// Toggle perfil
function toggleProfileMenu() {
    const dropdown = document.getElementById('profileDropdown');
    const notifDropdown = document.getElementById('notifDropdown');
    if (notifDropdown) notifDropdown.style.display = 'none';
    dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
}

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', (event) => {
    const notifDropdown = document.getElementById('notifDropdown');
    const notifButton = document.querySelector('.notif-button');
    if (notifDropdown && notifDropdown.style.display === 'block' && !notifButton.contains(event.target) && !notifDropdown.contains(event.target)) {
        notifDropdown.style.display = 'none';
    }
    
    const profileDropdown = document.getElementById('profileDropdown');
    const profileButton = document.querySelector('.profile-button');
    if (profileDropdown && profileDropdown.style.display === 'block' && !profileButton.contains(event.target) && !profileDropdown.contains(event.target)) {
        profileDropdown.style.display = 'none';
    }
});

// Formularios de postulación
document.querySelectorAll('.hideform').forEach(form => {
    form.addEventListener('submit', function(event) {
        const id = this.id.replace('form','');
        const enviarBtn = document.getElementById('enviarBtn' + id);
        if (enviarBtn && !enviarBtn.disabled) { 
            enviarBtn.style.backgroundColor = '#4CAF50';
            enviarBtn.value = '¡Te has postulado!';
            enviarBtn.disabled = true;
        }
    });
});

// JavaScript para alternar el modo de edición (NUEVA LÓGICA)
function toggleEdit() {
    const form = document.getElementById('curriculumForm');
    // Seleccionamos todos los campos que pueden ser editados
    const inputs = form.querySelectorAll('input[type="text"], textarea');
    const editButton = document.getElementById('editButton');
    const saveDeleteActions = document.getElementById('saveDeleteActions');
    
    // 1. Alternar los campos entre read-only y editable
    inputs.forEach(input => {
        input.removeAttribute('readonly'); 
        input.style.backgroundColor = 'white'; 
    });

    // 2. Alternar la visibilidad de los botones
    editButton.style.display = 'none';
    saveDeleteActions.style.display = 'flex'; 
}
</script>

<div class="container">
    
    <div class="form-container">
        
        <?php if (!empty($status_message)): ?>
            <div class="status-alert <?= $status_class ?>">
                <?= $status_message ?>
                
                <?php if (isset($_GET['status']) && $_GET['status'] === 'error' && isset($_GET['msg']) && $_GET['msg'] === 'limit_reached'): ?>
                    <br><a href="planes.php" style="color: inherit; text-decoration: underline; font-weight: bold;">¡Suscríbete aquí para postular sin límites!</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form action="save_curriculum.php" method="POST" id="curriculumForm">
            <h3><?= $cv_tiene_contenido ? 'Gestión de' : 'Crear' ?> Currículum Vitae</h3>
            
            <?php if (!$cv_tiene_contenido): ?>
                <p style="color: #4863a0; font-weight: bold;">Debes completar tu CV para poder postularte a ofertas.</p>
            <?php endif; ?>
            
            <?php if ($delete_error): ?>
                <p style="color: red; font-weight: bold;"><?= $delete_error ?></p>
            <?php endif; ?>

            <?php $readonly_attr = ($cv_tiene_contenido) ? 'readonly' : ''; ?>

            <label for="biografia">Biografía:</label><br>
            <input type="text" id="biografia" name="biografia" class="DescPuesto" required value="<?= htmlspecialchars($curriculum_data['biografia']) ?>" <?= $readonly_attr ?>><br><br>

            <label for="educacion">Educación:</label><br>
            <textarea id="educacion" name="educacion" class="DescPuesto" required <?= $readonly_attr ?>><?= htmlspecialchars($curriculum_data['educacion']) ?></textarea><br><br>

            <label for="habilidades">Habilidades (separadas por comas, ej: PHP, SQL, HTML):</label><br>
            <textarea id="habilidades" name="habilidades" class="DescPuesto" required <?= $readonly_attr ?>><?= htmlspecialchars($curriculum_data['habilidades']) ?></textarea><br><br>

            <label for="ciudad">Ciudad:</label><br>
            <input type="text" id="ciudad" name="ciudad" required value="<?= htmlspecialchars($curriculum_data['ciudad']) ?>" <?= $readonly_attr ?>><br><br>

            <label for="experiencia">Experiencia:</label><br>
            <input type="text" id="experiencia" name="experiencia" class="DescPuesto" required value="<?= htmlspecialchars($curriculum_data['experiencia']) ?>" <?= $readonly_attr ?>><br><br>

            <div class="form-actions">
                <?php if ($cv_tiene_contenido): ?>
                    <button type="button" id="editButton" class="boton-editar" onclick="toggleEdit()">Editar CV</button>

                    <div id="saveDeleteActions" style="display: none; width: 100%; gap: 10px;">
                        <input type="submit" name="submit_curriculum" value="Guardar Cambios" class="boton-publicar">
                        <input type="submit" 
                                     form="delete-form" 
                                     value="Eliminar CV" 
                                     class="boton-eliminar"
                                     onclick="return confirm('¿Estás seguro de que quieres eliminar tu CV? Esta acción no se puede deshacer y borrará tus postulaciones asociadas.');">
                    </div>
                <?php else: ?>
                    <input type="submit" name="submit_curriculum" value="Publicar CV" class="boton-publicar">
                <?php endif; ?>
            </div>
        </form>
        
        <?php if ($curriculum_id_usuario > 0): ?>
            <form action="index-logged.php" method="POST" id="delete-form" style="display:none;">
                <input type="hidden" name="delete_curriculum" value="1">
            </form>
        <?php endif; ?>
    </div>

    <div class="castings-container">
        <h3 class="sticky-header">¡ENCUENTRA TU CASTING!</h3>
        
        <p style="padding: 15px; background-color: #f7f7f7; border-radius: 5px; text-align: center; border: 1px solid #ddd; font-weight: bold;">
            <?php if ($es_premium): ?>
                🎉 Eres usuario **PREMIUM**. Postulaciones ilimitadas.
            <?php else: ?>
                Estás en el plan Gratuito. Postulaciones usadas este mes: **<?= $postulaciones_mes ?>** / **<?= $LIMITE_GRATUITO ?>**. 
                <a href="planes.php" style="color: #4863a0; font-weight: bold; text-decoration: none;">¡Suscríbete aquí!</a>
            <?php endif; ?>
        </p>
        
        <?php
            if ($resultOffers) {
                while ($row = mysqli_fetch_assoc($resultOffers)) {
                    $casting_id_actual = $row['casting_id'];
                    $agencia_id_actual = $row['agencia_id'];

                    // --- Verificar si ya se postuló ---
                    $yaPostulado = false;
                    if ($curriculum_id_usuario > 0) { 
                        $checkQuery = "
                            SELECT 1 FROM notificaciones n 
                            WHERE n.curriculum_id = $curriculum_id_usuario 
                            AND n.agencia_id = $agencia_id_actual 
                            AND n.casting_id = $casting_id_actual LIMIT 1"; 

                        $checkResult = mysqli_query($conn, $checkQuery);
                        $yaPostulado = ($checkResult && mysqli_num_rows($checkResult) > 0);
                    }
                    // --- FIN MEJORA ---

                    echo "<div class='offer'>";
                    echo "<h2>" . htmlspecialchars($row['nombre_agencia']) . "</h2>";
                    echo "<p><strong>Nombre del puesto:</strong> " . htmlspecialchars($row['NombrePuesto']) . "</p>";
                    echo "<p><strong>Descripción:</strong> " . htmlspecialchars($row['DescripcionPuesto']) . "</p>";
                    echo "<p><strong>Cant de vacantes:</strong> " . htmlspecialchars($row['CantVacantes']) . "</p>";
                    echo "<p><strong>Nivel de experiencia requerido:</strong> " . htmlspecialchars($row['NivelExperiencia']) . "</p>";
                    
                    echo "<form id='form{$casting_id_actual}' action='enviar_solicitud.php' method='POST' class='hideform'>";
                    echo "<input type='hidden' name='casting_id' value='{$casting_id_actual}'>"; 

                    if ($yaPostulado) {
                        echo "<input type='button' value='Ya te postulaste' class='boton-solicitud' style='background-color: #4CAF50;' disabled>";
                    } else if (!$cv_tiene_contenido) {
                        // No permite postular si el CV está vacío (o no existe)
                        echo "<input type='button' value='Crea un CV para postular' class='boton-solicitud' style='background-color: #888;' disabled title='Debes crear un currículum primero'>";
                    } else if ($limite_alcanzado) {
                        // Lógica para usuarios GRATUITOS que alcanzan el límite
                        echo "<input type='button' value='Límite de postulaciones alcanzado' class='boton-solicitud' style='background-color: #f0ad4e;' disabled title='Has alcanzado el límite de {$LIMITE_GRATUITO} postulaciones gratuitas este mes. ¡Hazte Premium para postular sin límites!'>";
                    } else {
                        echo "<input type='submit' id='enviarBtn{$casting_id_actual}' name='enviar_solicitud' value='Postularse' class='boton-solicitud'>";
                    }
                    
                    echo "</form>";

                    echo "</div>";
                }
            } else {
                echo "<p style='text-align:center; color:gray;'>No se pudieron cargar las ofertas.</p>";
            }
        ?>
    </div>
</div> 

<footer class="footer">
    <div class="social-media">
        </div>
    </footer>

</body>
</html>