</div> <!-- End container-fluid -->
    </div> <!-- End main-content -->
    
    <!-- JS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        /**
         * 🏆 SMART RESTO POS - CORE JAVASCRIPT
         * Main functionality dan Gold Theme Animations
         */
        
        // =============================================
        // MOBILE MENU & SIDEBAR
        // =============================================
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (menuToggle) {
            menuToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            });
        }
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
        
        // Close sidebar on navigation (mobile only)
        if (window.innerWidth < 768) {
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', function() {
                    setTimeout(() => {
                        sidebar.classList.remove('active');
                        sidebarOverlay.classList.remove('active');
                        document.body.style.overflow = '';
                    }, 200);
                });
            });
        }
        
        // =============================================
        // CLOCK
        // =============================================
        function updateTime() {
            const now = new Date();
            const options = { 
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            const timeString = now.toLocaleTimeString('id-ID', options);
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        updateTime();
        setInterval(updateTime, 1000);
        
        // =============================================
        // SELECT2 INITIALIZATION
        // =============================================
        $(document).ready(function() {
            if (typeof $.fn.select2 !== 'undefined') {
                $('.select2').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownAutoWidth: true
                });
            }
        });
        
        // =============================================
        // UTILITY FUNCTIONS
        // =============================================
        
        /**
         * Confirm Delete
         */
        function confirmDelete(message = 'Apakah Anda yakin ingin menghapus data ini?') {
            return confirm(message);
        }
        
        /**
         * Format Rupiah
         */
        function formatRupiah(angka, prefix = 'Rp ') {
            const number = typeof angka === 'string' ? parseFloat(angka) : angka;
            if (isNaN(number)) return prefix + '0';
            
            const numberString = Math.round(number).toString();
            const split = numberString.split(',');
            const sisa = split[0].length % 3;
            let rupiah = split[0].substr(0, sisa);
            const ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                const separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            return prefix + rupiah;
        }
        
        /**
         * Parse Rupiah to Number
         */
        function parseRupiah(rupiah) {
            if (typeof rupiah === 'number') return rupiah;
            return parseInt(rupiah.toString().replace(/[^0-9]/g, '')) || 0;
        }
        
        /**
         * Show Success Toast
         */
        function showSuccess(message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'swal-mobile-toast'
                    }
                });
            } else {
                alert('SUCCESS: ' + message);
            }
        }
        
        /**
         * Show Error Toast
         */
        function showError(message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'swal-mobile-toast'
                    }
                });
            } else {
                alert('ERROR: ' + message);
            }
        }
        
        /**
         * Show Loading
         */
        function showLoading(message = 'Loading...') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: message,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                    customClass: {
                        popup: 'swal-mobile-popup'
                    }
                });
            }
        }
        
        /**
         * Hide Loading
         */
        function hideLoading() {
            if (typeof Swal !== 'undefined') {
                Swal.close();
            }
        }
        
        /**
         * Confirm Dialog
         */
        async function confirmDialog(title, text, confirmButtonText = 'Ya, Lanjutkan') {
            const result = await Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DAA520',
                cancelButtonColor: '#d33',
                confirmButtonText: confirmButtonText,
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'swal-mobile-popup',
                    confirmButton: 'swal-mobile-button',
                    cancelButton: 'swal-mobile-button'
                },
                buttonsStyling: true
            });
            return result.isConfirmed;
        }
        
        /**
         * Print Content
         */
        function printContent(elementId) {
            const content = document.getElementById(elementId);
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Print</title>');
            printWindow.document.write('<style>');
            printWindow.document.write('body { font-family: Arial, sans-serif; padding: 20px; }');
            printWindow.document.write('table { width: 100%; border-collapse: collapse; margin: 20px 0; }');
            printWindow.document.write('th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }');
            printWindow.document.write('th { background-color: #DAA520; color: #000; font-weight: bold; }');
            printWindow.document.write('@media print { body { padding: 0; } }');
            printWindow.document.write('</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(content.innerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
        
        // =============================================
        // GOLD THEME ANIMATIONS
        // =============================================
        
        /**
         * Create Gold Particle Effect
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
            
            function animate() {
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
            }
            
            animate();
        }
        
        /**
         * Create Ripple Effect
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
            
            if (!document.getElementById('ripple-keyframes')) {
                const style = document.createElement('style');
                style.id = 'ripple-keyframes';
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
         * Initialize Gold Theme Animations
         */
        function initializeGoldTheme() {
            console.log('🏆 Initializing Gold Premium Theme...');
            
            // Apply to all buttons
            document.querySelectorAll('.btn, button:not(.btn-close)').forEach(button => {
                // Hover effect
                button.addEventListener('mouseenter', function() {
                    if (!this.disabled) {
                        this.style.transform = 'translateY(-2px) scale(1.02)';
                    }
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
                
                // Click effect with particles
                button.addEventListener('click', function(e) {
                    if (!this.disabled) {
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
                    }
                });
            });
            
            // Apply to all cards
            document.querySelectorAll('.card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });
            
            // Apply to all inputs
            document.querySelectorAll('input:not([type="hidden"]), textarea, select').forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.style.transform = '';
                });
            });
            
            // Scroll reveal animation
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
            
            // Observe cards
            document.querySelectorAll('.card').forEach(el => {
                observer.observe(el);
            });
            
            console.log('✅ Gold Theme initialized!');
        }
        
        /**
         * Add Cursor Trail
         */
        function addCursorTrail() {
            let particles = [];
            const maxParticles = 10;
            
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
                    width: 6px;
                    height: 6px;
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
                    particle.life -= 0.03;
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
        
        // =============================================
        // AUTO-INITIALIZATION
        // =============================================
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                initializeGoldTheme();
                addCursorTrail();
            });
        } else {
            initializeGoldTheme();
            addCursorTrail();
        }
        
        // =============================================
        // FORM HELPERS
        // =============================================
        
        // Auto format rupiah input
        $(document).on('keyup', '.format-rupiah', function() {
            let value = $(this).val();
            value = value.replace(/[^0-9]/g, '');
            $(this).val(formatRupiah(value, ''));
        });
        
        // Number only input
        $(document).on('keypress', '.number-only', function(e) {
            if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
                return false;
            }
        });
        
        // =============================================
        // MOBILE OPTIMIZATIONS
        // =============================================
        
        // Prevent double tap zoom on iOS
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, { passive: false });
        
        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== '#') {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            });
        });
        
        // =============================================
        // SWEETALERT2 MOBILE STYLES
        // =============================================
        
        const swalStyle = document.createElement('style');
        swalStyle.textContent = `
            .swal-mobile-toast {
                font-size: 14px !important;
                padding: 12px !important;
            }
            
            .swal-mobile-popup {
                font-size: 15px !important;
                padding: 20px !important;
            }
            
            .swal-mobile-popup .swal2-title {
                font-size: 20px !important;
                padding: 10px 0 !important;
            }
            
            .swal-mobile-popup .swal2-html-container {
                font-size: 14px !important;
                line-height: 1.6 !important;
            }
            
            .swal-mobile-button {
                font-size: 14px !important;
                padding: 10px 24px !important;
                border-radius: 8px !important;
                min-width: 100px !important;
            }
            
            @media (max-width: 576px) {
                .swal2-popup {
                    width: 90% !important;
                    max-width: 400px !important;
                }
                
                .swal2-actions {
                    flex-direction: column !important;
                    gap: 10px !important;
                    width: 100% !important;
                }
                
                .swal2-actions button {
                    width: 100% !important;
                    margin: 0 !important;
                }
            }
        `;
        document.head.appendChild(swalStyle);
        
        // =============================================
        // CONSOLE INFO
        // =============================================
        
        console.log('%c🏆 Smart Resto POS', 'color: #FFD700; font-size: 24px; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);');
        console.log('%cGold Premium Theme Active', 'color: #DAA520; font-size: 14px; font-weight: bold;');
        console.log('%cVersion 1.0.0 - Optimized for Mobile & Desktop', 'color: #B8860B; font-size: 12px;');
    </script>
</body>
</html>