const menu = document.querySelector('.menu');
const nav = document.querySelector('.nav-wrap nav');
const navLinks = [...document.querySelectorAll('.nav-wrap nav a')];
const progress = document.querySelector('.scroll-progress span');
const backToTop = document.querySelector('.back-to-top');
const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

const closeMenu = () => {
  nav?.classList.remove('open');
  menu?.setAttribute('aria-expanded', 'false');
  document.body.classList.remove('menu-open');
};

menu?.addEventListener('click', () => {
  const open = nav.classList.toggle('open');
  menu.setAttribute('aria-expanded', String(open));
  document.body.classList.toggle('menu-open', open);
});

navLinks.forEach(link => link.addEventListener('click', closeMenu));
document.addEventListener('keydown', event => event.key === 'Escape' && closeMenu());
document.addEventListener('click', event => {
  if (!nav?.classList.contains('open') || nav.contains(event.target) || menu?.contains(event.target)) return;
  closeMenu();
});

const sectionLinks = navLinks.map(link => ({ link, section: document.querySelector(link.hash) })).filter(item => item.section);
const activateNav = () => {
  const marker = scrollY + 130;
  let active = sectionLinks[0];
  sectionLinks.forEach(item => { if (item.section.offsetTop <= marker) active = item; });
  sectionLinks.forEach(item => {
    const selected = item === active;
    item.link.classList.toggle('active', selected);
    selected ? item.link.setAttribute('aria-current', 'location') : item.link.removeAttribute('aria-current');
  });
};

const updateScrollUI = () => {
  const available = document.documentElement.scrollHeight - innerHeight;
  if (progress) progress.style.transform = `scaleX(${available ? scrollY / available : 0})`;
  backToTop?.classList.toggle('visible', scrollY > 650);
  activateNav();
};

addEventListener('scroll', updateScrollUI, { passive: true });
addEventListener('resize', updateScrollUI);
addEventListener('resize', () => { if (innerWidth > 1000) closeMenu(); });
updateScrollUI();

backToTop?.addEventListener('click', () => scrollTo({ top: 0, behavior: reducedMotion ? 'auto' : 'smooth' }));

document.querySelectorAll('.faq details').forEach(item => item.addEventListener('toggle', () => {
  if (!item.open) return;
  document.querySelectorAll('.faq details[open]').forEach(other => { if (other !== item) other.open = false; });
}));

document.querySelectorAll('.chapter-accordion details').forEach(item => item.addEventListener('toggle', () => {
  if (!item.open) return;
  document.querySelectorAll('.chapter-accordion details[open]').forEach(other => { if (other !== item) other.open = false; });
}));

const revealTargets = document.querySelectorAll('.section-title, .result-card, .mini-card, .steps article, .media-card, .chapter-grid article, .story-grid > *, .teacher-grid article, .fit article');
if (!reducedMotion && 'IntersectionObserver' in window) {
  revealTargets.forEach(element => element.classList.add('reveal'));
  const observer = new IntersectionObserver(entries => entries.forEach(entry => {
    if (entry.isIntersecting) { entry.target.classList.add('revealed'); observer.unobserve(entry.target); }
  }), { threshold: .12, rootMargin: '0px 0px -35px' });
  revealTargets.forEach(element => observer.observe(element));
}

const participantCarousels = [...document.querySelectorAll('.coded-results-cards')];
participantCarousels.forEach(carousel => {
  const cards = [...carousel.querySelectorAll('.coded-result-card')];
  if (cards.length < 2) return;
  let activeIndex = 0;
  let timer;
  const goToCard = index => {
    activeIndex = (index + cards.length) % cards.length;
    const target = cards[activeIndex];
    carousel.scrollTo({ left: target.offsetLeft - carousel.offsetLeft, behavior: reducedMotion ? 'auto' : 'smooth' });
  };
  const stop = () => { if (timer) clearInterval(timer); timer = undefined; };
  const start = () => {
    stop();
    if (innerWidth > 760 || reducedMotion || carousel.offsetParent === null) return;
    timer = setInterval(() => goToCard(activeIndex + 1), 3600);
  };
  carousel.addEventListener('pointerdown', stop, { passive: true });
  carousel.addEventListener('pointerup', () => setTimeout(start, 4500), { passive: true });
  carousel.addEventListener('focusin', stop);
  carousel.addEventListener('focusout', start);
  addEventListener('resize', start);
  document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());
  start();
});

