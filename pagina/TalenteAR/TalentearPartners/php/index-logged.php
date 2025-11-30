<?php
session_name("PARTNERS_SESION");
session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";

$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    header("Location: error.php?code=500");
    exit();
}

if (!isset($_SESSION["email_agencia"])) {
    header("Location: error.php?code=401");
    exit();
}

$email_agencia = $_SESSION["email_agencia"];

// 🟣 Obtener nombre e ID de la empresa
$query = "SELECT id_agencia, nombre_contacto FROM agencias WHERE email_agencia = '$email_agencia'";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $nombre_contacto = $row["nombre_contacto"];
    $id_agencia = (int)$row["id_agencia"];
} else {
    header("Location: loginempresas.php");
    exit();
}

// ----------------------------------------------------
// ---------- 🔔 INICIO NOTIFICACIONES 🔔 ----------
// ----------------------------------------------------

$notifCountQuery = "
    SELECT COUNT(*) AS total
    FROM notificaciones n
    WHERE n.agencia_id = $id_agencia 
    AND n.leida = 0
    AND n.estado != 'invitacion' /* FIX: Excluir las invitaciones */
";

// 🔥 FIX CRÍTICO: Ejecutar la consulta de conteo y asignar el resultado a $notifCount
$notifCountResult = mysqli_query($conn, $notifCountQuery);
$notifCount = 0; // Inicializar la variable
if ($notifCountResult && mysqli_num_rows($notifCountResult) > 0) {
    $notifCount = (int) mysqli_fetch_assoc($notifCountResult)['total'];
}

$notifQuery = "
    SELECT n.*, t.nombre, t.apellido, c.NombrePuesto
    FROM notificaciones n
    JOIN curriculums cur ON n.curriculum_id = cur.curriculum_id
    JOIN talentos t ON cur.usuario_id = t.id_talento
    LEFT JOIN castings c ON n.casting_id = c.casting_id
    WHERE n.agencia_id = $id_agencia
    AND n.estado != 'invitacion' /* FIX: Excluir las invitaciones */
    ORDER BY n.fecha DESC
";
$notifResult = mysqli_query($conn, $notifQuery);
$notifError = $notifResult === false ? mysqli_error($conn) : null;

// --------------------------------------------------
// ---------- 🔔 FIN NOTIFICACIONES 🔔 ----------
// --------------------------------------------------

?>

<!DOCTYPE html>
<html>
<head>
    <title>Bienvenido</title>
    
    <link rel="stylesheet" type="text/css" href="../css/home-empresas.css"/>
    <link rel="stylesheet" type="text/css" href="../css/sticky.css"/>
    
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        /* Estilos para resaltar el CV Premium */
        .curriculum.premium {
            border: 2px solid #4863a0 !important; /* Borde más grueso y azul */
            background-color: #e6f0ff !important; /* Fondo ligeramente azulado */
            padding: 20px;
        }
        .curriculum.premium h2 {
            color: #4863a0;
        }
        .premium-tag {
            background-color: #ffd700;
            color: #1a1a1a;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8em;
            margin-left: 10px;
            font-weight: bold;
        }
        /* Nuevo estilo para notificaciones no leídas en el dropdown */
        .notificacion-unread {
            background-color: #ffe6e6 !important; /* Fondo rojo claro */
            border-left: 5px solid #ff4d4d !important; /* Borde izquierdo rojo */
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="navbar-title">
        <a href="index-logged.php" class="navbar-title">
            <img class="maleta" src="../../imagenes/logotalentear.png" alt="" />
            <h2 class="title">TalenteAR partners</h2>
        </a>
    </div>
    <div class="navbar-links">
        <a href="postulaciones.php" class="box redirect">👥 Postulantes</a>
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
                            $nombre_talento = htmlspecialchars($notif['nombre']) . " " . htmlspecialchars($notif['apellido']);
                            $estado = $notif['estado'];
                            $es_leida = (int)$notif['leida']; // Comprobación segura
                            $casting_puesto = htmlspecialchars($notif['NombrePuesto']) ?? 'N/A';
                            
                            $mensaje = "";
                            $clase_estado = "";
                            $texto_estado = "";
                            
                            // 🔥 Lógica para manejar la Postulación (estado='pendiente')
                            if ($estado === 'pendiente') {
                                $mensaje = "¡**$nombre_talento** se postuló al casting **'$casting_puesto'**!";
                                $clase_estado = "estado-pendiente";
                                $texto_estado = "Nueva Postulación 📥";
                            } elseif ($estado === 'aceptada') {
                                $mensaje = "<strong>$nombre_talento</strong> aceptó tu invitación para $casting_puesto.";
                                $clase_estado = "estado-aceptada";
                                $texto_estado = "Invitación aceptada ✅";
                            } elseif ($estado === 'rechazada') {
                                $mensaje = "<strong>$nombre_talento</strong> rechazó tu invitación.";
                                $clase_estado = "estado-rechazada";
                                $texto_estado = "Invitación rechazada ❌";
                            } else {
                                // Caso de seguridad, aunque no debería ocurrir con tu DB
                                continue; 
                            }

                            // Aplicar clase visual si NO está leída
                            $unread_class = $es_leida === 0 ? 'notificacion-unread' : '';
                            
                            // El link lleva a la tabla de solicitudes al final de la página (se asume que existe)
                            echo "<a href='#tabla_solicitudes' class='notificacion $unread_class' style='text-decoration:none; color:inherit; display:block; padding: 10px; margin-bottom: 5px; border-radius: 5px;'>"; 
                            echo "<p>$mensaje</p>";
                            echo "<p class='notif-fecha'>" . htmlspecialchars($notif['fecha']) . "</p>";
                            echo "<p class='notif-estado $clase_estado'>$texto_estado</p>";
                            echo "</a>";
                        }
                    } else {
                        // Mensaje por defecto cuando NO HAY notificaciones
                        echo "<p style='text-align:center; color:gray; padding: 15px;'>No tienes notificaciones nuevas.</p>";
                    }
                }
                ?>
            </div>
        </div>
        <div class="profile-menu">
            <button class="profile-button" onclick="toggleProfileMenu()">
            <img src="../../imagenes/profilelogo.png" alt="Perfil" class="profile-logo" />
          </button>
          <div id="profileDropdown" class="dropdown-content">
            <?php echo "<p style='text-align:center'>¡Hola, $nombre_contacto!</p>"; ?>
            <hr>
            <a href="perfil.php">Mi Perfil</a>
            <a href="configuracion.php">Configuración</a>
            <a href="cerrar_sesion.php">Cerrar Sesión</a>
          </div>
        </div>
    </div>
