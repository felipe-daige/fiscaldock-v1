/**
 * Meu Plano - page script
 * Renders CSS bar chart from data-attributes.
 */
window.initPlano = function() {
    var bars = document.querySelectorAll('[data-bar-index]');
    bars.forEach(function(bar, i) {
        var targetWidth = bar.style.width;
        bar.style.width = '0%';
        setTimeout(function() {
            bar.style.width = targetWidth;
        }, 100 + (i * 80));
    });
};
