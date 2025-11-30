<?php
session_name("TALENTEAR_SESION");
session_start();

// ----------------------------------------------------
// --- 1. Inicialización de Variables y Conexión ---
// ----------------------------------------------------
$notifError = null;
$conn = null;
$solicitudes = []; // Contiene TODAS las notificaciones del talento
$nombre = "";
$id_talento = 0;
$notifCount = 0;

$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";
$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    // Es mejor lanzar un error en producción que die, pero para desarrollo, lo mantenemos simple.
    $notifError = "Error de conexión: " . mysqli_connect_error();
}

// -------------------------------------------------------------------------------------
// --- 2. Verificación de Sesión y Obtención de datos del Talento (ID y Nombre) ---
// -------------------------------------------------------------------------------------
if (!isset($_SESSION['email'])) {
    if ($conn) mysqli_close($conn);
    header("Location: ../login.php");
    exit;
}

$email = $_SESSION["email"];
if ($conn) {
    $query = "SELECT id_talento, nombre FROM talentos WHERE email = ?";
    $stmt_user = mysqli_prepare($conn, $query);

    if ($stmt_user) {
        mysqli_stmt_bind_param($stmt_user, "s", $email);
        mysqli_stmt_execute($stmt_user);
        $result = mysqli_stmt_get_result($stmt_user);

        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $nombre = $row["nombre"];
            $id_talento = (int)$row["id_talento"];
        } else {
            mysqli_stmt_close($stmt_user);
            mysqli_close($conn);
            header("Location: ../login.php");
            exit();
        }
        mysqli_stmt_close($stmt_user);
    } else {
        $notifError = "Error al preparar la consulta del usuario: " . mysqli_error($conn);
    }
}


// -------------------------------------------------------------------------------------
// --- 3. Contar notificaciones y Traer TODAS las solicitudes ---
// -------------------------------------------------------------------------------------
if ($id_talento > 0 && $conn) {
    // 3.1. CONTADOR: Solo cuenta notificaciones activas para el Talento
    $notifCountQ = "
        SELECT COUNT(*) AS total
        FROM notificaciones n
        JOIN curriculums c ON n.curriculum_id = c.curriculum_id
        WHERE c.usuario_id = ?
        AND n.leida = 0
        AND (n.estado = 'invitacion' OR (n.estado IN ('aceptada', 'rechazada') AND n.casting_id > 0))
    ";
    $stmt_count = mysqli_prepare($conn, $notifCountQ);
    if ($stmt_count) {
        mysqli_stmt_bind_param($stmt_count, "i", $id_talento);
        mysqli_stmt_execute($stmt_count);
        $rc = mysqli_stmt_get_result($stmt_count);
        if ($rc && mysqli_num_rows($rc) > 0) {
            $notifCount = (int) mysqli_fetch_assoc($rc)['total'];
        }
        mysqli_stmt_close($stmt_count);
    }

    // 3.2. CONSULTA PRINCIPAL: Trae TODAS las notificaciones
    $notifQ = "
        SELECT
            n.notificacion_id AS id_solicitud, n.leida, n.estado, n.fecha, n.casting_id,
            e.id_agencia, e.nombre_agencia, e.email_agencia, e.numero_telefono_agencia,
            e.ubicacion_agencia, e.nombre_contacto, e.apellido_contacto,
            cst.NombrePuesto AS titulo_casting
        FROM notificaciones n
        JOIN curriculums c ON n.curriculum_id = c.curriculum_id
        JOIN agencias e ON n.agencia_id = e.id_agencia
        LEFT JOIN castings cst ON n.casting_id = cst.casting_id
        WHERE c.usuario_id = ?
        ORDER BY n.fecha DESC
    ";
    $stmt_notif = mysqli_prepare($conn, $notifQ);
    if ($stmt_notif) {
        mysqli_stmt_bind_param($stmt_notif, "i", $id_talento);
        mysqli_stmt_execute($stmt_notif);
        $notifResult = mysqli_stmt_get_result($stmt_notif);

        if (!$notifResult) {
            $notifError = mysqli_error($conn);
        } else {
            $solicitudes = mysqli_fetch_all($notifResult, MYSQLI_ASSOC);
        }
        mysqli_stmt_close($stmt_notif);
    } else {
        $notifError = "Error al preparar la consulta de notificaciones: " . mysqli_error($conn);
    }
}

// ----------------------------------------------------
// --- 4. Separación de datos para los dos paneles ---
// ----------------------------------------------------
$invitaciones_directas = [];
$postulaciones_propias = [];

