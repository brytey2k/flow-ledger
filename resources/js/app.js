import TomSelect from 'tom-select';

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
