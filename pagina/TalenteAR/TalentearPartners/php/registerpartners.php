<?php
session_name("PARTNERS_SESION");
session_start();

$host = "localhost"; 
$username = "root"; 
$password = ""; 
$database = "agenciatrabajo"; 

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Error al conectar a la base de datos: " . mysqli_connect_error());
}

$error = null;
// Inicialización de variables para repoblar el formulario (UX)
$nombre_agencia = $ubicacion_agencia = $numero_telefono_agencia = $email_agencia = $nombre_contacto = $apellido_contacto = '';
$terms = false;


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Recolección de variables y limpieza (trim)
    $nombre_agencia = trim($_POST["nombre_agencia"]);
    $ubicacion_agencia = trim($_POST["ubicacion_agencia"]);
    $numero_telefono_agencia = trim($_POST["numero_telefono_agencia"]);
    $email_agencia = trim($_POST["email_agencia"]);
    $password_agencia = $_POST["password_agencia"]; 
    $confirm_passwordEmpresa = $_POST["confirm_passwordEmpresa"];
    $nombre_contacto = trim($_POST["nombre_contacto"]);
    $apellido_contacto = trim($_POST["apellido_contacto"]);
    $terms = isset($_POST["terms"]);

    // --- 2. VALIDACIONES DE FORMATO Y LÍMITES ---
    
    // Validación de campos vacíos y términos
    if (empty($nombre_agencia) || empty($ubicacion_agencia) || empty($numero_telefono_agencia) || empty($email_agencia) || empty($password_agencia) || empty($confirm_passwordEmpresa) || empty($nombre_contacto) || empty($apellido_contacto)) {
        $error = "Todos los campos de la agencia son obligatorios.";
    } elseif (!$terms) {
        $error = "Debes aceptar los Términos y Condiciones para registrar a tu agencia.";
    }

    // Validación de Nombre/Apellido de Contacto (Solo letras, espacios, hasta 50 caracteres)
    elseif (!preg_match("/^[a-zA-Z\s]{1,50}$/", $nombre_contacto)) {
        $error = "El Nombre del contacto solo debe contener letras y espacios (máximo 50 caracteres).";
    }
    elseif (!preg_match("/^[a-zA-Z\s]{1,50}$/", $apellido_contacto)) {
        $error = "El Apellido del contacto solo debe contener letras y espacios (máximo 50 caracteres).";
    }

    // Validación de Longitud de Nombre de Agencia
    elseif (strlen($nombre_agencia) > 100) {
        $error = "El nombre de la agencia es demasiado largo (máximo 100 caracteres).";
    }
    
    // Validación de Teléfono (solo números, mínimo 8, máximo 15)
    elseif (!preg_match("/^[0-9]{8,15}$/", $numero_telefono_agencia)) {
        $error = "El número de teléfono debe contener solo dígitos (entre 8 y 15).";
    }

    // Validación de Formato de Email y Dominio
    elseif (!filter_var($email_agencia, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico no es válido.";
    }
    elseif (!checkdnsrr(substr($email_agencia, strpos($email_agencia, '@') + 1), 'MX')) {
        $error = "El dominio del correo electrónico parece no ser válido o no existe.";
    }

    // Validación de Longitud de Contraseña (mínimo 8, máximo 32)
    elseif (strlen($password_agencia) < 8 || strlen($password_agencia) > 32) {
        $error = "La contraseña debe tener entre 8 y 32 caracteres.";
    }
    
    // Validación de coincidencia de contraseñas
    elseif ($password_agencia != $confirm_passwordEmpresa) {
        $error = "Las contraseñas no coinciden. Por favor, intenta nuevamente.";
    }
    
    // Si todas las validaciones pasan:
    if ($error === null) {
        // Comprobación de existencia de email (VULNERABLE a Inyección SQL)
        $query = "SELECT * FROM agencias WHERE email_agencia = '$email_agencia'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
          $error = "El correo electrónico ya está registrado. Por favor, utiliza otro correo electrónico.";
        } else { 
            $hashed_password = password_hash($password_agencia, PASSWORD_DEFAULT);
            
            // Inserción de datos (VULNERABLE a Inyección SQL)
            $query = "INSERT INTO agencias (nombre_agencia, ubicacion_agencia, numero_telefono_agencia, email_agencia, password_agencia, nombre_contacto, apellido_contacto) 
                      VALUES ('$nombre_agencia', '$ubicacion_agencia', '$numero_telefono_agencia', '$email_agencia', '$hashed_password', '$nombre_contacto', '$apellido_contacto')";
            
            $result = mysqli_query($conn, $query);
            
            if ($result) {
              $_SESSION['success_message'] = "Registro exitoso. Ahora puedes iniciar sesión.";
              header("Location: loginpartners.php");
              exit();
            } else {
              $error = "Error al guardar el registro: " . mysqli_error($conn);
            }
        }
    }
} 

