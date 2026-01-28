document.addEventListener('DOMContentLoaded', function() {
    // Page load animations
    initPageAnimations();
    
    // Navbar scroll effect
    initNavbarScroll();
    
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    const quantityInput = document.getElementById('quantity');
    const decreaseBtn = document.getElementById('decrease-qty');
    const increaseBtn = document.getElementById('increase-qty');
    
    if (decreaseBtn && increaseBtn && quantityInput) {
        decreaseBtn.addEventListener('click', function() {
            let qty = parseInt(quantityInput.value);
            if (qty > 1) {
                quantityInput.value = qty - 1;
            }
        });
        
        increaseBtn.addEventListener('click', function() {
            let qty = parseInt(quantityInput.value);
            let max = parseInt(quantityInput.max);
            if (qty < max) {
                quantityInput.value = qty + 1;
            }
        });
    }
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            const price = this.dataset.price;
            
            let quantity = 1;
            if (quantityInput) {
                quantity = parseInt(quantityInput.value);
            }
            
            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="bi bi-hourglass-split"></i> Adding...';
            
            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                this.disabled = false;
                this.innerHTML = originalText;
                
                if (data.success) {
                    showToast(data.message, 'success');
                    updateCartCount(data.cart_count);
                } else {
                    showToast(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.disabled = false;
                this.innerHTML = originalText;
                showToast('Failed to add to cart. Please try again.', 'danger');
            });
        });
    });
    
    function showToast(message, type) {
        const toastContainer = document.getElementById('toast-container') || createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 3000
        });
        
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
    
    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1050';
        document.body.appendChild(container);
        return container;
    }
    
    function updateCartCount(count) {
        const cartBadge = document.querySelector('.navbar .badge.bg-danger');
        if (cartBadge) {
            if (count > 0) {
                cartBadge.textContent = count;
                cartBadge.style.display = 'inline-block';
                cartBadge.classList.add('bounce');
                setTimeout(() => cartBadge.classList.remove('bounce'), 500);
            } else {
                cartBadge.style.display = 'none';
            }
        }
    }
    
    function initPageAnimations() {
        // Animate elements on page load
        const pageContent = document.querySelector('main');
        if (pageContent) {
            pageContent.classList.add('page-content');
        }
        
        // Stagger animation for cards
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.classList.add('stagger-item');
            card.style.animationDelay = `${index * 0.1}s`;
        });
        
        // Floating animation for hero icons
        const heroIcons = document.querySelector('.hero-section .bi-bag-check');
        if (heroIcons) {
            heroIcons.classList.add('floating');
        }
        
        // Pulse animation for feature icons
        const featureIcons = document.querySelectorAll('.features-section i');
        featureIcons.forEach(icon => {
            icon.classList.add('pulse');
        });
    }
    
    function initNavbarScroll() {
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });
        }
    }
    
    // Enhanced button interactions
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn') && !e.target.disabled) {
            // Create ripple effect
            const button = e.target;
            const ripple = document.createElement('span');
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            button.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        }
    });
    
    // Smooth scroll for anchor links
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
});