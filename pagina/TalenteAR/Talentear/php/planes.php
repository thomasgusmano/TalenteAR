<?php
session_name("TALENTEAR_SESION");
session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "agenciatrabajo";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    error_log("Error al conectar a la base de datos: " . mysqli_connect_error());
    header("Location: error.php?code=500");
    exit();
}

// 1. Validar sesión y obtener datos del talento
if (!isset($_SESSION["email"])) {
    header("Location: login.php");
    exit();
}
$email = $_SESSION["email"];
$id_talento = 0;
$nombre = "";
$es_premium = false;
$error_message = "";

$query = "SELECT id_talento, nombre, es_premium FROM talentos WHERE email = ?";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $id_talento = (int)$row["id_talento"];
        $nombre = $row["nombre"];
        $es_premium = (bool)$row["es_premium"];
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        header("Location: login.php");
        exit();
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Error al preparar la consulta de talento: " . mysqli_error($conn));
    $error_message = "Error interno. Intente más tarde.";
}


// 2. Lógica para la compra de suscripción (Simulación)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['plan_id']) && $_POST['plan_id'] === 'premium' && $id_talento > 0) {
    
    // ** SIMULACIÓN DE PROCESAMIENTO DE PAGO **
    // En un entorno real, aquí se procesarían los datos de la tarjeta con un proveedor de pagos.
    // Como se solicitó, asumimos que el pago con datos ficticios es exitoso.
    
    if ($es_premium) {
        $error_message = "¡Ya tienes el plan Premium activo! No necesitas suscribirte de nuevo.";
    } else {
        // Consulta para actualizar el estado Premium y resetear el contador de postulaciones
        // Esto le da al usuario postulaciones ilimitadas.
        // Campo: es_premium, postulaciones_mes
        $update_premium_query = "
            UPDATE talentos 
            SET es_premium = 1, postulaciones_mes = 0 
            WHERE id_talento = ?
        ";
        
        $stmt_update = mysqli_prepare($conn, $update_premium_query);

        if ($stmt_update) {
            mysqli_stmt_bind_param($stmt_update, "i", $id_talento);
            $success = mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);

            if ($success) {
                // Éxito: Redirigir al dashboard con un mensaje
                mysqli_close($conn);
                header("Location: index-logged.php?status=premium_activated");
                exit();
            } else {
                $error_message = "Error en la base de datos al activar el plan Premium.";
            }
        } else {
            $error_message = "Error interno del servidor al preparar la actualización.";
        }
    }
}

