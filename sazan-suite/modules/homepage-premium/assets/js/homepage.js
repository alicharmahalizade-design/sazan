(() => {
  "use strict";

  if (window.__sazanPremiumHomepageStarted) return;
  window.__sazanPremiumHomepageStarted = true;

  const $ = (selector, scope = document) => scope.querySelector(selector);
  const $$ = (selector, scope = document) => [...scope.querySelectorAll(selector)];
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

  const searchToggle = $(".search-toggle");
  const searchPanel = $(".search-panel");
  const menuToggle = $(".menu-toggle");
  const mobileNav = $(".mobile-nav");

  const togglePanel = (button, panel, state) => {
    panel?.classList.toggle("open", state);
    button?.setAttribute("aria-expanded", String(state));
  };

  searchToggle?.addEventListener("click", () => {
    const open = !searchPanel.classList.contains("open");
    togglePanel(searchToggle, searchPanel, open);
    togglePanel(menuToggle, mobileNav, false);
    if (open) setTimeout(() => $("input", searchPanel)?.focus(), 120);
  });

  menuToggle?.addEventListener("click", () => {
    const open = !mobileNav.classList.contains("open");
    togglePanel(menuToggle, mobileNav, open);
    togglePanel(searchToggle, searchPanel, false);
  });

  $$("a", mobileNav).forEach((link) => {
    link.addEventListener("click", () => togglePanel(menuToggle, mobileNav, false));
  });

  document.addEventListener("click", (event) => {
    if (!event.target.closest(".site-header")) {
      togglePanel(searchToggle, searchPanel, false);
      togglePanel(menuToggle, mobileNav, false);
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      togglePanel(searchToggle, searchPanel, false);
      togglePanel(menuToggle, mobileNav, false);
      searchToggle?.focus();
    }
  });

  searchPanel?.addEventListener("submit", (event) => {
    event.preventDefault();
    const query = $("input", searchPanel).value.trim();
    const target = /دوره|آموزش/.test(query) ? $("#courses") : $("#articles");
    target?.scrollIntoView({ behavior: "smooth" });
    togglePanel(searchToggle, searchPanel, false);
  });

  const homepageRoot = document.querySelector(".sazan-premium-homepage");
  const heroStage = $(".hero-stage");
  const assetsUrl = heroStage?.dataset.assetsUrl || homepageRoot?.dataset.assetsUrl || "assets/";
  const fallbackSlides = [
    {
      title: "دوره حکمرانی بر بازار",
      image: `${assetsUrl}images/حکمرانی-بر-بازار.webp`,
      alt: "تصویر دوره حکمرانی بر بازار",
      ribbon: "پرطرفدار",
    },
    {
      title: "زبان بدن",
      image: `${assetsUrl}images/زبان-بدن.webp`,
      alt: "تصویر دوره زبان بدن",
      ribbon: "پرطرفدار",
    },
    {
      title: "مذاکره کننده حرفه ای",
      image: `${assetsUrl}images/ChatGPT-Image-Aug-2-2026-07_30_24-PM.png`,
      alt: "تصویر دوره مذاکره کننده حرفه‌ای",
      ribbon: "پیشنهاد سازان",
    },
  ];
  let slides = fallbackSlides;
  if (heroStage?.dataset.slides) {
    try {
      const configuredSlides = JSON.parse(heroStage.dataset.slides);
      if (Array.isArray(configuredSlides) && configuredSlides.length) slides = configuredSlides;
    } catch (error) {
      slides = fallbackSlides;
    }
  }
  const heroImage = $(".hero-character");
  const heroCopy = $(".hero-copy");
  const heroTitle = $(".hero-copy h1, .hero-copy h2, .hero-copy h3, .hero-copy h4, .hero-copy h5, .hero-copy h6");
  const heroSummary = $(".hero-summary");
  const heroRibbon = $(".hero-ribbon");
  const heroPrimary = $(".primary-button");
  const heroSecondary = $(".secondary-button");
  const heroDots = $$(".hero-dots button");
  let currentSlide = 0;
  let heroTimer;
  const autoplayPauses = new Set();

  const showSlide = (index) => {
    currentSlide = (index + slides.length) % slides.length;
    const slide = slides[currentSlide];
    heroImage.classList.add("changing");
    heroCopy.classList.add("changing");
    window.setTimeout(() => {
      heroImage.src = slide.image;
      heroImage.alt = slide.alt;
      heroTitle.textContent = slide.title;
      heroRibbon.textContent = slide.ribbon;
      if (heroSummary && slide.summary) heroSummary.textContent = slide.summary;
      if (heroPrimary) {
        if (slide.primaryText) heroPrimary.firstChild.textContent = `${slide.primaryText} `;
        if (slide.primaryUrl) heroPrimary.href = slide.primaryUrl;
      }
      if (heroSecondary) {
        const secondaryText = [...heroSecondary.childNodes].find((node) => node.nodeType === Node.TEXT_NODE);
        if (secondaryText && slide.secondaryText) secondaryText.textContent = slide.secondaryText;
        if (slide.secondaryUrl) heroSecondary.href = slide.secondaryUrl;
      }
      heroDots.forEach((dot, dotIndex) => {
        const active = dotIndex === currentSlide;
        dot.classList.toggle("active", active);
        if (active) dot.setAttribute("aria-current", "true");
        else dot.removeAttribute("aria-current");
      });
      heroImage.classList.remove("changing");
      heroCopy.classList.remove("changing");
    }, 260);
  };

  const restartHeroTimer = () => {
    window.clearInterval(heroTimer);
    if (reduceMotion.matches || autoplayPauses.size) return;
    heroTimer = window.setInterval(() => showSlide(currentSlide + 1), 9000);
  };

  const setAutoplayPause = (reason, paused) => {
    if (paused) autoplayPauses.add(reason);
    else autoplayPauses.delete(reason);
    restartHeroTimer();
  };

  $(".hero-prev")?.addEventListener("click", () => { showSlide(currentSlide - 1); restartHeroTimer(); });
  $(".hero-next")?.addEventListener("click", () => { showSlide(currentSlide + 1); restartHeroTimer(); });
  heroDots[0]?.setAttribute("aria-current", "true");
  heroDots.forEach((dot, index) => dot.addEventListener("click", () => { showSlide(index); restartHeroTimer(); }));
  heroStage?.addEventListener("mouseenter", () => setAutoplayPause("hover", true));
  heroStage?.addEventListener("mouseleave", () => setAutoplayPause("hover", false));
  heroStage?.addEventListener("focusin", () => setAutoplayPause("focus", true));
  heroStage?.addEventListener("focusout", (event) => {
    if (!heroStage.contains(event.relatedTarget)) setAutoplayPause("focus", false);
  });
  heroStage?.addEventListener("keydown", (event) => {
    if (event.key === "ArrowLeft") { showSlide(currentSlide - 1); event.preventDefault(); }
    if (event.key === "ArrowRight") { showSlide(currentSlide + 1); event.preventDefault(); }
  });
  document.addEventListener("visibilitychange", () => setAutoplayPause("hidden", document.hidden));
  reduceMotion.addEventListener?.("change", restartHeroTimer);
  restartHeroTimer();

  const countdownNumbers = $$(".countdown b");
  let secondsLeft = Number(heroStage?.dataset.countdownSeconds) || ((((38 * 24 + 21) * 60 + 41) * 60) + 30);
  window.setInterval(() => {
    secondsLeft = Math.max(0, secondsLeft - 1);
    const days = Math.floor(secondsLeft / 86400);
    const hours = Math.floor((secondsLeft % 86400) / 3600);
    const minutes = Math.floor((secondsLeft % 3600) / 60);
    const seconds = secondsLeft % 60;
    [days, hours, minutes, seconds].forEach((number, index) => {
      if (countdownNumbers[index]) {
        countdownNumbers[index].textContent = number.toLocaleString("fa-IR", { minimumIntegerDigits: 2, useGrouping: false });
      }
    });
  }, 1000);

  const filterButtons = $$(".course-filters button");
  const courseCards = $$(".course-card");
  const courseGrid = $(".course-grid");
  const courseCount = $(".course-count");
  filterButtons.forEach((button) => {
    button.setAttribute("aria-pressed", String(button.classList.contains("active")));
    button.addEventListener("click", () => {
      filterButtons.forEach((item) => {
        item.classList.remove("active");
        item.setAttribute("aria-pressed", "false");
      });
      button.classList.add("active");
      button.setAttribute("aria-pressed", "true");
      const filter = button.dataset.filter;
      let visibleCount = 0;
      courseCards.forEach((card) => {
        const filteredOut = filter !== "all" && card.dataset.category !== filter;
        card.classList.toggle("filtered-out", filteredOut);
        card.hidden = filteredOut;
        if (!filteredOut) visibleCount += 1;
      });
      if (courseGrid) courseGrid.dataset.visibleCount = String(visibleCount);
      if (courseCount) courseCount.textContent = `${visibleCount.toLocaleString("fa-IR")} دوره فعال`;
      if (courseGrid && window.matchMedia("(max-width: 760px)").matches) {
        const firstVisibleCard = courseCards.find((card) => !card.hidden);
        window.requestAnimationFrame(() => firstVisibleCard?.scrollIntoView({ behavior: reduceMotion.matches ? "auto" : "smooth", block: "nearest", inline: "start" }));
      }
    });
  });

  const cartCount = $(".cart-count");
  const actionToast = $(".action-toast");
  let cartValue = 0;
  let toastTimer;
  const addToCart = (event) => {
    cartValue += 1;
    cartCount.textContent = cartValue.toLocaleString("fa-IR");
    $(".cart-button")?.animate(
      [{ transform: "scale(1)" }, { transform: "scale(1.14)" }, { transform: "scale(1)" }],
      { duration: 360, easing: "ease-out" }
    );
    actionToast?.classList.add("show");
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => actionToast?.classList.remove("show"), 2800);
  };
  $(".primary-button")?.addEventListener("click", addToCart);

  const backToTop = $(".back-to-top");
  const siteHeader = $(".site-header");
  const progressBar = $(".scroll-progress span");
  let scrollTicking = false;
  const updateScrollUI = () => {
    const scrollable = Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
    const progress = Math.min(1, window.scrollY / scrollable);
    progressBar.style.width = `${progress * 100}%`;
    backToTop?.classList.toggle("visible", window.scrollY > 800);
    siteHeader?.classList.toggle("is-scrolled", window.scrollY > 40);
    scrollTicking = false;
  };
  window.addEventListener("scroll", () => {
    if (!scrollTicking) {
      scrollTicking = true;
      window.requestAnimationFrame(updateScrollUI);
    }
  }, { passive: true });
  backToTop?.addEventListener("click", () => window.scrollTo({ top: 0, behavior: "smooth" }));

  const revealElements = $$(
    ".section-intro, .service-card, .section-heading, .course-card, .consultation-inner, " +
    ".about-copy, .about-stats, .calendar-item, .articles-heading, .article-card, .footer-cta"
  );
  if (!reduceMotion.matches && "IntersectionObserver" in window) {
    revealElements.forEach((element, index) => {
      element.classList.add("will-reveal");
      element.style.setProperty("--reveal-delay", `${(index % 3) * 60}ms`);
    });
    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-revealed");
        observer.unobserve(entry.target);
      });
    }, { rootMargin: "0px 0px -8%", threshold: .08 });
    revealElements.forEach((element) => revealObserver.observe(element));
  }

  if (window.matchMedia("(pointer: fine)").matches) {
    $$(".service-card").forEach((card) => {
      let bounds;
      card.addEventListener("pointerenter", () => { bounds = card.getBoundingClientRect(); });
      card.addEventListener("pointermove", (event) => {
        if (!bounds) return;
        card.style.setProperty("--spot-x", `${event.clientX - bounds.left}px`);
        card.style.setProperty("--spot-y", `${event.clientY - bounds.top}px`);
      });
    });
  }

  const observedSections = $$("main section[id]");
  const navLinks = $$(".desktop-nav a, .mobile-nav a");
  if ("IntersectionObserver" in window) {
    const navigationObserver = new IntersectionObserver((entries) => {
      const visible = entries.filter((entry) => entry.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
      if (!visible) return;
      const id = visible.target.id;
      navLinks.forEach((link) => {
        const active = link.getAttribute("href") === `#${id}`;
        link.classList.toggle("is-active", active);
        if (active) link.setAttribute("aria-current", "page");
        else link.removeAttribute("aria-current");
      });
    }, { rootMargin: "-20% 0px -65%", threshold: [0, .15, .35] });
    observedSections.forEach((section) => navigationObserver.observe(section));
  }

  updateScrollUI();
})();