</div>

<?php
echo "<h1 style='color: purple;'>Bienvenido, " . $nombre_contacto . "!</h1>";
?>

<div class="container">
    <div class="form-container">
        <form id="formOferta" action="save_offer.php" method="POST">
            <h3>¡CREA TU OFERTA DE EMPLEO!</h3>
            <label for="NombrePuesto">Nombre del puesto a cubrir:</label><br>
            <input type="text" id="NombrePuesto" name="NombrePuesto" required><br><br>

            <label for="DescripcionPuesto">Descripción del puesto:</label><br>
            <textarea id="DescripcionPuesto" name="DescripcionPuesto" class="DescPuesto" required></textarea><br><br>

            <label for="CantVacantes">Cantidad de vacantes:</label><br>
            <input type="text" id="CantVacantes" name="CantVacantes" required><br><br>

            <label for="NivelExperiencia">Nivel de experiencia requerido:</label><br>
            <select name="NivelExperiencia" id="NivelExperiencia" style="width: 100%; height: 40px; font-size:14px;">
                <option value="Bajo">Bajo</option>
                <option value="Intermedio">Intermedio</option>
                <option value="Alto">Alto</option>
            </select>

            <input type="submit" name="submit_offer" value="Publicar" class="boton-publicar">
        </form>
    </div>

    <div class="curriculums-container">
        <h3 class="sticky-header">¡ENCUENTRA TU CANDIDATO IDEAL!</h3>

<?php
// Consulta con la mejora para mostrar Talentos Premium primero Y FILTRAR CVS VACÍOS
$query = "
SELECT 
    c.*, 
    d.nombre, 
    d.apellido,
    d.es_premium
FROM curriculums c
JOIN talentos d ON c.usuario_id = d.id_talento
WHERE 
    LENGTH(TRIM(c.biografia)) > 0 OR
    LENGTH(TRIM(c.educacion)) > 0 OR
    LENGTH(TRIM(c.ciudad)) > 0 OR
    LENGTH(TRIM(c.experiencia)) > 0 OR
    LENGTH(TRIM(c.habilidades)) > 0
ORDER BY 
    d.es_premium DESC,
    d.id_talento DESC
";

$resultCurriculums = mysqli_query($conn, $query);

if (!$resultCurriculums) {
    die("Error SQL: " . mysqli_error($conn));
}

