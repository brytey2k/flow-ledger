import TomSelect from 'tom-select';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-tom-select]').forEach((el) => {
        new TomSelect(el, {
            plugins: el.multiple ? ['remove_button'] : [],
            placeholder: el.dataset.tomSelectPlaceholder || 'Search...',
            maxOptions: null,
            closeAfterSelect: false,
        });
    });
});

const lightboxTriggers = document.querySelectorAll('.glightbox');

if (lightboxTriggers.length) {
    Promise.all([import('glightbox'), import('glightbox/dist/css/glightbox.min.css')]).then(([{ default: GLightbox }]) => {
        GLightbox({
            selector: '.glightbox',
            touchNavigation: true,
            loop: false,
        });
    });
}

const bentoCells = document.querySelectorAll('.bento .cell');

if (bentoCells.length && 'IntersectionObserver' in window) {
    const bentoObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    bentoObserver.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.15 },
    );

    bentoCells.forEach((cell, index) => {
        cell.style.setProperty('--i', String(index % 8));
        bentoObserver.observe(cell);
    });
} else {
    bentoCells.forEach((cell) => cell.classList.add('is-visible'));
}
