<?php
// 1. ASIGNAR NOMBRE DE SESIÓN ÚNICO (Para Partners)
session_name("PARTNERS_SESION"); 
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
    $email_agencia = $_POST["email_agencia"];
    $password_agencia = $_POST["password_agencia"];
    // **¡IMPORTANTE!** Usa prepared statements para seguridad (aunque aquí no lo estás haciendo, tenlo en cuenta)
    $query = "SELECT * FROM agencias WHERE email_agencia = '$email_agencia'";
    $result = mysqli_query($conn, $query);
    
    if (isset ($result)) {
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $stored_password = $row["password_agencia"];
            if (password_verify($password_agencia, $stored_password)) {
                // SESIÓN GUARDADA CORRECTAMENTE
                $_SESSION["email_agencia"] = $email_agencia;
                header("Location: index-logged.php"); // Asegúrate de que index-logged.php use PARTNERS_SESION
                exit();
            } else {
            // ... (el resto de tu manejo de errores)
            }
        }
    }
}
// El resto de la parte PHP no requiere cambios si está funcionando
?>


<!DOCTYPE html>
<html>
  <head>
    <title>TalenteAR partners - Iniciar sesión</title>
    <link rel="stylesheet" type="text/css" href="../css/loginempresas.css"/>
    <link rel="stylesheet" type="text/css" href="../css/sticky.css"/>
    <meta charset="UTF-8" />
  </head>
  <body>
    <div class="navbar">
      <div class="navbar-title">
        <div class="imagenesflechas">
          <a href="#" class="flecha-link" onclick="goBack()">
            <img
              class="flecha"
              src="../../imagenes/flecha.png"
              alt="volver atrás"
            />
            <img
              class="flecha-hover"
              src="../../imagenes/flecharellena-empresas.png"
              alt="volver atrás"
            />
          </a>
        </div>
        <a href="../html/partners.html" class="navbar-title">
          <img class="maleta" src="../../imagenes/logotalentear.png" alt="" />
          <h2 class="title">TalenteAR partners</h2>
        </a>
      </div>
      <div class="box-container">
        <a href="../../Talentear/html/index.html" class="box redirect">
          No soy una agencia
        </a>
      </div>
    </div>

    <div class="container">
    <?php if ($success_message): ?>
            <p style="color: green; margin: 0;"><?php echo $success_message; ?></p>
        <?php endif; ?>
      <h1>Iniciar sesión como Empresa</h1>
      <form class="login-form" action="loginpartners.php" method="POST">
        <input type="email" name="email_agencia" placeholder="Correo electrónico" required/>
        <input type="password" name="password_agencia" placeholder="Contraseña" required/>
        <button type="submit">Iniciar sesión</button>

        <?php
            if (isset ($result)) {
                if (mysqli_num_rows($result) > 0) {
                  $row = mysqli_fetch_assoc($result);
                  $stored_password = $row["password_agencia"];
                  if (password_verify($password_agencia, $stored_password)) {
                      $_SESSION["email_agencia"] = $email_agencia;
                      header("Location: index-logged.php"); 
                      exit();
                  } else {
                  echo '<p style="color: red;">Contraseña incorrecta. Por favor, intenta nuevamente. </p>';
                  }
                } else {
                  echo '<p style="color: red;">El correo electrónico no está registrado. Por favor, crea una cuenta primero.</p>';
                }
              }
              mysqli_close($conn);
        ?>
        
        <p>
          ¿No tienes una cuenta?
          <a class="registrate" href="registerpartners.php">Regístrate como agencia</a>
        </p>
      </form>
    </div>
    <footer class="footer">
      <div class="social-media">
        <a href="https://www.instagram.com/TalenteARtec4"
          ><img src="../../imagenes/instagram-alt-logo-108.png"
        /></a>
        <a href="https://www.linkedin.com/in/TalenteAR/"
          ><img src="../../imagenes/linkedin-logo-108.png"
        /></a>
        <a href="https://www.facebook.com/profile.php?id_talento=61553049380666"
          ><img src="../../imagenes/facebook-circle-logo-108.png"
        /></a>
      </div>
      <div class="footer-links">
        <a href="../../TalenteARpartners/html/terminosycondiciones-partners.html">Términos y Condiciones -</a>
        <a href="../../TalenteARpartners/html/politic.html">Política de Privacidad -</a>
        <a href="../../TalenteARpartners/html/address.html">Dirección física</a>
      </div>
      <div class="footer-text">TalenteAR® - Todos los derechos reservados</div>
    </footer>
    <script>
      function goBack() {
        window.history.back();
      }
    </script>
  </body>
</html>
