const textElement = document.getElementById("typing-text");
const words = [
  "Encuentra Tu Próximo Trabajo",
  "Conecta Con Oportunidades",
  "Impulsa Tu Carrera",
  "Nuevas Oportunidades Laborales",
  "Desarrolla Tu Futuro Profesional",
  "Mejora Tu Vida Profesional",
  "Construye Tu Futuro Laboral",
  "Explora Oportunidades Laborales",
];

let wordIndex = 0;
let letterIndex = 0;

function typeText() {
  if (letterIndex <= words[wordIndex].length) {
    textElement.textContent = words[wordIndex].substring(0, letterIndex);
    letterIndex++;
    setTimeout(typeText, 50);
  } else {
    setTimeout(eraseText, 1000);
  }
}

function eraseText() {
  if (letterIndex >= 0) {
    textElement.textContent = words[wordIndex].substring(0, letterIndex);
    letterIndex--;
    setTimeout(eraseText, 20);
  } else {
    wordIndex++;
    if (wordIndex === words.length) {
      setTimeout(() => {
        wordIndex = 0;
        typeText();
      }, 5000);
    } else {
      setTimeout(typeText, 50);
    }
  }
}
typeText();
