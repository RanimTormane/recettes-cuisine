// Cache l'écran de chargement une fois la page chargée
window.addEventListener('load', function () {
    var spinner = document.getElementById('spinner');
    if (spinner) { spinner.classList.add('is-hidden'); }
});

// Bouton « retour en haut »
var toTop = document.getElementById('back-to-top');
if (toTop) {
    window.addEventListener('scroll', function () {
        toTop.classList.toggle('is-visible', window.scrollY > 300);
    });
    toTop.addEventListener('click', function (e) {
        e.preventDefault();
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    });
}
