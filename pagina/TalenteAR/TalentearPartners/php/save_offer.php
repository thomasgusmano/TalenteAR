
<!DOCTYPE html>
<html>
<head>
    <title>Bienvenido</title>
    <link rel="stylesheet" type="text/css" href="../css/home-usuarios.css"/>
    <link rel="stylesheet" type="text/css" href="../css/sticky.css"/>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body>
<div class="navbar">
<div style="margin-left: 80px;" class="navbar-title">
      <div class="imagenesflechas">
        <a href="../php/index-logged.php" class="flecha-link" onclick="goBack()">
          <img class="flecha" src="../../imagenes/flecha.png" alt="volver atrás"> 
          <img class="flecha-hover" src="../../imagenes/flecharellena.png" alt="volver atrás">
        </a>
      </div>
        <a href="index-logged.php" class="navbar-title">
          <img class="maleta" src="../../imagenes/logotalentear.png" alt="">
          <h2 class="title">TalenteAR partners</h2>
        </a>
    </div>
        <a href="cerrar_sesion.php" class="box redirect">
            Cerrar sesión
        </a>
    </div>
<?php
session_name("PARTNERS_SESION");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_offer"])) {

    if (isset($_SESSION["email_agencia"])) {

        $host = "localhost";
        $username = "root";
        $password = "";
        $database = "agenciatrabajo";

        $conn = mysqli_connect($host, $username, $password, $database);
        
        if (!$conn) {

            die("Error al conectar a la base de talentos: " . mysqli_connect_error());

        }

        $email_agencia = $_SESSION["email_agencia"];
        $query_user_id = "SELECT id_agencia FROM agencias WHERE email_agencia = '$email_agencia'";
        $result = mysqli_query($conn, $query_user_id);

        if (mysqli_num_rows($result) > 0) {

            $row = mysqli_fetch_assoc($result);
            $agencia_id = $row["id_agencia"];

            $NombrePuesto = mysqli_real_escape_string($conn, $_POST["NombrePuesto"]);
            $DescripcionPuesto = mysqli_real_escape_string($conn, $_POST["DescripcionPuesto"]);
            $CantVacantes = mysqli_real_escape_string($conn, $_POST["CantVacantes"]);
            $NivelExperiencia = mysqli_real_escape_string($conn, $_POST["NivelExperiencia"]);

            $query = "INSERT INTO castings (agencia_id, NombrePuesto, DescripcionPuesto, CantVacantes, NivelExperiencia) VALUES ('$agencia_id', '$NombrePuesto', '$DescripcionPuesto', '$CantVacantes', '$NivelExperiencia')";

            if (mysqli_query($conn, $query)) {

                echo "<h1 style='color: white; text-align:center; font-size: 40px'>Oferta de empleo publicada correctamente.</h1>";

            } else {

                echo "Error al publicar la oferta de empleo: " . mysqli_error($conn);

            }} else {

            echo "Error: No se pudo encontrar el usuario.";

        }

        mysqli_close($conn);

    } else {

        header("Location: bienvenido.php"); 
        exit();

    }} else {

    header("Location: bienvenido.php"); 
    exit();

}
?>
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
        <a href="../../TalenteAR partners/html/terminosycondiciones-partners.html">Términos y Condiciones -</a>
        <a href="../../TalenteAR partners/html/politic.html">Política de Privacidad -</a>
        <a href="../../TalenteAR partners/html/address.html">Dirección física</a>
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