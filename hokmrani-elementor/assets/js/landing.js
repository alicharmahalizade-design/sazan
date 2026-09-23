/*!
 * Hokmrani Landing for Elementor – front-end behaviour.
 * Same behaviour as the original static page; every binding is idempotent so the
 * widgets also work when Elementor re-renders them inside the editor.
 */
(function () {
  'use strict';

  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var body = document.body;
  var settings = window.HKL || {};

  var isEditor = function () { return body.classList.contains('elementor-editor-active'); };
  var once = function (element, key) {
    var flag = 'hkl' + key;
    if (element.dataset[flag]) return false;
    element.dataset[flag] = '1';
    return true;
  };
  var all = function (selector, root) { return Array.prototype.slice.call((root || document).querySelectorAll(selector)); };

  /* Pages that use the widgets without the landing canvas (or a fresh editor drop). */
  var ensureBodyClasses = function () {
    if (document.querySelector('[data-widget_type^="hk-"]')) body.classList.add('hk-page', 'hk-dv2', 'is-version-4');
  };

  /* ---------------------------------------------------------------- Menu */
  var getMenu = function () { return document.querySelector('.menu'); };
  var getNav = function () { return document.querySelector('.nav-wrap nav'); };

  var closeMenu = function () {
    var nav = getNav();
    var menu = getMenu();
    if (nav) nav.classList.remove('open');
    if (menu) menu.setAttribute('aria-expanded', 'false');
    body.classList.remove('menu-open');
  };

  var initMenu = function () {
    all('.menu').forEach(function (menu) {
      if (!once(menu, 'Menu')) return;
      menu.addEventListener('click', function () {
        var nav = getNav();
        if (!nav) return;
        var open = nav.classList.toggle('open');
        menu.setAttribute('aria-expanded', String(open));
        body.classList.toggle('menu-open', open);
      });
    });
    all('.nav-wrap nav a').forEach(function (link) {
      if (once(link, 'NavLink')) link.addEventListener('click', closeMenu);
    });
  };

  /* ------------------------------------------------ Scroll progress & nav */
  var sectionLinks = [];
  var refreshSectionLinks = function () {
    sectionLinks = all('.nav-wrap nav a').map(function (link) {
      var section = null;
      if (link.hash) {
        try { section = document.querySelector(link.hash); } catch (e) { section = null; }
      }
      return { link: link, section: section };
    }).filter(function (item) { return item.section; });
  };

  var activateNav = function () {
    if (!sectionLinks.length) return;
    var marker = window.scrollY + 130;
    var active = sectionLinks[0];
    sectionLinks.forEach(function (item) { if (item.section.offsetTop <= marker) active = item; });
    sectionLinks.forEach(function (item) {
      var selected = item === active;
      item.link.classList.toggle('active', selected);
      if (selected) item.link.setAttribute('aria-current', 'location');
      else item.link.removeAttribute('aria-current');
    });
  };

  var updateScrollUI = function () {
    var progress = document.querySelector('.scroll-progress span');
    var backToTop = document.querySelector('.back-to-top');
    var available = document.documentElement.scrollHeight - window.innerHeight;
    if (progress) progress.style.transform = 'scaleX(' + (available ? window.scrollY / available : 0) + ')';
    if (backToTop) backToTop.classList.toggle('visible', window.scrollY > 650);
    activateNav();
  };

  var initBackToTop = function () {
    all('.back-to-top').forEach(function (button) {
      if (!once(button, 'Top')) return;
      button.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reducedMotion ? 'auto' : 'smooth' }); });
    });
  };

  /* ------------------------------------------------------ Accordions */
  var exclusiveDetails = function (selector) {
    all(selector).forEach(function (item) {
      if (!once(item, 'Details')) return;
      item.addEventListener('toggle', function () {
        if (!item.open) return;
        all(selector + '[open]').forEach(function (other) { if (other !== item) other.open = false; });
      });
    });
  };

  /* ------------------------------------------------------ Reveal */
  var revealObserver = null;
  var initReveal = function () {
    if (reducedMotion || isEditor() || !('IntersectionObserver' in window)) return;
    if (!revealObserver) {
      revealObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) { entry.target.classList.add('revealed'); revealObserver.unobserve(entry.target); }
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -35px' });
    }
    all('.section-title, .result-card, .mini-card, .steps article, .media-card, .chapter-grid article, .story-grid > *, .teacher-grid article, .fit article').forEach(function (element) {
      if (!once(element, 'Reveal')) return;
      element.classList.add('reveal');
      revealObserver.observe(element);
    });
  };

  /* ------------------------------------------- Participant cards (mobile) */
  var initParticipantCarousels = function () {
    all('.coded-results-cards').forEach(function (carousel) {
      var cards = all('.coded-result-card', carousel);
      if (cards.length < 2 || !once(carousel, 'Cards')) return;
      var activeIndex = 0;
      var timer;
      var goToCard = function (index) {
        activeIndex = (index + cards.length) % cards.length;
        var target = cards[activeIndex];
        carousel.scrollTo({ left: target.offsetLeft - carousel.offsetLeft, behavior: reducedMotion ? 'auto' : 'smooth' });
      };
      var stop = function () { if (timer) clearInterval(timer); timer = undefined; };
      var start = function () {
        stop();
        if (window.innerWidth > 760 || reducedMotion || carousel.offsetParent === null) return;
        timer = setInterval(function () { goToCard(activeIndex + 1); }, 3600);
      };
      carousel.addEventListener('pointerdown', stop, { passive: true });
      carousel.addEventListener('pointerup', function () { setTimeout(start, 4500); }, { passive: true });
      carousel.addEventListener('focusin', stop);
      carousel.addEventListener('focusout', start);
      window.addEventListener('resize', start);
      document.addEventListener('visibilitychange', function () { if (document.hidden) stop(); else start(); });
      start();
    });
  };

  /* ------------------------------------------------------ Story carousel */
  var initStoryCarousels = function () {
    all('.story-carousel').forEach(function (carousel) {
      var slides = all('[data-story-slide]', carousel);
      var dots = all('[data-story-dot]', carousel);
      if (slides.length < 2 || !once(carousel, 'Stories')) return;
      var active = 0;
      var timer;
      var show = function (index) {
        active = (index + slides.length) % slides.length;
        slides.forEach(function (slide, i) {
          var selected = i === active;
          slide.hidden = !selected;
          slide.classList.toggle('is-active', selected);
        });
        dots.forEach(function (dot, i) {
          var selected = i === active;
          dot.classList.toggle('is-active', selected);
          dot.setAttribute('aria-selected', String(selected));
        });
      };
      var stop = function () { if (timer) clearInterval(timer); timer = undefined; };
      var start = function () {
        stop();
        if (reducedMotion || document.hidden || carousel.offsetParent === null || carousel.getAttribute('data-autoplay') === 'no') return;
        timer = setInterval(function () { show(active + 1); }, Number(carousel.getAttribute('data-interval')) || 5600);
      };
      var prev = carousel.querySelector('[data-story-prev]');
      var next = carousel.querySelector('[data-story-next]');
      if (prev) prev.addEventListener('click', function () { show(active - 1); start(); });
      if (next) next.addEventListener('click', function () { show(active + 1); start(); });
      dots.forEach(function (dot) { dot.addEventListener('click', function () { show(Number(dot.dataset.storyDot)); start(); }); });
      carousel.addEventListener('mouseenter', stop);
      carousel.addEventListener('mouseleave', start);
      carousel.addEventListener('focusin', stop);
      carousel.addEventListener('focusout', start);
      document.addEventListener('visibilitychange', function () { if (document.hidden) stop(); else start(); });
      show(0);
      start();
    });
  };

  /* ------------------------------------------------------ Video links */
  var initVideos = function () {
    all('[data-video]').forEach(function (button) {
      if (!once(button, 'Video')) return;
      button.addEventListener('click', function () {
        var url = button.getAttribute('data-video');
        if (url) window.open(url, '_blank', 'noopener');
      });
    });
  };

  /* ------------------------------------------------------ Enrollment modal */
  var enrollmentTrigger = null;
  var enrollmentCloseTimer = null;
  var getModal = function () { return document.querySelector('[data-enrollment-modal]'); };

  var openEnrollment = function (trigger) {
    var modal = getModal();
    if (!modal) return;
    var dialog = modal.querySelector('.enrollment-dialog');
    var form = modal.querySelector('.enrollment-form');
    if (enrollmentCloseTimer) clearTimeout(enrollmentCloseTimer);
    enrollmentTrigger = trigger;
    modal.hidden = false;
    body.classList.add('enrollment-opened');
    requestAnimationFrame(function () {
      modal.classList.add('is-open');
      if (dialog) dialog.focus();
      var input = form && form.querySelector('input');
      if (input) input.focus();
    });
  };

  var closeEnrollment = function () {
    var modal = getModal();
    if (!modal) return;
    modal.classList.remove('is-open');
    body.classList.remove('enrollment-opened');
    enrollmentCloseTimer = setTimeout(function () {
      modal.hidden = true;
      enrollmentCloseTimer = null;
    }, reducedMotion ? 0 : 220);
    if (enrollmentTrigger) enrollmentTrigger.focus();
  };

  var sendLead = function (form) {
    if (!settings.ajaxUrl || !window.fetch || !window.FormData) return Promise.resolve();
    var data = new FormData(form);
    data.append('action', 'hkl_submit_lead');
    data.append('page_url', window.location.href);
    return window.fetch(settings.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data }).catch(function () {});
  };

  var initEnrollment = function () {
    all('[data-open-enrollment]').forEach(function (button) {
      if (once(button, 'Open')) button.addEventListener('click', function () { openEnrollment(button); });
    });
    all('[data-close-enrollment]').forEach(function (button) {
      if (once(button, 'Close')) button.addEventListener('click', closeEnrollment);
    });
    all('.enrollment-form').forEach(function (form) {
      if (!once(form, 'Form')) return;
      var modal = form.closest('[data-enrollment-modal]') || document;
      var success = modal.querySelector('.enrollment-success');
      var error = modal.querySelector('.enrollment-error');
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        var phone = form.elements.phone;
        phone.value = phone.value.replace(/[\s-]/g, '');
        if (!form.checkValidity()) {
          if (error) error.textContent = form.getAttribute('data-error') || 'لطفاً همه اطلاعات را به‌درستی تکمیل کنید.';
          form.reportValidity();
          return;
        }
        if (error) error.textContent = '';
        var sent = sendLead(form);
        var redirect = form.getAttribute('data-redirect');
        if (redirect && !isEditor()) sent.then(function () { window.location.href = redirect; });
        form.hidden = true;
        if (success) success.hidden = false;
      });
    });
  };

  /* ------------------------------------------------------ Boot */
  var init = function () {
    ensureBodyClasses();
    initMenu();
    initBackToTop();
    exclusiveDetails('.faq details');
    exclusiveDetails('.chapter-accordion details');
    initReveal();
    initParticipantCarousels();
    initStoryCarousels();
    initVideos();
    initEnrollment();
    refreshSectionLinks();
    updateScrollUI();
  };

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    closeMenu();
    var modal = getModal();
    if (modal && modal.classList.contains('is-open')) closeEnrollment();
  });
  document.addEventListener('click', function (event) {
    var nav = getNav();
    var menu = getMenu();
    if (!nav || !nav.classList.contains('open') || nav.contains(event.target) || (menu && menu.contains(event.target))) return;
    closeMenu();
  });
  window.addEventListener('scroll', updateScrollUI, { passive: true });
  window.addEventListener('resize', updateScrollUI);
  window.addEventListener('resize', function () { if (window.innerWidth > 1000) closeMenu(); });

  init();

  /* Elementor editor / dynamic rendering. */
  var hookElementor = function () {
    if (!window.elementorFrontend || !window.elementorFrontend.hooks) return;
    window.elementorFrontend.hooks.addAction('frontend/element_ready/global', function () { init(); });
  };
  if (window.elementorFrontend && window.elementorFrontend.hooks) hookElementor();
  else if (window.jQuery) window.jQuery(window).on('elementor/frontend/init', hookElementor);
})();
