// public/assets/js/print-button.js — "Imprimir / salvar PDF" on the invoice page (the CSP blocks inline scripts).
document.querySelectorAll('[data-print]').forEach((button) => {
  button.addEventListener('click', () => window.print());
});