document.querySelectorAll('.story-carousel').forEach(carousel => {
  const slides = [...carousel.querySelectorAll('[data-story-slide]')];
  const dots = [...carousel.querySelectorAll('[data-story-dot]')];
  if (slides.length < 2) return;
  let active = 0;
  let timer;
  const show = index => {
    active = (index + slides.length) % slides.length;
    slides.forEach((slide, i) => {
      const selected = i === active;
      slide.hidden = !selected;
      slide.classList.toggle('is-active', selected);
    });
    dots.forEach((dot, i) => {
      const selected = i === active;
      dot.classList.toggle('is-active', selected);
      dot.setAttribute('aria-selected', String(selected));
    });
  };
  const stop = () => { if (timer) clearInterval(timer); timer = undefined; };
  const start = () => {
    stop();
    if (reducedMotion || document.hidden || carousel.offsetParent === null) return;
    timer = setInterval(() => show(active + 1), 5600);
  };
  carousel.querySelector('[data-story-prev]')?.addEventListener('click', () => { show(active - 1); start(); });
  carousel.querySelector('[data-story-next]')?.addEventListener('click', () => { show(active + 1); start(); });
  dots.forEach(dot => dot.addEventListener('click', () => { show(Number(dot.dataset.storyDot)); start(); }));
  carousel.addEventListener('mouseenter', stop);
  carousel.addEventListener('mouseleave', start);
  carousel.addEventListener('focusin', stop);
  carousel.addEventListener('focusout', start);
  document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());
  show(0);
  start();
});

const enrollmentModal = document.querySelector('[data-enrollment-modal]');
const enrollmentDialog = enrollmentModal?.querySelector('.enrollment-dialog');
const enrollmentForm = enrollmentModal?.querySelector('.enrollment-form');
const enrollmentSuccess = enrollmentModal?.querySelector('.enrollment-success');
const enrollmentError = enrollmentModal?.querySelector('.enrollment-error');
let enrollmentTrigger = null;
let enrollmentCloseTimer = null;

const openEnrollment = trigger => {
  if (!enrollmentModal) return;
  if (enrollmentCloseTimer) clearTimeout(enrollmentCloseTimer);
  enrollmentTrigger = trigger;
  enrollmentModal.hidden = false;
  document.body.classList.add('enrollment-opened');
  requestAnimationFrame(() => {
    enrollmentModal.classList.add('is-open');
    enrollmentDialog?.focus();
    enrollmentForm?.querySelector('input')?.focus();
  });
};

const closeEnrollment = () => {
  if (!enrollmentModal) return;
  enrollmentModal.classList.remove('is-open');
  document.body.classList.remove('enrollment-opened');
  enrollmentCloseTimer = setTimeout(() => {
    enrollmentModal.hidden = true;
    enrollmentCloseTimer = null;
  }, reducedMotion ? 0 : 220);
  enrollmentTrigger?.focus();
};

document.querySelectorAll('[data-open-enrollment]').forEach(button => button.addEventListener('click', () => openEnrollment(button)));
enrollmentModal?.querySelectorAll('[data-close-enrollment]').forEach(button => button.addEventListener('click', closeEnrollment));
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && enrollmentModal?.classList.contains('is-open')) closeEnrollment();
});

enrollmentForm?.addEventListener('submit', event => {
  event.preventDefault();
  const phone = enrollmentForm.elements.phone;
  phone.value = phone.value.replace(/[\s-]/g, '');
  if (!enrollmentForm.checkValidity()) {
    enrollmentError.textContent = 'لطفاً همه اطلاعات را به‌درستی تکمیل کنید.';
    enrollmentForm.reportValidity();
    return;
  }
  enrollmentError.textContent = '';
  enrollmentForm.hidden = true;
  enrollmentSuccess.hidden = false;
});