mysqli_close($conn);
?>


<!DOCTYPE html>
<html>
<head>
  <title>TalenteAR partners - Registrarse</title>
  <link rel="stylesheet" type="text/css" href="../css/registerempresas.css">
  <link rel="stylesheet" type="text/css" href="../css/sticky.css">
  <meta charset="UTF-8">
</head>
<body>
  <div class="navbar">
    <div class="navbar-title">
      <div class="imagenesflechas">
        <a href="#" class="flecha-link" onclick="goBack()">
          <img class="flecha" src="../../imagenes/flecha.png" alt="volver atrás"> 
          <img class="flecha-hover" src="../../imagenes/flecharellena-empresas.png" alt="volver atrás">
        </a>
      </div>
        <a href="../html/partners.html" class="navbar-title">
          <img class="maleta" src="../../imagenes/logotalentear.png" alt="">
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
      <h2>Registro de agencias</h2>
      <form action="registerpartners.php" method="POST">
          <fieldset>
              <legend>Datos de la agencia</legend>
              <div class="form-row">
                  <div class="form-group">
                    <label for="nombre_agencia">Nombre de la agencia:</label>
                    <input type="text" id="nombre_agencia" name="nombre_agencia" required value="<?php echo htmlspecialchars($nombre_agencia ?? ''); ?>" maxlength="100">
                  </div>
                  <div class="form-group">
                    <label for="ubicacion_agencia">Ubicacion de la agencia:</label>
                    <input type="text" id="ubicacion_agencia" name="ubicacion_agencia" required value="<?php echo htmlspecialchars($ubicacion_agencia ?? ''); ?>" maxlength="100">
                  </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label for="numero_telefono_agencia">Número de teléfono:</label>
                  <input type="tel" id="numero_telefono_agencia" name="numero_telefono_agencia" required value="<?php echo htmlspecialchars($numero_telefono_agencia ?? ''); ?>" maxlength="15">
                </div>
                <div class="form-group">
                  <label for="email_agencia">Correo electrónico:</label>
                  <input type="email" id="email_agencia" name="email_agencia" required value="<?php echo htmlspecialchars($email_agencia ?? ''); ?>" maxlength="100">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label for="password_agencia">Contraseña:</label>
                  <input type="password" id="password_agencia" name="password_agencia" required minlength="8" maxlength="32">
                </div>
                <div class="form-group">
                  <label for="confirm_passwordEmpresa">Confirmar contraseña:</label>
                  <input type="password" id="confirm_passwordEmpresa" name="confirm_passwordEmpresa" required minlength="8" maxlength="32">
                </div>
              </div>
              <fieldset>
                <legend>Datos del contacto principal</legend>
                <div class="form-row">
                  <div class="form-group">
                    <label for="nombre_contacto">Nombre del usuario:</label>
                    <input type="text" id="nombre_contacto" name="nombre_contacto" required value="<?php echo htmlspecialchars($nombre_contacto ?? ''); ?>" maxlength="50">
                </div>
                <div class="form-group">
                  <label for="apellido_contacto">Apellido del usuario:</label>
                  <input type="text" id="apellido_contacto" name="apellido_contacto" required value="<?php echo htmlspecialchars($apellido_contacto ?? ''); ?>" maxlength="50">
                </div>
              </div>
            </fieldset>
          </fieldset>

          <div class="terms-checkbox">
            <input type="checkbox" id="terms" name="terms" required <?php echo ($terms ?? false) ? 'checked' : ''; ?> />
            <label for="terms" class="labelterms">Acepto los <a class="linkredirect" href="/TalenteARpartners/html/terminosycondiciones-partners.html">terminos y condiciones</a>.</label>
          </div>
            <input class="sumbit" type="submit" value="Registrarse">

            <?php if (isset($error)): ?>
                <p style="color: red; font-weight: bold; margin-top: 15px;"><?php echo $error; ?></p>
            <?php endif; ?>
            
              <p>¿Ya tienes una cuenta? <a class="linkredirect" href="loginpartners.php">Iniciar sesión como agencia</a></p>
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
        <a href="https://www.facebook.com/profile.php?id=61553049380666"
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
</body>
  <script>
    function goBack() {
    window.history.back();
    }
  </script>
</html>