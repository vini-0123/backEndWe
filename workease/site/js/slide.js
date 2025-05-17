// Variáveis para slider
let slideIndex = 1;
        
// Inicializar o slider
window.onload = function() {
showSlides(slideIndex);
            
// Mudança automática de slides a cada 5 segundos
setInterval(function() {
    plusSlides(1);
    }, 5000);
};
        
// Próximo/anterior controles
function plusSlides(n) {
    showSlides(slideIndex += n);
}
        
// Controles de miniaturas de imagens
function currentSlide(n) {
    showSlides(slideIndex = n);
}
        
function showSlides(n) {
    let i;
    let slides = document.getElementsByClassName("slide");
    let dots = document.getElementsByClassName("dot");
            
// Circular: se passou do último, volta para o primeiro
if (n > slides.length) {slideIndex = 1}
            
// Circular: se vai antes do primeiro, vai para o último
if (n < 1) {slideIndex = slides.length}
            
// Esconde todos os slides
for (i = 0; i < slides.length; i++) {
    slides[i].classList.remove("active");
}
            
// Remove a classe "active" de todos os pontos
for (i = 0; i < dots.length; i++) {
    dots[i].className = dots[i].className.replace(" active", "");
}
            
// Mostra o slide atual
slides[slideIndex-1].classList.add("active");
            
// Destaca o ponto atual
dots[slideIndex-1].className += " active";
}