foreach ($solicitudes as $n) {
    $casting_id = (int) ($n['casting_id'] ?? 0);
    
    if ($casting_id === 0) {
        // Invitaciones Directas (deben tener estado 'invitacion', 'aceptada' o 'rechazada')
        if (in_array($n['estado'], ['invitacion', 'aceptada', 'rechazada'])) {
             $invitaciones_directas[] = $n;
        }
    } else {
        // Postulaciones Propias a Castings (casting_id > 0)
        $postulaciones_propias[] = $n;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Mis Solicitudes - TalenteAR</title>

<link rel="stylesheet" type="text/css" href="../css/home-usuarios.css"/>
<link rel="stylesheet" type="text/css" href="../css/sticky.css"/>
<link rel="stylesheet" type="text/css" href="../css/login.css"/>
<link rel="stylesheet" type="text/css" href="../css/solicitudes.css"/>
<script src="../js/profile-menu.js" defer></script>
<style>
/* Estilos para las secciones */
.seccion-solicitudes { margin-top: 40px; }
.seccion-solicitudes h2 { margin-bottom: 20px; border-bottom: 2px solid #ccc; padding-bottom: 5px; }
/* Asegurar que los botones Ver Empresa en el dropdown se vean */
.notificacion .boton-ver {
    background-color: #4863a0;
    color: white;
    padding: 5px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    margin-top: 5px;
    display: inline-block;
}
.notif-dropdown { 
    right: 0;
    left: auto; /* Asegura que se alinee a la derecha */
}
</style>
</head>
<body class="body-solicitudes">

<div class="navbar">
    <div class="navbar-title">
        <div class="imagenesflechas">
            <a href="#" class="flecha-link" onclick="goBack()">
                <img class="flecha" src="../../imagenes/flecha.png" alt="volver atrás" />
                <img class="flecha-hover" src="../../imagenes/flecharellena.png" alt="volver atrás" />
            </a>
        </div>
        <a href="index-logged.php" class="navbar-title">
            <img class="maleta" src="../../imagenes/logotalentear.png" alt="TalenteAR" />
            <h2 class="title">TalenteAR</h2>
        </a>
    </div>

<div class="navbar-links">
    <a href="../cerrar_sesion.php" class="box redirect">Cerrar sesión</a>
    <div class="notif-container">
        <button class="notif-button" onclick="toggleNotificaciones()" style="background:none; border:none; cursor:pointer; position:relative;">
            <img src="../../imagenes/campanita.png" alt="Notificaciones" class="campanita">
            <?php if ($notifCount > 0): ?>
                <span id="notifCount" class="numerito"><?= $notifCount ?></span>
            <?php endif; ?>
        </button>
        <div id="notifDropdown" class="notif-dropdown">
            <h4 style="text-align:center; margin-top:0;">Notificaciones</h4>
            <hr>
            <?php
if ($id_talento > 0 && !empty($solicitudes)) { 
    $temp_conn = mysqli_connect($host, $username, $password, $database);
    if ($temp_conn) {
        $stmt_temp = mysqli_prepare($temp_conn, $notifQ);
        if ($stmt_temp) {
            mysqli_stmt_bind_param($stmt_temp, "i", $id_talento);
            mysqli_stmt_execute($stmt_temp);
            $tempNotifResult = mysqli_stmt_get_result($stmt_temp);

            if ($tempNotifResult) {
                while ($notif = mysqli_fetch_assoc($tempNotifResult)) {
                    $leida = $notif['leida'] ? 'opacity:0.6;' : '';
                    $estado_notif = $notif['estado'] ?? 'pendiente';
                    $casting_id_notif = (int) ($notif['casting_id'] ?? 0);
                    
                    // Condición para mostrar en el Dropdown
                    $is_pending_invitation = ($estado_notif === 'invitacion');
                    $is_recent_response = ($casting_id_notif > 0 && in_array($estado_notif, ['aceptada', 'rechazada']) && $notif['leida'] == 0);
                    
                    if (!$is_pending_invitation && !$is_recent_response) {
                        continue; // Solo muestra invitaciones pendientes y respuestas a castings no leídas.
                    }

                    echo "<div class='notificacion' style='margin-bottom:10px; padding:8px; border-radius:6px; background-color:#f9f9f9; $leida'>";

                    // Lógica de visualización del dropdown
                    if ($estado_notif === 'invitacion') {
                        echo "<p style='margin:0;'><strong>" . htmlspecialchars($notif['nombre_agencia']) . "</strong> te envió una **INVITACIÓN**.</p>";
                        echo "<p style='color:#4863a0; margin-top:3px;'>Nueva Solicitud 📬</p>";
                    } elseif ($estado_notif === 'aceptada' && $casting_id_notif > 0) {
                        echo "<p style='margin:0;'>¡Tu postulación fue **ACEPTADA** por **" . htmlspecialchars($notif['nombre_agencia']) . "**! 🎉</p>";
                        echo "<p style='color:green; margin-top:3px;'>Solicitud aceptada ✅</p>";
                    } elseif ($estado_notif === 'rechazada' && $casting_id_notif > 0) {
                        echo "<p style='margin:0;'>Tu postulación fue **RECHAZADA** por **" . htmlspecialchars($notif['nombre_agencia']) . "**.</p>";
                        echo "<p style='color:#888; margin-top:3px;'>Solicitud rechazada ❌</p>";
                    } else {
                         continue;
                    }

                    // Botón Ver empresa
                    echo "<form method='POST' action='ver_empresa.php' style='margin-top:6px;'>";
                    echo "<input type='hidden' name='email_agencia' value='" . htmlspecialchars($notif['email_agencia']) . "'>";
                    echo "<input type='hidden' name='notif_id' value='" . intval($notif['id_solicitud']) . "'>";
                    echo "<input type='submit' value='Ver empresa' class='boton-ver'>";
                    echo "</form>";

                    echo "<p style='margin:0; font-size:12px; color:gray;'>" . htmlspecialchars($notif['fecha']) . "</p>";
                    echo "</div>";
                }
            }
            mysqli_stmt_close($stmt_temp);
        }
        mysqli_close($temp_conn);
    } else {
        echo "<p style='text-align:center; color:red;'>Error al cargar notificaciones.</p>";
    }
} else {
    echo "<p style='text-align:center; color:gray;'>No tienes notificaciones.</p>";
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
            <a href="../cerrar_sesion.php">Cerrar Sesión</a>
        </div>
    </div>
</div>
</div>
<div class="container-body">
    <?php if ($notifError): ?>
        <p style='color:red; margin-left: 20px;'>Error al cargar datos: <?= htmlspecialchars($notifError) ?></p>
    <?php endif; ?>

    <div class="seccion-solicitudes">
        <h2 class="titulo-seccion">📬 Invitaciones Directas de Agencias</h2>
        <?php if (empty($invitaciones_directas)): ?>
            <p style='text-align:left; color:#666; margin-left: 20px;'>Actualmente no tienes invitaciones directas pendientes o historial de colaboraciones.</p>
        <?php else: ?>
            <div class="solicitudes-feed">
                <?php foreach ($invitaciones_directas as $n): 
                    $estado = $n['estado'] ?? 'pendiente';
                    $id_solicitud = (int)$n['id_solicitud'];
                    $fecha_completa = strtotime($n['fecha']);
                    $fecha_recibida = date('d/m/Y', $fecha_completa);
                    $hora_recibida = date('H:i', $fecha_completa);
                ?>
                    <div class="solicitud-panel">
                        <div class="panel-info-principal">
                            <h3><span class="icon icon-lg">🏢</span> **<?= htmlspecialchars($n['nombre_agencia']) ?>**</h3>
                            <div class="empresa-contacto">
                                <p><span class="icon">👤</span> Representante: <?= htmlspecialchars($n['nombre_contacto'] . ' ' . $n['apellido_contacto']) ?></p>
                                <p><span class="icon">📧</span> Email: <a href="mailto:<?= htmlspecialchars($n['email_agencia']) ?>"><?= htmlspecialchars($n['email_agencia']) ?></a></p>
                                <p><span class="icon">📌</span> Ubicación: <?= htmlspecialchars($n['ubicacion_agencia']) ?></p>
                            </div>
                        </div>

                        <div class="panel-fecha">
                            <p>
                               <span class="icon">📅</span> Recibida el **<?= $fecha_recibida ?>** a las **<?= $hora_recibida ?> hs**
                            </p>
                        </div>

                        <div class="panel-estado">
                            <span class="estado-tag tag-<?= htmlspecialchars($estado) ?>">
                                <?php
                                if ($estado === 'invitacion') {
                                    echo 'Esperando tu respuesta'; 
                                } elseif ($estado === 'aceptada') {
                                    echo 'Invitación Aceptada ✅';
                                } elseif ($estado === 'rechazada') {
                                    echo 'Invitación Rechazada ❌';
                                }
                                ?>
                            </span>
                        </div>

                        <div class="panel-acciones">
                            <?php if ($estado === 'invitacion'): ?>
                                <form method="POST" action="procesar_respuesta_agencia.php" class="form-action">
                                    <input type="hidden" name="notificacion_id" value="<?= $id_solicitud ?>">
                                    <input type="hidden" name="accion" value="aceptada">
                                    <button class="btn-acept btn-small" type="submit">Aceptar ✅</button>
                                </form>
                                <form method="POST" action="procesar_respuesta_agencia.php" class="form-action">
                                    <input type="hidden" name="notificacion_id" value="<?= $id_solicitud ?>">
                                    <input type="hidden" name="accion" value="rechazada">
                                    <button class="btn-rech btn-small" type="submit">Rechazar ❌</button>
                                </form>
                            <?php endif; ?>

                            <form method='POST' action='ver_empresa.php' class='form-action'>
                                <input type='hidden' name='email_agencia' value='<?= htmlspecialchars($n['email_agencia']) ?>'>
                                <button type='submit' class='btn-info btn-small'>Ver Perfil</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div> <hr>
    
    <div class="seccion-solicitudes">
        <h2 class="titulo-seccion">📋 Mis Postulaciones a Castings</h2>
        <?php if (empty($postulaciones_propias)): ?>
            <p style='text-align:left; color:#666; margin-left: 20px;'>Aún no has postulado a ningún casting.</p>
        <?php else: ?>
            <div class="solicitudes-feed">
                <?php foreach ($postulaciones_propias as $p): 
                    $estado = $p['estado'] ?? 'pendiente';
                    $fecha_completa = strtotime($p['fecha']);
                    $fecha_envio = date('d/m/Y', $fecha_completa);
                ?>
                    <div class="solicitud-panel">
                        <div class="panel-info-principal">
                            <h3><span class="icon icon-lg">🎬</span> Casting: **<?= htmlspecialchars($p['titulo_casting'] ?? 'Casting Eliminado/No Encontrado') ?>**</h3>
                            <div class="empresa-contacto">
                                <p><span class="icon">🏢</span> Agencia: <?= htmlspecialchars($p['nombre_agencia']) ?></p>
                                <p><span class="icon">📧</span> Email: <a href="mailto:<?= htmlspecialchars($p['email_agencia']) ?>"><?= htmlspecialchars($p['email_agencia']) ?></a></p>
                                <p><span class="icon">📌</span> Ubicación: <?= htmlspecialchars($p['ubicacion_agencia']) ?></p>
                            </div>
                        </div>

                        <div class="panel-fecha">
                            <p>
                               <span class="icon">📅</span> Enviada el **<?= $fecha_envio ?>**
                            </p>
                        </div>

                        <div class="panel-estado">
                            <span class="estado-tag tag-<?= htmlspecialchars($estado) ?>">
                                <?php
                                if ($estado === 'pendiente') echo 'En Revisión ⏳';
                                elseif ($estado === 'aceptada') echo 'ACEPTADA ✅';
                                elseif ($estado === 'rechazada') echo 'RECHAZADA ❌';
                                ?>
                            </span>
                        </div>

                        <div class="panel-acciones">
                            <form method='POST' action='ver_casting.php' class='form-action'>
                                <input type='hidden' name='casting_id' value='<?= $p['casting_id'] ?>'> 
                                <button type='submit' class='btn-info btn-small'>Ver Casting</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div> </div> <footer class="footer">
    </footer>

<script>
function goBack() { window.history.back(); }

function toggleNotificaciones() {
    const dropdown = document.getElementById('notifDropdown');
    const count = document.getElementById('notifCount');
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileDropdown) profileDropdown.style.display = 'none';
    dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';

    if (count && dropdown.style.display === 'block') {
        // Enviar petición para marcar como leídas las notificaciones activas del Talento
        fetch('marcar_leidas_talento.php', { method: 'POST' })
            .then(() => {
                // Eliminar el numerito después de marcar como leídas
                const countElement = document.getElementById('notifCount');
                if (countElement) {
                     countElement.remove();
                }
            })
            .catch(err => console.error('Error al marcar notificaciones:', err));
    }
}

function toggleProfileMenu() {
    const dropdown = document.getElementById('profileDropdown');
    const notifDropdown = document.getElementById('notifDropdown');
    if (notifDropdown) notifDropdown.style.display = 'none';
    dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';
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
</script>

<?php
if ($conn) {
mysqli_close($conn);
}
?>

</body>
</html>