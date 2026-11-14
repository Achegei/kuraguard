document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('kg-mobile-menu-toggle');
  const menu = document.getElementById('kg-public-links');

  if (!toggle || !menu) return;

  const closeMenu = () => {
    menu.classList.remove('kg-menu-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open navigation menu');
  };

  toggle.addEventListener('click', () => {
    const isOpen = menu.classList.toggle('kg-menu-open');
    toggle.setAttribute('aria-expanded', String(isOpen));
    toggle.setAttribute(
      'aria-label',
      isOpen ? 'Close navigation menu' : 'Open navigation menu'
    );
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeMenu();
    }
  });

  document.addEventListener('click', (event) => {
    if (!toggle.contains(event.target) && !menu.contains(event.target)) {
      closeMenu();
    }
  });

  menu.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', closeMenu);
  });

  window.matchMedia('(min-width: 1101px)').addEventListener('change', closeMenu);
});
