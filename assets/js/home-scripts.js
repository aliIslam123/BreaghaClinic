/* ================================================
   home-scripts.js — Editorial Luxury Hero
   Breagha Clinic
   ================================================ */

(function () {
  'use strict';

  /* ── 1. Hero Stagger Entrance ── */
  function heroEntrance() {
    var elements = document.querySelectorAll('[data-hero]');
    var delays = {
      brand: 0.3,
      line: 0.65,
      headline: 0.9,
      tagline: 1.1,
      hours: 1.3,
      actions: 1.5,
      scroll: 2.4
    };
    elements.forEach(function (el) {
      var key = el.getAttribute('data-hero');
      var delay = delays[key] || 1;
      el.style.transition =
        'opacity 0.9s cubic-bezier(0.22, 0.61, 0.36, 1) ' + delay + 's, ' +
        'transform 0.9s cubic-bezier(0.22, 0.61, 0.36, 1) ' + delay + 's';
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
    });
    // Divider height animation
    var divider = document.querySelector('.hero__divider');
    if (divider) {
      setTimeout(function () {
        divider.style.transition = 'height 1s cubic-bezier(0.22, 0.61, 0.36, 1)';
        divider.style.height = '100%';
      }, 650);
    }
  }

  /* ── 2. Canvas Particles ── */
  function initParticles() {
    var canvas = document.getElementById('heroCanvas');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var w, h, particles = [];

    function resize() {
      w = canvas.width = canvas.offsetWidth;
      h = canvas.height = canvas.offsetHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    var count = w < 600 ? 20 : 40;
    for (var i = 0; i < count; i++) {
      particles.push({
        x: Math.random() * w,
        y: Math.random() * h,
        r: 0.8 + Math.random() * 2,
        dx: (Math.random() - 0.5) * 0.15,
        dy: -0.1 - Math.random() * 0.3,
        alpha: 0.1 + Math.random() * 0.4,
        phase: Math.random() * Math.PI * 2
      });
    }

    function draw() {
      ctx.clearRect(0, 0, w, h);
      for (var i = 0; i < particles.length; i++) {
        var p = particles[i];
        p.x += p.dx;
        p.y += p.dy;
        p.phase += 0.008;
        var a = p.alpha * (0.6 + 0.4 * Math.sin(p.phase));
        if (p.y < -10) { p.y = h + 10; p.x = Math.random() * w; }
        if (p.x < -10) p.x = w + 10;
        if (p.x > w + 10) p.x = -10;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(195, 158, 49, ' + a + ')';
        ctx.fill();
      }
      requestAnimationFrame(draw);
    }
    draw();
  }

  /* ── 3. Parallax on Scroll ── */
  function initParallax() {
    var hero = document.querySelector('.hero');
    var bg = document.querySelector('.hero__bg');
    if (!hero || !bg) return;

    var ticking = false;
    window.addEventListener('scroll', function () {
      if (!ticking) {
        requestAnimationFrame(function () {
          var sy = window.scrollY;
          if (sy < window.innerHeight) {
            bg.style.transform = 'translateY(' + (sy * 0.35) + 'px)';
          }
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });
  }

  /* ── 4. Navbar Scroll ── */
  function initNavbar() {
    var navbar = document.querySelector('.navbar');
    if (!navbar) return;

    function handleNavScroll() {
      navbar.classList.toggle('scrolled', window.scrollY > 60);
    }

    window.addEventListener('scroll', handleNavScroll, { passive: true });
    handleNavScroll();
  }

  /* ── 5. Mobile Nav Toggle ── */
  function initMobileNav() {
    var toggle = document.querySelector('.nav-toggle');
    var links = document.querySelector('.nav-links');
    if (!toggle || !links) return;

    toggle.addEventListener('click', function () {
      var isOpen = links.classList.toggle('open');
      toggle.classList.toggle('open', isOpen);
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  }

  /* ── 6. Smooth Scroll for # Links ── */
  function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
      anchor.addEventListener('click', function (e) {
        var href = this.getAttribute('href');
        if (href === '#' || href === 'javascript:void(0)') return;
        e.preventDefault();
        var target = document.querySelector(href);
        if (target) target.scrollIntoView({ behavior: 'smooth' });
      });
    });
  }

  /* ── 7. IntersectionObserver for .fade-in-smooth ── */
  function initFadeObserver() {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) entry.target.classList.add('in');
      });
    }, { threshold: 0.12 });

    function observeExisting() {
      document.querySelectorAll('.fade-in-smooth, .card, .spot-card').forEach(function (el) {
        if (!el.classList.contains('in')) observer.observe(el);
      });
    }

    observeExisting();

    new MutationObserver(function (mutations) {
      for (var m = 0; m < mutations.length; m++) {
        var nodes = mutations[m].addedNodes;
        for (var n = 0; n < nodes.length; n++) {
          var node = nodes[n];
          if (!(node instanceof HTMLElement)) continue;
          if (node.matches && node.matches('.fade-in-smooth, .card, .spot-card')) {
            observer.observe(node);
          } else if (node.querySelectorAll) {
            node.querySelectorAll('.fade-in-smooth, .card, .spot-card').forEach(function (el) {
              observer.observe(el);
            });
          }
        }
      }
    }).observe(document.body, { childList: true, subtree: true });
  }

  /* ── Initialize ── */
  function init() {
    heroEntrance();
    initParticles();
    initParallax();
    initNavbar();
    initMobileNav();
    initSmoothScroll();
    initFadeObserver();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
