/**
 * 🏆 SMART RESTO POS - GOLD THEME ANIMATIONS
 * Interactive animations dan effects untuk semua halaman
 * Auto-apply untuk elemen yang ada
 */

(function() {
    'use strict';
    
    // ========================================
    // CONFIGURATION
    // ========================================
    const config = {
        particleCount: 30,
        animationDuration: 300,
        hoverScale: 1.05,
        clickScale: 0.95
    };
    
    // ========================================
    // UTILITY FUNCTIONS
    // ========================================
    
    /**
     * Add class with animation
     */
    function addClassAnimated(element, className, duration = 300) {
        element.classList.add(className);
        setTimeout(() => {
            element.style.transition = `all ${duration}ms ease`;
        }, 10);
    }
    
    /**
     * Create gold particle effect
     */
    function createGoldParticle(x, y) {
        const particle = document.createElement('div');
        particle.style.cssText = `
            position: fixed;
            left: ${x}px;
            top: ${y}px;
            width: ${Math.random() * 8 + 4}px;
            height: ${Math.random() * 8 + 4}px;
            background: linear-gradient(135deg, #FFD700, #DAA520);
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            box-shadow: 0 0 10px rgba(218, 165, 32, 0.8);
        `;
        
        document.body.appendChild(particle);
        
        const angle = Math.random() * Math.PI * 2;
        const velocity = Math.random() * 3 + 2;
        const vx = Math.cos(angle) * velocity;
        const vy = Math.sin(angle) * velocity - 5;
        
        let posX = x;
        let posY = y;
        let opacity = 1;
        let velocityY = vy;
        
        const animate = () => {
            posX += vx;
            posY += velocityY;
            velocityY += 0.3; // gravity
            opacity -= 0.02;
            
            particle.style.left = posX + 'px';
            particle.style.top = posY + 'px';
            particle.style.opacity = opacity;
            
            if (opacity > 0) {
                requestAnimationFrame(animate);
            } else {
                particle.remove();
            }
        };
        
        animate();
    }
    
    /**
     * Create ripple effect
     */
    function createRipple(event, element) {
        const ripple = document.createElement('span');
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            border-radius: 50%;
            background: rgba(255, 215, 0, 0.5);
            transform: translate(${x}px, ${y}px) scale(0);
            animation: rippleAnimation 0.6s ease-out;
            pointer-events: none;
        `;
        
        // Add keyframe animation if not exists
        if (!document.getElementById('ripple-style')) {
            const style = document.createElement('style');
            style.id = 'ripple-style';
            style.textContent = `
                @keyframes rippleAnimation {
                    to {
                        transform: translate(${x}px, ${y}px) scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.appendChild(ripple);
        
        setTimeout(() => ripple.remove(), 600);
    }
    
    /**
     * Float animation for elements
     */
    function applyFloatAnimation(element, duration = 3000) {
        let startTime = null;
        const startY = 0;
        const amplitude = 10;
        
        function animate(currentTime) {
            if (!startTime) startTime = currentTime;
            const elapsed = currentTime - startTime;
            const progress = (elapsed % duration) / duration;
            const y = startY + Math.sin(progress * Math.PI * 2) * amplitude;
            
            element.style.transform = `translateY(${y}px)`;
            
            requestAnimationFrame(animate);
        }
        
        requestAnimationFrame(animate);
    }
    
    /**
     * Glowing text effect
     */
    function applyGlowText(element) {
        let intensity = 0;
        let increasing = true;
        
        function animate() {
            if (increasing) {
                intensity += 0.02;
                if (intensity >= 1) increasing = false;
            } else {
                intensity -= 0.02;
                if (intensity <= 0) increasing = true;
            }
            
            element.style.textShadow = `
                0 0 ${10 + intensity * 20}px rgba(255, 215, 0, ${0.5 + intensity * 0.5}),
                0 0 ${20 + intensity * 40}px rgba(218, 165, 32, ${0.3 + intensity * 0.3})
            `;
            
            requestAnimationFrame(animate);
        }
        
        animate();
    }
    
    // ========================================
    // AUTO-APPLY ANIMATIONS
    // ========================================
    
    /**
     * Initialize when DOM is ready
     */
    function initializeGoldTheme() {
        console.log('🏆 Initializing Gold Premium Theme...');
        
        // Apply to all buttons
        document.querySelectorAll('.btn, button, [role="button"]').forEach(button => {
            if (!button.classList.contains('btn-close')) {
                // Add hover effect
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px) scale(1.02)';
                    this.style.boxShadow = '0 8px 25px rgba(218, 165, 32, 0.5)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                    this.style.boxShadow = '';
                });
                
                // Add click effect with particles
                button.addEventListener('click', function(e) {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                    
                    // Create particles
                    for (let i = 0; i < 5; i++) {
                        setTimeout(() => {
                            createGoldParticle(e.clientX, e.clientY);
                        }, i * 20);
                    }
                    
                    // Ripple effect
                    createRipple(e, this);
                });
            }
        });
        
        // Apply to all cards
        document.querySelectorAll('.card, .gold-card, [class*="card"]').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
                this.style.boxShadow = '0 20px 50px rgba(218, 165, 32, 0.45)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
                this.style.boxShadow = '';
            });
        });
        
        // Apply to all inputs
        document.querySelectorAll('input, textarea, select').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 0 0 3px rgba(218, 165, 32, 0.2), 0 0 20px rgba(218, 165, 32, 0.3)';
            });
            
            input.addEventListener('blur', function() {
                this.style.transform = '';
                this.style.boxShadow = '';
            });
        });
        
        // Apply glow to important text
        document.querySelectorAll('h1, h2, .page-title, .logo-text').forEach(title => {
            if (title.textContent.length < 50) { // Only short titles
                applyGlowText(title);
            }
        });
        
        // Apply float to icons
        document.querySelectorAll('.stat-icon, .logo-icon, .feature-icon').forEach((icon, index) => {
            applyFloatAnimation(icon, 3000 + index * 200);
        });
        
        // Add shimmer to cards on page load
        setTimeout(() => {
            document.querySelectorAll('.card, .gold-card').forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('shimmer');
                    setTimeout(() => {
                        card.classList.remove('shimmer');
                    }, 3000);
                }, index * 100);
            });
        }, 500);
        
        // Intersection Observer for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '0';
                    entry.target.style.transform = 'translateY(30px)';
                    
                    setTimeout(() => {
                        entry.target.style.transition = 'all 0.6s ease';
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, 100);
                    
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        // Observe all cards and sections
        document.querySelectorAll('.card, .stats-card, section, .gold-card').forEach(el => {
            observer.observe(el);
        });
        
        console.log('✅ Gold Theme initialized successfully!');
    }
    
    // ========================================
    // SPECIAL EFFECTS
    // ========================================
    
    /**
     * Add gold cursor trail
     */
    function addCursorTrail() {
        let particles = [];
        const maxParticles = 15;
        
        document.addEventListener('mousemove', (e) => {
            if (particles.length >= maxParticles) {
                const oldParticle = particles.shift();
                oldParticle.element.remove();
            }
            
            const particle = {
                x: e.clientX,
                y: e.clientY,
                life: 1,
                element: document.createElement('div')
            };
            
            particle.element.style.cssText = `
                position: fixed;
                left: ${particle.x}px;
                top: ${particle.y}px;
                width: 8px;
                height: 8px;
                background: radial-gradient(circle, #FFD700, transparent);
                border-radius: 50%;
                pointer-events: none;
                z-index: 9998;
                transform: translate(-50%, -50%);
            `;
            
            document.body.appendChild(particle.element);
            particles.push(particle);
        });
        
        function animateParticles() {
            particles.forEach((particle, index) => {
                particle.life -= 0.02;
                particle.element.style.opacity = particle.life;
                particle.element.style.transform = `translate(-50%, -50%) scale(${particle.life})`;
                
                if (particle.life <= 0) {
                    particle.element.remove();
                    particles.splice(index, 1);
                }
            });
            
            requestAnimationFrame(animateParticles);
        }
        
        animateParticles();
    }
    
    /**
     * Add parallax effect to background
     */
    function addParallaxEffect() {
        document.addEventListener('mousemove', (e) => {
            const moveX = (e.clientX - window.innerWidth / 2) / 50;
            const moveY = (e.clientY - window.innerHeight / 2) / 50;
            
            document.querySelectorAll('[data-parallax]').forEach(el => {
                const speed = el.dataset.parallax || 1;
                el.style.transform = `translate(${moveX * speed}px, ${moveY * speed}px)`;
            });
        });
    }
    
    /**
     * Add sparkle effect on hover
     */
    function addSparkleEffect() {
        document.querySelectorAll('.sparkle-on-hover, .btn-primary, .btn-gold').forEach(element => {
            element.addEventListener('mouseenter', function(e) {
                for (let i = 0; i < 3; i++) {
                    setTimeout(() => {
                        const rect = this.getBoundingClientRect();
                        const x = rect.left + Math.random() * rect.width;
                        const y = rect.top + Math.random() * rect.height;
                        
                        const sparkle = document.createElement('div');
                        sparkle.style.cssText = `
                            position: fixed;
                            left: ${x}px;
                            top: ${y}px;
                            width: 4px;
                            height: 4px;
                            background: #FFD700;
                            border-radius: 50%;
                            pointer-events: none;
                            z-index: 9999;
                            animation: sparkleAnimation 0.8s ease-out;
                        `;
                        
                        document.body.appendChild(sparkle);
                        setTimeout(() => sparkle.remove(), 800);
                    }, i * 100);
                }
            });
        });
        
        // Add keyframe animation
        if (!document.getElementById('sparkle-style')) {
            const style = document.createElement('style');
            style.id = 'sparkle-style';
            style.textContent = `
                @keyframes sparkleAnimation {
                    0% {
                        transform: scale(0) rotate(0deg);
                        opacity: 1;
                    }
                    100% {
                        transform: scale(3) rotate(180deg);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    // ========================================
    // INITIALIZE ON LOAD
    // ========================================
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initializeGoldTheme();
            addCursorTrail();
            addParallaxEffect();
            addSparkleEffect();
        });
    } else {
        initializeGoldTheme();
        addCursorTrail();
        addParallaxEffect();
        addSparkleEffect();
    }
    
    // Re-initialize when new content is loaded (for SPAs)
    const bodyObserver = new MutationObserver(() => {
        initializeGoldTheme();
    });
    
    bodyObserver.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    console.log('🌟 Gold Theme Animations loaded!');
    
})();
