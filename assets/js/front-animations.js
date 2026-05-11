document.addEventListener('DOMContentLoaded', function () {
  document.body.classList.add('front-motion-ready');

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const navbar = document.querySelector('.navbar');

  if (navbar) {
    const syncNavbarState = function () {
      navbar.classList.toggle('is-scrolled', window.scrollY > 12);
    };

    syncNavbarState();
    window.addEventListener('scroll', syncNavbarState, { passive: true });
  }

  const revealTargets = Array.from(document.querySelectorAll('[data-reveal]'));

  document.querySelectorAll('[data-stagger-children]').forEach(function (group) {
    Array.from(group.children).forEach(function (child, index) {
      if (!child.hasAttribute('data-reveal')) {
        child.setAttribute('data-reveal', 'up');
      }

      child.style.setProperty('--reveal-delay', `${index * 90}ms`);
      if (!revealTargets.includes(child)) {
        revealTargets.push(child);
      }
    });
  });

  if (prefersReducedMotion) {
    revealTargets.forEach(function (element) {
      element.classList.add('is-visible');
    });
  } else if ('IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver(function (entries, observer) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }

        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, {
      threshold: 0.14,
      rootMargin: '0px 0px -8% 0px'
    });

    revealTargets.forEach(function (element) {
      revealObserver.observe(element);
    });
  } else {
    revealTargets.forEach(function (element) {
      element.classList.add('is-visible');
    });
  }

  if (!prefersReducedMotion) {
    document.querySelectorAll('[data-tilt-card]').forEach(function (card) {
      card.addEventListener('pointermove', function (event) {
        const rect = card.getBoundingClientRect();
        const px = (event.clientX - rect.left) / rect.width;
        const py = (event.clientY - rect.top) / rect.height;
        const rotateY = (px - 0.5) * 7;
        const rotateX = (0.5 - py) * 7;

        card.style.setProperty('--mx', `${px * 100}%`);
        card.style.setProperty('--my', `${py * 100}%`);
        card.style.transform = `perspective(900px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-6px)`;
      });

      card.addEventListener('pointerleave', function () {
        card.style.removeProperty('transform');
      });
    });
  }
});
