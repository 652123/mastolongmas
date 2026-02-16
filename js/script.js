// --- SWEETALERT2 THEME CONFIG ---
const MasAlert = Swal.mixin({
    confirmButtonColor: '#FF8C00', // Brand Orange
    denyButtonColor: '#EF4444',
    cancelButtonColor: '#1E3A8A', // Brand Blue
    iconColor: '#FF8C00',
    background: (document.documentElement.classList.contains('dark') ? '#0F172A' : '#ffffff'),
    color: (document.documentElement.classList.contains('dark') ? '#F3F4F6' : '#1F2937'),
    customClass: {
        popup: 'rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-700',
        confirmButton: 'swal2-confirm-btn', // Defined in CSS or ignored if using inline colors
        cancelButton: 'swal2-cancel-btn'
    }
});

// Preloader
function hidePreloader() {
    const preloader = document.getElementById('preloader');
    if (preloader) {
        preloader.classList.add('hide');
        // Remove from DOM after transition to free up memory
        setTimeout(() => {
            preloader.style.display = 'none';
        }, 500);
    }
}

// Hide when DOM is ready (faster than load)
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(hidePreloader, 500); // Small delay for smoothness
});

// Fallback: Force hide after 3 seconds (in case of slow network)
window.addEventListener('load', hidePreloader);
setTimeout(hidePreloader, 3000);

// Mobile Menu Toggle
const mobileMenuBtn = document.getElementById('mobile-menu-btn');
const mobileMenu = document.getElementById('mobile-menu');

if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
    });
}

// Auto Open Login Modal from URL
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('login')) {
    openModal('login');
}

// Navbar Scroll Effect
const navbar = document.getElementById('navbar');

window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled'); // Glass effect
    } else {
        navbar.classList.remove('scrolled'); // Transparent
    }
});

// Smooth Scroll for Anchor Links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const targetId = this.getAttribute('href');
        if (targetId === '#' || targetId === '') return;

        e.preventDefault();
        if (mobileMenu) mobileMenu.classList.add('hidden'); // Close mobile menu on click

        try {
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        } catch (err) {
            console.warn("Invalid selector:", targetId);
        }
    });
});

// Scroll Reveal Animation
const revealElements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');

const revealObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('active');
        }
    });
}, {
    root: null,
    threshold: 0.15,
    rootMargin: "0px"
});

revealElements.forEach(el => revealObserver.observe(el));

// --- TYPEWRITER EFFECT ---
class TypeWriter {
    constructor(txtElement, words, wait = 3000) {
        this.txtElement = txtElement;
        this.words = words;
        this.txt = '';
        this.wordIndex = 0;
        this.wait = parseInt(wait, 10);
        this.type();
        this.isDeleting = false;
    }

    type() {
        // Current index of word
        const current = this.wordIndex % this.words.length;
        // Get full text of current word
        const fullTxt = this.words[current];

        // Check if deleting
        if (this.isDeleting) {
            // Remove char
            this.txt = fullTxt.substring(0, this.txt.length - 1);
        } else {
            // Add char
            this.txt = fullTxt.substring(0, this.txt.length + 1);
        }

        // Insert txt into element
        this.txtElement.innerHTML = `<span class="txt">${this.txt}</span>`;

        // Initial Type Speed
        let typeSpeed = 100;

        if (this.isDeleting) {
            typeSpeed /= 2;
        }

        // If word is complete
        if (!this.isDeleting && this.txt === fullTxt) {
            // Make pause at end
            typeSpeed = this.wait;
            // Set delete to true
            this.isDeleting = true;
        } else if (this.isDeleting && this.txt === '') {
            this.isDeleting = false;
            // Move to next word
            this.wordIndex++;
            // Pause before start typing
            typeSpeed = 500;
        }

        setTimeout(() => this.type(), typeSpeed);
    }
}

// Init TypeWriter
document.addEventListener('DOMContentLoaded', initTypeWriter);

function initTypeWriter() {
    const txtElement = document.querySelector('.txt-type');
    if (txtElement) {
        const words = JSON.parse(txtElement.getAttribute('data-words'));
        const wait = txtElement.getAttribute('data-wait');
        // Init TypeWriter
        new TypeWriter(txtElement, words, wait);
    }
}