mysqli_close($conn); // Cerrar conexión si no redirigimos
?>
<!DOCTYPE html>
<html>
<head>
    <title>Planes de Suscripción</title>
    <link rel="stylesheet" type="text/css" href="../css/home-usuarios.css"/>
    <link rel="stylesheet" type="text/css" href="../css/sticky.css"/>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        /* Estilos específicos para la página de planes */
        .planes-container {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 30px;
            padding: 50px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .plan-card {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 45%;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 3px solid #ddd;
        }
        
        .plan-card.premium {
            border-color: #4863a0; /* Azul TalenteAR */
            box-shadow: 0 8px 25px rgba(72, 99, 160, 0.3);
            transform: scale(1.03);
        }

        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .plan-card h3 {
            font-size: 2em;
            color: #333;
            margin-bottom: 10px;
        }

        .plan-card .price {
            font-size: 3em;
            color: #4863a0;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .plan-card ul {
            list-style: none;
            padding: 0;
            margin-bottom: 30px;
            text-align: left;
        }

        .plan-card li {
            padding: 10px 0;
            border-bottom: 1px dashed #eee;
            color: #555;
            display: flex;
            align-items: center;
        }

        .plan-card li:last-child {
            border-bottom: none;
        }
        
        .plan-card li span {
            margin-right: 10px;
            color: #3e8e41;
            font-weight: bold;
            font-size: 1.2em;
        }

        /* Formulario de Pago */
        .payment-form {
            margin-top: 30px;
            text-align: left;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .payment-form label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
            color: #333;
        }

        .payment-form input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        
        .card-details {
            display: flex;
            gap: 15px;
        }
        .card-details > div {
            width: 50%;
        }

        .btn-subscribe {
            background-color: #3e8e41;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            margin-top: 20px;
            width: 100%;
            transition: background-color 0.3s;
        }

        .btn-subscribe:hover {
            background-color: #337a35;
        }

        .alert-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
            text-align: center;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .current-plan {
            background-color: #e6f7ff;
            color: #004085;
            padding: 8px;
            border-radius: 5px;
            margin-top: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="navbar-title">
        <a href="../php/index-logged.php" class="navbar-title">
            <img class="maleta" src="../../imagenes/logotalentear.png" alt="Logo TalenteAR"/>
            <h2 class="title">TalenteAR</h2>
        </a>
    </div>
    <div class="navbar-links">
        <a href="index-logged.php" class="box redirect">Inicio</a>
        <a href="solicitudes.php" class="box redirect">📬 Solicitudes</a>
        <a href="cerrar_sesion.php" class="box redirect">Cerrar sesión</a>
        
        <div class="profile-menu">
            <span class="welcome-text-navbar"><?= htmlspecialchars($nombre) ?></span>
            <button class="profile-button">
                <img src="../../imagenes/profilelogo.png" alt="Perfil" class="profile-logo" />
            </button>
            </div>
    </div>
</div>

<div class="container">
    <h1 style="text-align: center; margin-top: 40px; color: #4863a0;">Elige el Plan Perfecto para Impulsar tu Carrera</h1>
    <p style="text-align: center; margin-bottom: 30px; color: #555;">Mejora tu visibilidad y postúlate sin límites.</p>

    <?php if ($error_message): ?>
        <div class="alert-message alert-error" style="max-width: 600px; margin: 20px auto;">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <div class="planes-container">
        
        <div class="plan-card <?= !$es_premium ? 'premium' : '' ?>">
            <h3>Plan Gratuito</h3>
            <p class="price">$0/mes</p>
            <ul>
                <li><span>✅</span> Crear y mantener tu Currículum.</li>
                <li><span>✅</span> Acceso a todas las ofertas de Casting.</li>
                <li><span>✅</span> Ser visto por las Agencias.</li>
                <li><span>⚠️</span> **Límite de 5 postulaciones al mes.**</li>
                <li><span>❌</span> Sin prioridad en búsquedas.</li>
            </ul>
            
            <?php if ($es_premium): ?>
                <div class="current-plan">Tu plan actual es PREMIUM.</div>
            <?php else: ?>
                <div class="current-plan">Tu plan actual.</div>
            <?php endif; ?>
        </div>

        <div class="plan-card premium">
            <h3>Plan Premium</h3>
            <p class="price">$9.99/mes</p>
            <ul>
                <li><span>⭐</span> Crear y mantener tu Currículum.</li>
                <li><span>⭐</span> Acceso a todas las ofertas de Casting.</li>
                <li><span>⭐</span> **Postulaciones Ilimitadas.**</li>
                <li><span>⭐</span> **Prioridad alta** en los resultados de búsqueda de Agencias.</li>
                <li><span>⭐</span> Notificaciones instantáneas de interés de Agencias.</li>
            </ul>
            
            <?php if ($es_premium): ?>
                <div class="current-plan">¡Felicitaciones! Este es tu plan.</div>
            <?php else: ?>
                <form action="planes.php" method="POST" class="payment-form">
                    <h4>Datos de Tarjeta Ficticios</h4>
                    <input type="hidden" name="plan_id" value="premium">
                    
                    <label for="card_name">Nombre en la tarjeta</label>
                    <input type="text" id="card_name" name="card_name" required placeholder="Talento Talentuoso">

                    <label for="card_number">Número de Tarjeta (Ficticio)</label>
                    <input type="text" id="card_number" name="card_number" required placeholder="4111222233334444" pattern="\d{16}" title="16 dígitos numéricos">
                    
                    <div class="card-details">
                        <div>
                            <label for="expiry">Fecha Exp. (MM/AA)</label>
                            <input type="text" id="expiry" name="expiry" required placeholder="12/26" pattern="\d{2}/\d{2}" title="Formato MM/AA">
                        </div>
                        <div>
                            <label for="cvv">CVV (Ficticio)</label>
                            <input type="text" id="cvv" name="cvv" required placeholder="123" pattern="\d{3,4}" title="3 o 4 dígitos numéricos">
                        </div>
                    </div>

                    <button type="submit" class="btn-subscribe">
                        Activar Plan Premium ($9.99/mes)
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div> 

<footer class="footer">
    <div class="social-media">
        </div>
</footer>

<script src="../js/profile-menu.js"></script>
</body>
</html>