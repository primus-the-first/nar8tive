/**
 * NR8iv AFRICA - Interactive JavaScript
 * Adds smooth animations and interactivity to the website
 */

(function () {
  "use strict";

  // ==========================================
  // SMOOTH SCROLL NAVIGATION
  // ==========================================
  const smoothScroll = () => {
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
      anchor.addEventListener("click", function (e) {
        const href = this.getAttribute("href");

        // Skip if it's just "#" or empty
        if (!href || href === "#") return;

        const target = document.querySelector(href);
        if (!target) return;

        e.preventDefault();

        const navbarHeight =
          document.querySelector(".navbar")?.offsetHeight || 0;
        const targetPosition =
          target.getBoundingClientRect().top +
          window.pageYOffset -
          navbarHeight;

        window.scrollTo({
          top: targetPosition,
          behavior: "smooth",
        });

        // Close mobile menu if open
        const navbarCollapse = document.querySelector(".navbar-collapse");
        if (navbarCollapse && navbarCollapse.classList.contains("show")) {
          const bsCollapse = new bootstrap.Collapse(navbarCollapse, {
            toggle: false,
          });
          bsCollapse.hide();
        }
      });
    });
  };

  // ==========================================
  // SMART NAVBAR BEHAVIOR
  // ==========================================
  const smartNavbar = () => {
    const navbar = document.querySelector(".navbar");
    if (!navbar) return;

    let lastScrollTop = 0;
    let scrollThreshold = 100;
    let ticking = false;

    const updateNavbar = () => {
      const scrollTop =
        window.pageYOffset || document.documentElement.scrollTop;

      // Add shadow when scrolled
      if (scrollTop > 50) {
        navbar.classList.add("shadow");
      } else {
        navbar.classList.remove("shadow");
      }

      // Hide/show navbar on scroll (only on desktop)
      if (window.innerWidth > 991) {
        if (scrollTop > lastScrollTop && scrollTop > scrollThreshold) {
          navbar.style.transform = "translateY(-100%)";
        } else {
          navbar.style.transform = "translateY(0)";
        }
      } else {
        navbar.style.transform = "translateY(0)";
      }

      lastScrollTop = scrollTop;
      ticking = false;
    };

    window.addEventListener("scroll", () => {
      if (!ticking) {
        window.requestAnimationFrame(updateNavbar);
        ticking = true;
      }
    }, { passive: true });
  };

  // ==========================================
  // ACTIVE NAV LINK HIGHLIGHTING
  // ==========================================
  const highlightActiveNav = () => {
    const sections = document.querySelectorAll("section[id]");
    const navLinks = document.querySelectorAll(".navbar-nav .nav-link");
    let ticking = false;

    const updateActiveNav = () => {
      let current = "";
      const scrollPos = window.pageYOffset + 150;

      sections.forEach((section) => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;

        if (scrollPos >= sectionTop && scrollPos < sectionTop + sectionHeight) {
          current = section.getAttribute("id");
        }
      });

      navLinks.forEach((link) => {
        link.classList.remove("active");
        if (link.getAttribute("href") === `#${current}`) {
          link.classList.add("active");
        }
      });
      ticking = false;
    };

    window.addEventListener("scroll", () => {
      if (!ticking) {
        window.requestAnimationFrame(updateActiveNav);
        ticking = true;
      }
    }, { passive: true });
  };

  // ==========================================
  // SCROLL ANIMATIONS
  // ==========================================
  const scrollAnimations = () => {
    const animateOnScroll = () => {
      const elements = document.querySelectorAll(
        ".service-card, .portfolio-item, .section-title, #about .row > div"
      );

      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
              setTimeout(() => {
                entry.target.style.opacity = "1";
                entry.target.style.transform = "translateY(0)";
              }, index * 100);
              observer.unobserve(entry.target);
            }
          });
        },
        {
          threshold: 0.1,
          rootMargin: "0px 0px -50px 0px",
        }
      );

      elements.forEach((element) => {
        element.style.opacity = "0";
        element.style.transform = "translateY(30px)";
        element.style.transition = "opacity 0.6s ease, transform 0.6s ease";
        observer.observe(element);
      });
    };

    // Only run if browser supports IntersectionObserver
    if ("IntersectionObserver" in window) {
      animateOnScroll();
    }
  };

  // ==========================================
  // SECTION FADE ON SCROLL
  // ==========================================
  const sectionFadeOnScroll = () => {
    const sections = document.querySelectorAll('section');
    
    const fadeSection = () => {
      const scrollPos = window.pageYOffset;
      const windowHeight = window.innerHeight;
      
      sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.offsetHeight;
        const sectionBottom = sectionTop + sectionHeight;
        
        // Calculate fade based on scroll position
        let opacity = 1;
        
        // Fade out when scrolling past the section
        if (scrollPos > sectionBottom - windowHeight * 0.3) {
          const fadeStart = sectionBottom - windowHeight * 0.3;
          const fadeDistance = windowHeight * 0.5;
          opacity = Math.max(0, 1 - (scrollPos - fadeStart) / fadeDistance);
        }
        
        // Fade in when approaching the section
        if (scrollPos + windowHeight < sectionTop + windowHeight * 0.3) {
          const fadeStart = sectionTop + windowHeight * 0.3;
          const fadeDistance = windowHeight * 0.5;
          opacity = Math.max(0, 1 - (fadeStart - (scrollPos + windowHeight)) / fadeDistance);
        }
        
        section.style.opacity = opacity;
        section.style.transition = 'opacity 0.3s ease';
      });
    };
    
    // Throttle scroll event for performance
    let ticking = false;
    window.addEventListener('scroll', () => {
      if (!ticking) {
        window.requestAnimationFrame(() => {
          fadeSection();
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });
    
    // Initial fade
    fadeSection();
  };

  // ==========================================
  // FORM VALIDATION & ENHANCEMENT
  // ==========================================
  const formEnhancement = () => {
    const form = document.querySelector("#contactForm");
    if (!form) return;

    // Add custom validation
    form.addEventListener("submit", async function (e) {
      e.preventDefault();

      // Get form elements
      const name = form.querySelector('input[name="name"]');
      const email = form.querySelector('input[name="email"]');
      const projectType = form.querySelector('select[name="project_type"]');
      const description = form.querySelector('textarea[name="description"]');
      const submitBtn = form.querySelector('button[type="submit"]');

      // Basic validation
      let valid = true;

      if (!name.value.trim()) {
        showError(name, "Please enter your name");
        valid = false;
      } else {
        clearError(name);
      }

      if (!email.value.trim() || !isValidEmail(email.value)) {
        showError(email, "Please enter a valid email");
        valid = false;
      } else {
        clearError(email);
      }

      if (!projectType.value || projectType.value === "Select project type") {
        showError(projectType, "Please select a project type");
        valid = false;
      } else {
        clearError(projectType);
      }

      if (!description.value.trim()) {
        showError(description, "Please describe your project");
        valid = false;
      } else {
        clearError(description);
      }

      if (valid) {
        // Show loading state
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML =
          '<i class="bi bi-hourglass-split"></i> Sending...';
        submitBtn.disabled = true;

        try {
          // Prepare form data
          const formData = new FormData(form);

          // Submit form via AJAX
          const response = await fetch('send_email.php', {
            method: 'POST',
            body: formData
          });

          const result = await response.json();

          if (result.success) {
            // Success state
            form.reset();
            submitBtn.innerHTML =
              '<i class="bi bi-check-circle"></i> Sent Successfully!';
            submitBtn.classList.remove("btn-primary-custom");
            submitBtn.classList.add("btn-success");

            // Show success alert
            showAlert('success', result.message || 'Thank you for your message! We will get back to you soon.');

            // Reset button after 3 seconds
            setTimeout(() => {
              submitBtn.innerHTML = originalText;
              submitBtn.classList.remove("btn-success");
              submitBtn.classList.add("btn-primary-custom");
              submitBtn.disabled = false;
            }, 3000);
          } else {
            // Error state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            showAlert('error', result.message || 'Sorry, there was an error sending your message. Please try again.');
          }
        } catch (error) {
          // Network or other error
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
          showAlert('error', 'Sorry, there was an error sending your message. Please check your connection and try again.');
          console.error('Form submission error:', error);
        }
      }
    });

    // Helper functions
    function showError(element, message) {
      clearError(element);
      element.classList.add("is-invalid");
      const errorDiv = document.createElement("div");
      errorDiv.className = "invalid-feedback d-block";
      errorDiv.textContent = message;
      element.parentNode.appendChild(errorDiv);
    }

    function clearError(element) {
      element.classList.remove("is-invalid");
      const errorDiv = element.parentNode.querySelector(".invalid-feedback");
      if (errorDiv) {
        errorDiv.remove();
      }
    }

    function isValidEmail(email) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return re.test(email);
    }

    function showAlert(type, message) {
      // Remove any existing alerts
      const existingAlert = document.querySelector('.custom-alert');
      if (existingAlert) {
        existingAlert.remove();
      }

      // Create alert element
      const alertDiv = document.createElement('div');
      alertDiv.className = `custom-alert alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
      alertDiv.style.cssText = 'position: fixed; top: 100px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
      alertDiv.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'}"></i>
        <span style="margin-left: 10px;">${message}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;

      document.body.appendChild(alertDiv);

      // Auto-dismiss after 5 seconds
      setTimeout(() => {
        alertDiv.classList.remove('show');
        setTimeout(() => alertDiv.remove(), 150);
      }, 5000);
    }
  };

  // ==========================================
  // COUNTER ANIMATIONS FOR STATS
  // ==========================================
  const counterAnimation = () => {
    const counters = document.querySelectorAll("[data-count]");

    counters.forEach((counter) => {
      const target = parseInt(counter.getAttribute("data-count"));
      const duration = 2000; // 2 seconds
      const increment = target / (duration / 16); // 60fps
      let current = 0;

      const updateCounter = () => {
        current += increment;
        if (current < target) {
          counter.textContent = Math.ceil(current);
          requestAnimationFrame(updateCounter);
        } else {
          counter.textContent = target;
        }
      };

      // Start animation when element is in view
      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              updateCounter();
              observer.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.5 }
      );

      observer.observe(counter);
    });
  };

  // ==========================================
  // PARALLAX EFFECT FOR HERO
  // ==========================================
  const parallaxEffect = () => {
    const hero = document.querySelector("#hero");
    if (!hero) return;

    let ticking = false;
    const updateParallax = () => {
      const scrolled = window.pageYOffset;
      const parallaxSpeed = 0.5;

      if (scrolled < window.innerHeight) {
        hero.style.transform = `translateY(${scrolled * parallaxSpeed}px)`;
      }
      ticking = false;
    };

    window.addEventListener("scroll", () => {
      if (!ticking) {
        window.requestAnimationFrame(updateParallax);
        ticking = true;
      }
    }, { passive: true });
  };

  // ==========================================
  // LOADER ANIMATION
  // ==========================================
  const pageLoader = () => {
    window.addEventListener("load", () => {
      document.body.classList.add("loaded");
    });
  };

  // ==========================================
  // INITIALIZE ALL FEATURES
  // ==========================================
  const init = () => {
    smoothScroll();
    smartNavbar();
    highlightActiveNav();
    scrollAnimations();
    sectionFadeOnScroll();
    formEnhancement();
    counterAnimation();
    parallaxEffect();
    pageLoader();

    console.log("✨ NR8iv AFRICA interactive features loaded");
  };

  // Run when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
