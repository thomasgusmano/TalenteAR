window.addEventListener("load", function () {
  const imagen = document.querySelector(".mi-imagen");
  imagen.classList.add("mover-derecha");
  imagen.addEventListener("animationiteration", function () {
    imagen.classList.remove("mover-derecha");
  });
});
