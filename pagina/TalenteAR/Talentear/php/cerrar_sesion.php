<?php
// 1. Asigna un nombre de sesión único para Talentear
session_name("TALENTEAR_SESION");
session_start();

// 2. Destruye la sesión (solo la de Talentear)
session_destroy();

// 3. Redirige
header("Location: ../../TalenteAR/html/index.html");
exit();
?>