// --- 3D TILT EFFECT ---
const tiltCards = document.querySelectorAll('.tilt-card');

tiltCards.forEach(card => {
    card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        const rotateX = ((y - centerY) / centerY) * -10; // Max rotation deg
        const rotateY = ((x - centerX) / centerX) * 10;

        card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.05)`;
    });

    card.addEventListener('mouseleave', () => {
        card.style.transform = `perspective(1000px) rotateX(0) rotateY(0) scale(1)`;
    });
});


// --- NUMBER COUNTER ANIMATION ---
const counters = document.querySelectorAll('.counter');
const speed = 200; // The lower the slower

const counterObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const counter = entry.target;
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText;
                const inc = target / speed;

                if (count < target) {
                    counter.innerText = Math.ceil(count + inc);
                    setTimeout(updateCount, 20);
                } else {
                    counter.innerText = target;
                }
            };
            updateCount();
            observer.unobserve(counter);
        }
    });
}, { threshold: 0.5 });

counters.forEach(counter => counterObserver.observe(counter));


// --- MODAL LOGIC ---

function openModal(type) {
    const modal = document.getElementById(type + 'Modal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent body scroll

        // Trigger Animation
        setTimeout(() => {
            modal.classList.remove('opacity-0', 'scale-95');
            modal.classList.add('opacity-100', 'scale-100');
        }, 10);
    }
}

function closeModal(type) {
    const modal = document.getElementById(type + 'Modal');
    if (modal) {
        // Reverse Animation
        modal.classList.remove('opacity-100', 'scale-100');
        modal.classList.add('opacity-0', 'scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto'; // Enable body scroll

            // Reset forms if any inside modal
            const form = modal.querySelector('form');
            if (form && type !== 'cart') form.reset(); // Don't reset cart

            // Reset alerts
            const alert = modal.querySelector('[id$="Alert"]');
            if (alert) alert.classList.add('hidden');

            // Allow re-enable submit buttons
            const btn = modal.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = false;
                // We can't easily revert innerHTML without storing original, 
                // but typically form handlers reset it on error anyway.
            }

        }, 300); // Matches CSS transition duration
    }
}

function switchModal(closeType, openType) {
    closeModal(closeType);
    // Wait for close animation to finish half-way for smoother transition
    setTimeout(() => {
        openModal(openType);
    }, 300);
}

// Close modal on outside click
window.addEventListener('click', (e) => {
    if (e.target.classList.contains('fixed') && e.target.classList.contains('z-50')) {
        // This assumes the modal wrapper has these classes (standard Tailwind modal)
        // Check if it has an ID ending in 'Modal'
        if (e.target.id && e.target.id.endsWith('Modal')) {
            closeModal(e.target.id.replace('Modal', ''));
        }
    }
});


// AJAX Handling for Auth
document.addEventListener('DOMContentLoaded', () => {
    const handleAuth = (formId, alertId) => {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const alertBox = document.getElementById(alertId);
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;

            // Loading State
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...';
            alertBox.classList.add('hidden');

            const formData = new FormData(form);

            try {
                const response = await fetch('auth_action.php', {
                    method: 'POST',
                    body: formData
                });

                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    throw new Error("Server Error: " + text.substring(0, 100));
                }

                alertBox.classList.remove('hidden');
                if (result.status === 'success') {
                    alertBox.className = "mb-4 p-3 rounded text-sm text-center bg-green-100 text-green-700 block";
                    alertBox.innerHTML = `<i class="fa-solid fa-check"></i> ${result.message}`;

                    setTimeout(() => {
                        if (result.redirect) {
                            window.location.replace(result.redirect);
                        } else {
                            window.location.reload();
                        }
                    }, 1000);
                } else {
                    alertBox.className = "mb-4 p-3 rounded text-sm text-center bg-red-100 text-red-700 block";
                    alertBox.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${result.message}`;
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } catch (error) {
                console.error(error);
                alertBox.className = "mb-4 p-3 rounded text-sm text-center bg-red-100 text-red-700 block";
                alertBox.innerText = "Terjadi kesalahan jaringan.";
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    };

    handleAuth('loginForm', 'loginAlert');
    handleAuth('registerForm', 'registerAlert');
});