if (mysqli_num_rows($resultCurriculums) === 0) {
    echo "<p>No se encontraron currículums que hayan sido publicados con contenido.</p>";
} else {
    while ($row = mysqli_fetch_assoc($resultCurriculums)) {
        $curriculum_id = $row['curriculum_id'];
        $es_premium = isset($row['es_premium']) ? (bool)$row['es_premium'] : false; // Manejo seguro
        $premium_class = $es_premium ? 'premium' : ''; // Clase CSS
        
        // Verificar si esta empresa ya envió solicitud a este currículum
        $checkQuery = "SELECT 1 FROM notificaciones WHERE curriculum_id = '$curriculum_id' AND agencia_id = '$id_agencia'";
        $checkResult = mysqli_query($conn, $checkQuery);
        $yaEnviado = ($checkResult && mysqli_num_rows($checkResult) > 0);

        ?>

        <div class="curriculum <?= $premium_class ?>">
            <h2>
                <?= htmlspecialchars($row['nombre']) ?> <?= htmlspecialchars($row['apellido']) ?>
                <?php if ($es_premium): ?>
                    <span class="premium-tag">⭐ TALENTO PREMIUM</span>
                <?php endif; ?>
            </h2>
            <p><strong>Biografía:</strong> <?= htmlspecialchars($row['biografia']) ?></p>
            <p><strong>Educación:</strong> <?= htmlspecialchars($row['educacion']) ?></p>
            <p><strong>Ciudad:</strong> <?= htmlspecialchars($row['ciudad']) ?></p>
            <p><strong>Experiencia:</strong> <?= htmlspecialchars($row['experiencia']) ?></p>


        <form method="POST" action="enviar_invitacion.php"> 
              <input type="hidden" name="curriculum_id" value="<?= $curriculum_id ?>">
              <?php 
              ?>
              <?php if ($yaEnviado): ?>
                  <input type="button" value="Invitación enviada" class="boton-solicitud" style="background-color: #4CAF50;" disabled>
              <?php else: ?>
                  <input type="submit" name="enviar_invitacion" value="Invitar a ver CV" class="boton-solicitud">
              <?php endif; ?>
        </form>
        </div>

        <?php
    }
}
?>
    </div>
</div>





<footer class="footer">
    <div class="social-media">
        <a href="https://www.instagram.com/TalenteARtec4"><img src="../../imagenes/instagram-alt-logo-108.png" /></a>
        <a href="https://www.linkedin.com/in/TalenteAR/"><img src="../../imagenes/linkedin-logo-108.png" /></a>
        <a href="https://www.facebook.com/profile.php?id_talento=61553049380666"><img src="../../imagenes/facebook-circle-logo-108.png" /></a>
    </div>
    <div class="footer-links">
        <a href="/TalenteARpartners/html/terminosycondiciones-empresas.html">Términos y Condiciones -</a>
        <a href="empresas.html">Política de Privacidad -</a>
        <a href="empresas.html">Dirección física</a>
    </div>
    <div class="footer-text">TalenteAR® - Todos los derechos reservados</div>
</footer>

<script>
// Toggle notificaciones
function toggleNotificaciones() {
    const dropdown = document.getElementById('notifDropdown');
    const count = document.getElementById('notifCount');
    const profileDropdown = document.getElementById('profileDropdown');
    
    // Cierra el menú de perfil si está abierto
    if (profileDropdown) profileDropdown.style.display = 'none';
    
    // Muestra/oculta el dropdown de notificaciones
    dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';

    // Si el contador existe y estamos abriendo el dropdown
    if (count && dropdown.style.display === 'block') {
        // Llama al script para marcar notificaciones como leídas
        // NOTA: Asegúrate de que 'notisleidas.php' exista y ponga leida=1
        fetch('notisleidas.php', { method: 'POST' })
            .then(() => {
                const unreadNotifs = document.querySelectorAll('.notificacion-unread');
                unreadNotifs.forEach(n => n.classList.remove('notificacion-unread'));
                if (count) count.remove(); // Elimina el 'numerito' rojo
            }) 
            .catch(err => console.error('Error al marcar notificaciones:', err));
    }
}

// Toggle perfil
function toggleProfileMenu() {
    const dropdown = document.getElementById('profileDropdown');
    const notifDropdown = document.getElementById('notifDropdown');

    // Cierra las notificaciones si están abiertas
    if (notifDropdown) notifDropdown.style.display = 'none';

    // Muestra/oculta el dropdown de perfil
    dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
}

// Cerrar dropdowns al hacer clic fuera
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

// (Tu script 'enviarSolicitud' original de AJAX, si lo sigues usando)
function enviarSolicitud(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    fetch('enviar_solicitud.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        console.log('Respuesta del servidor:', data);
        const boton = form.querySelector('.boton-solicitud');
        boton.value = 'Solicitud enviada';
        boton.style.backgroundColor = '#4CAF50';
        boton.disabled = true;
    })
    .catch(error => {
        console.error('Error al enviar solicitud:', error);
    });
}
</script>
</body>
</html>