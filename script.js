// Form validation
document.querySelectorAll("form").forEach(form => {
  form.addEventListener("submit", function(e) {
    const password = form.querySelector("input[name='password']");
    if (password && password.value.length < 6) {
      e.preventDefault();
      alert("Password must be at least 6 characters.");
    }
  });
});

// Utility functions
function isInViewport(element) {
  const rect = element.getBoundingClientRect();
  return (
    rect.top <= (window.innerHeight || document.documentElement.clientHeight) &&
    rect.bottom >= 0
  );
}

// Initialize all functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  // Animation handling
  const animateElements = document.querySelectorAll('.animate');
  
  function handleScrollAnimation() {
    animateElements.forEach(element => {
      if (isInViewport(element)) {
        element.classList.add('visible');
      }
    });
  }

  // Initial animation check
  handleScrollAnimation();
  window.addEventListener('scroll', handleScrollAnimation);

  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });

  // Interactive elements handlers
  const interactiveElements = {
    '.skill-item': (element) => {
      element.addEventListener('click', (e) => {
        e.preventDefault();
        console.log('Selected skill:', element.textContent.trim());
      });
    },
    '.btn-primary, .btn-secondary': (element) => {
      element.addEventListener('click', (e) => {
        console.log('CTA button clicked:', element.textContent.trim());
      });
    },
    '.social-link': (element) => {
      element.addEventListener('click', (e) => {
        e.preventDefault();
        console.log('Social link clicked:', element.getAttribute('href'));
      });
    },
    '.footer-links a, .legal-links a': (element) => {
      element.addEventListener('click', (e) => {
        e.preventDefault();
        console.log('Footer link clicked:', element.getAttribute('href'));
      });
    }
  };

  // Apply handlers to all interactive elements
  Object.entries(interactiveElements).forEach(([selector, handler]) => {
    document.querySelectorAll(selector).forEach(handler);
  });

  // Header scroll effect
  let lastScroll = 0;
  const header = document.querySelector('.main-header');
  
  if (header) {
    window.addEventListener('scroll', () => {
      const currentScroll = window.pageYOffset;
      
      if (currentScroll <= 0) {
        header.classList.remove('scroll-up');
        return;
      }
      
      if (currentScroll > lastScroll && !header.classList.contains('scroll-down')) {
        header.classList.remove('scroll-up');
        header.classList.add('scroll-down');
      } else if (currentScroll < lastScroll && header.classList.contains('scroll-down')) {
        header.classList.remove('scroll-down');
        header.classList.add('scroll-up');
      }
      lastScroll = currentScroll;
    });
  }

  // Scroll reveal animation (excluding categories section)
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.target.closest('.categories-section')) return;
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
      }
    });
  });

  // Observe elements with animate class that are not in categories section
  document.querySelectorAll('.animate:not(.categories-section *)').forEach((el) => observer.observe(el));
});