document.addEventListener('DOMContentLoaded', () => {
  const success = document.getElementById('successMessageMain');
  if (success) {
    success.hidden = false;
    success.style.display = 'none';
  }

  const items = document.querySelectorAll('.reveal');
  if (!('IntersectionObserver' in window)) {
    items.forEach((item) => item.classList.add('visible'));
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  items.forEach((item) => observer.observe(item));

  document.querySelectorAll('.fit-card button').forEach((button) => {
    button.addEventListener('click', () => {
      const card = button.closest('.fit-card');
      const answer = document.getElementById(button.getAttribute('aria-controls'));
      const willOpen = button.getAttribute('aria-expanded') !== 'true';

      document.querySelectorAll('.fit-card').forEach((otherCard) => {
        const otherButton = otherCard.querySelector('button');
        const otherAnswer = document.getElementById(otherButton.getAttribute('aria-controls'));
        otherCard.classList.remove('is-open');
        otherButton.setAttribute('aria-expanded', 'false');
        otherAnswer.hidden = true;
      });

      if (willOpen) {
        card.classList.add('is-open');
        button.setAttribute('aria-expanded', 'true');
        answer.hidden = false;
      }
    });
  });
});
