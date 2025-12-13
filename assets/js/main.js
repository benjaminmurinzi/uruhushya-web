/**
 * =====================================================
 * URUHUSHYA - Main JavaScript
 * =====================================================
 * Handles interactivity on the landing page
 * =====================================================
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    
    /**
     * =====================================================
     * SMOOTH SCROLLING FOR ANCHOR LINKS
     * =====================================================
     */
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
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
    
    /**
     * =====================================================
     * NAVBAR SCROLL EFFECT
     * =====================================================
     * Add shadow to navbar when scrolling down
     */
    const navbar = document.querySelector('.navbar');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
        } else {
            navbar.style.boxShadow = 'none';
        }
    });
    
    /**
     * =====================================================
     * ANIMATED COUNTER FOR STATISTICS
     * =====================================================
     * Animates numbers when they come into view
     */
    function animateCounter(element) {
        const target = parseInt(element.textContent.replace(/[^0-9]/g, ''));
        const duration = 2000; // 2 seconds
        const increment = target / (duration / 16); // 60fps
        let current = 0;
        
        const timer = setInterval(function() {
            current += increment;
            
            if (current >= target) {
                element.textContent = formatNumber(target);
                clearInterval(timer);
            } else {
                element.textContent = formatNumber(Math.floor(current));
            }
        }, 16);
    }
    
    function formatNumber(num) {
        if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K+';
        }
        return num.toString();
    }
    
    /**
     * =====================================================
     * INTERSECTION OBSERVER FOR ANIMATIONS
     * =====================================================
     * Trigger animations when elements come into view
     */
    const observer = new IntersectionObserver(
        function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Add fade-in animation
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    
                    // Animate counters
                    if (entry.target.classList.contains('stat-number')) {
                        animateCounter(entry.target);
                    }
                }
            });
        },
        {
            threshold: 0.1 // Trigger when 10% visible
        }
    );
    
    // Observe all feature cards and stat numbers
    document.querySelectorAll('.feature-card, .user-type-card, .pricing-card, .stat-number').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
    
    /**
     * =====================================================
     * DROPDOWN MENU TOGGLE (for mobile)
     * =====================================================
     */
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    
    if (dropdownToggle && dropdownMenu) {
        dropdownToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.style.opacity = dropdownMenu.style.opacity === '1' ? '0' : '1';
            dropdownMenu.style.visibility = dropdownMenu.style.visibility === 'visible' ? 'hidden' : 'visible';
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            if (dropdownMenu) {
                dropdownMenu.style.opacity = '0';
                dropdownMenu.style.visibility = 'hidden';
            }
        });
    }
    
    /**
     * =====================================================
     * PRICING CARD HOVER EFFECT
     * =====================================================
     */
    document.querySelectorAll('.pricing-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.borderColor = 'var(--primary)';
        });
        
        card.addEventListener('mouseleave', function() {
            if (!this.classList.contains('featured')) {
                this.style.borderColor = 'var(--gray-200)';
            }
        });
    });
    
    /**
     * =====================================================
     * FORM VALIDATION HELPER (for future forms)
     * =====================================================
     */
    window.validateEmail = function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    };
    
    window.validatePhone = function(phone) {
        // Rwanda phone format: 078... or +25078...
        const re = /^(\+?250)?0?7[238]\d{7}$/;
        return re.test(phone.replace(/\s/g, ''));
    };
    
    /**
     * =====================================================
     * SHOW SUCCESS/ERROR MESSAGES
     * =====================================================
     */
    window.showMessage = function(message, type = 'success') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message-toast message-${type}`;
        messageDiv.textContent = message;
        
        messageDiv.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 1rem 1.5rem;
            background: ${type === 'success' ? '#10b981' : '#ef4444'};
            color: white;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            animation: slideInRight 0.3s ease;
        `;
        
        document.body.appendChild(messageDiv);
        
        // Remove after 3 seconds
        setTimeout(function() {
            messageDiv.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(function() {
                document.body.removeChild(messageDiv);
            }, 300);
        }, 3000);
    };
    
    /**
     * =====================================================
     * LOADING SPINNER
     * =====================================================
     */
    window.showLoading = function() {
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'loading-spinner';
        loadingDiv.innerHTML = `
            <div style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 99999;
            ">
                <div style="
                    width: 50px;
                    height: 50px;
                    border: 4px solid #f3f4f6;
                    border-top: 4px solid #4f46e5;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                "></div>
            </div>
        `;
        document.body.appendChild(loadingDiv);
    };
    
    window.hideLoading = function() {
        const loadingDiv = document.getElementById('loading-spinner');
        if (loadingDiv) {
            document.body.removeChild(loadingDiv);
        }
    };
    
    /**
     * =====================================================
     * CONSOLE MESSAGE (Easter Egg)
     * =====================================================
     */
    console.log('%c🚗 URUHUSHYA', 'color: #4f46e5; font-size: 24px; font-weight: bold;');
    console.log('%cDriving License Platform for Rwanda 🇷🇼', 'color: #6b7280; font-size: 14px;');
    console.log('%cBuilt with ❤️ by the URUHUSHYA Team', 'color: #9ca3af; font-size: 12px;');
    
});

/**
 * =====================================================
 * CSS ANIMATIONS (add to page dynamically)
 * =====================================================
 */
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }
    
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }
`;
document.head.appendChild(style);