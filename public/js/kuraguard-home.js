(() => {
  const container = document.querySelector('[data-kg-slideshow]');
  if (!container) return;
  const slides = Array.from(container.querySelectorAll('[data-kg-slide]'));
  const counter = document.querySelector('[data-kg-counter]');
  const pause = document.querySelector('[data-kg-pause]');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let index = 0;
  let playing = !reduceMotion;
  let timer;
  function show(next) {
    index = (next + slides.length) % slides.length;
    slides.forEach((slide, i) => {
      slide.classList.toggle('is-active', i === index);
      slide.setAttribute('aria-hidden', i === index ? 'false' : 'true');
    });
    if (counter) counter.textContent = `${String(index + 1).padStart(2, '0')} / ${String(slides.length).padStart(2, '0')}`;
  }
  function schedule() { clearInterval(timer); if (playing) timer = setInterval(() => show(index + 1), 6500); }
  document.querySelector('[data-kg-prev]')?.addEventListener('click', () => { show(index - 1); schedule(); });
  document.querySelector('[data-kg-next]')?.addEventListener('click', () => { show(index + 1); schedule(); });
  pause?.addEventListener('click', () => { playing = !playing; pause.textContent = playing ? 'Ⅱ' : '▶'; pause.setAttribute('aria-label', playing ? 'Pause slideshow' : 'Play slideshow'); schedule(); });
  show(0); schedule();
})();
