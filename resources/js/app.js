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
