<?php
session_name("TALENTEAR_SESION");
session_start();
$host = "localhost";
$username = "root";
$password = ""; 
$database = "agenciatrabajo"; 
$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Error al conectar a la base de talentos: " . mysqli_connect_error());
}

$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    $query = "SELECT * FROM talentos WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>TalenteAR - Iniciar sesión</title>
    <link rel="stylesheet" type="text/css" href="../css/login.css"/>
    <link rel="stylesheet" type="text/css" href="../css/sticky.css"/>
    <meta charset="UTF-8" />
</head>
<body>
    <div class="navbar">
        <div class="navbar-title">
            <div class="imagenesflechas">
                <a href="#" class="flecha-link" onclick="goBack()">
                    <img class="flecha" src="../../imagenes/flecha.png" alt="volver atrás" />
                    <img class="flecha-hover" src="../../imagenes/flecharellena.png" alt="volver atrás" />
                </a>
            </div>
            <a href="index.html" class="navbar-title">
                <img class="maleta" src="../../imagenes/logotalentear.png" alt="" />
                <h2 class="title">TalenteAR</h2>
            </a>
        </div>
        <div class="box-container">
            <a href="../../TalenteARpartners/html/partners.html" class="box redirect">
                Soy una empresa
            </a>
        </div>
    </div>

    <div class="container">
    <?php if ($success_message): ?>
            <p style="color: green; margin: 0;"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <h1>Iniciar sesión</h1>
        <form class="login-form" action="login.php" method="POST">
            <input type="email" name="email" placeholder="Correo electrónico" required />
            <input type="password" name="password" placeholder="Contraseña" required />
            <button type="submit">Iniciar sesión</button>

            <?php
                if (isset ($result)) {
                    if (mysqli_num_rows($result) > 0) {
                        $row = mysqli_fetch_assoc($result);
                        $stored_password = $row["password"];
                        if (password_verify($password, $stored_password)) {
                            $_SESSION["email"] = $email;
                            header("Location: index-logged.php");
                            exit();
                        } else {
                            echo '<p style="color: red;">Contraseña incorrecta. Por favor, intenta nuevamente.</p>';
                        }
                    } else {
                        echo '<p style="color: red;">El correo electrónico no está registrado. Por favor, crea una cuenta primero.</p>';
                    }
                }
                mysqli_close($conn);
            ?>

            <p>
                ¿No tienes una cuenta?
                <a class="registrate" href="register.php">Regístrate</a>
            </p>
        </form>
    </div>
    <footer class="footer">
        <div class="social-media">
            <a href="https://www.instagram.com/TalenteARtec4"><img src="../../imagenes/instagram-alt-logo-108.png"/></a>
            <a href="https://www.linkedin.com/in/TalenteAR/"><img src="../../imagenes/linkedin-logo-108.png"/></a>
            <a href="https://www.facebook.com/profile.php?id_talento=61553049380666"><img src="../../imagenes/facebook-circle-logo-108.png"/></a>
        </div>
        <div class="footer-links">
            <a href="terminosycondiciones.html">Términos y Condiciones</a><b>-</b>
            <a href="politic.html">&nbsp;Política de Privacidad</a><b>-</b>
            <a href="address.html">&nbsp;Dirección física</a>
        </div>
        <div class="footer-text">TalenteAR® - Todos los derechos reservados</div>
    </footer>
</body>
<script>
    function goBack() {
        window.history.back();
    }
</script>
</html>
