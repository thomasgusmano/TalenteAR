<!DOCTYPE html>
<html>
<head>
    <title>TalenteAR - Registrarse</title>
    <link rel="stylesheet" type="text/css" href="../../TalenteAR/css/error.css"/>
    <link rel="stylesheet" type="text/css" href="../css/sticky.css"/>
    <meta charset="UTF-8"/>
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
        No soy una empresa
      </a>
    </div>
  </div>  
    <h1>LO SENTIMOS... PARECE QUE HA OCURRIDO UN ERROR.</h1>
</body>
</html>

<?php
$error_code = isset($_GET['code']) ? $_GET['code'] : 500;

switch ($error_code) {
    case 401:
        http_response_code(401);
        $error_message = 
        "<p class='error'>Error 401: Se requiere iniciar sesión.
        <br> Seguramente entraste sin primero haber iniciado tu sesión, para iniciar sesión haz clic <a href='loginempresas.php'>aquí.</a> 
        <br> Si el problema persiste, <a href='contactar(noexistetodavia).php'>CONTACTANOS.</a></p>";
        break;
    case 404:
        http_response_code(404);
        $error_message = "<p class='error'>Error 404: La página que buscas no se pudo encontrar.
        <br> Seguramente especificaste mal la URL o hemos quitado la pagina que estabas buscando.
        <br> Si el problema persiste, <a href='contactar(noexistetodavia).php'>CONTACTANOS.</a></p>";
        break;
    default:
        http_response_code(500);
        $error_message = "<p class='error'>Error 500: Ha ocurrido un error interno del servidor.";
}

echo $error_message;
?>
<h1 class="last-element">Aquí puedes <a href="../html/index.html">volver al inicio.</a></h1>
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
            <a href="/TalenteARpartners/html/terminosycondiciones-empresas.html">Términos y Condiciones</a><a class="guion-footer">-</a>
            <a href="empresas.html">Política de Privacidad</a><a class="guion-footer">-</a>
            <a href="empresas.html">Dirección física</a>
        </div>
        <div class="footer-text">TalenteAR® - Todos los derechos reservados</div>
    </footer>
<script>
    function goBack() {
        window.history.back();
    }
</script>
