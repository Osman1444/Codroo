// Theme management
let theme = 'dark';

function toggleTheme() {
    theme = theme === 'light' ? 'dark' : 'light';
    document.documentElement.classList.toggle('dark');
    document.body.className = `min-h-screen ${theme}`;
    initCanvas(); // Reinitialize canvas with new theme colors
}

// Interactive Background
let canvas, ctx, circles = [], mousePosition = { x: 0, y: 0 };

function initCanvas() {
    canvas = document.getElementById('background-canvas');
    if (!canvas) return;
    
    ctx = canvas.getContext('2d');
    if (!ctx) return;

    // Colors based on theme
    const colors = theme === 'dark' 
        ? [
            'rgba(139, 92, 246, 0.15)',  // Lighter Purple
            'rgba(79, 70, 229, 0.15)',   // Lighter Indigo
            'rgba(59, 130, 246, 0.15)',  // Lighter Blue
            'rgba(147, 51, 234, 0.15)',  // Violet
            'rgba(67, 56, 202, 0.15)',   // Deep Indigo
        ]
        : [
            'rgba(99, 102, 241, 0.08)',   // Indigo
            'rgba(79, 70, 229, 0.08)',    // Darker Indigo
            'rgba(59, 130, 246, 0.08)',   // Blue
            'rgba(147, 51, 234, 0.08)',   // Violet
            'rgba(67, 56, 202, 0.08)',    // Deep Indigo
        ];

    // Set canvas size
    const dpr = window.devicePixelRatio || 1;
    canvas.width = window.innerWidth * dpr;
    canvas.height = window.innerHeight * dpr;
    canvas.style.width = `${window.innerWidth}px`;
    canvas.style.height = `${window.innerHeight}px`;
    ctx.scale(dpr, dpr);

    // Initialize circles
    circles = Array.from({ length: 12 }, () => {
        const radius = Math.random() * 150 + 80;
        return {
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            radius,
            dx: (Math.random() - 0.5) * 0.8,
            dy: (Math.random() - 0.5) * 0.8,
            color: colors[Math.floor(Math.random() * colors.length)],
            baseX: 0,
            baseY: 0,
            follows: true,
            scale: 1
        };
    });

    // Store initial positions
    circles.forEach(circle => {
        circle.baseX = circle.x;
        circle.baseY = circle.y;
    });

    // Mouse move handler
    canvas.addEventListener('mousemove', (e) => {
        const rect = canvas.getBoundingClientRect();
        mousePosition = {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    });

    // Start animation
    animate();
}

function drawCircle(circle) {
    ctx.beginPath();
    const gradient = ctx.createRadialGradient(
        circle.x, circle.y, 0,
        circle.x, circle.y, circle.radius
    );
    gradient.addColorStop(0, circle.color);
    gradient.addColorStop(1, 'rgba(0, 0, 0, 0)');
    ctx.fillStyle = gradient;
    ctx.arc(circle.x, circle.y, circle.radius, 0, Math.PI * 2);
    ctx.fill();
}

function animate() {
    if (!canvas || !ctx) return;

    // Set background color based on theme
    ctx.fillStyle = theme === 'dark' ? '#0a0f1f' : '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    circles.forEach(circle => {
        // Update position
        circle.x += circle.dx;
        circle.y += circle.dy;

        // Mouse interaction
        const dx = mousePosition.x - circle.x;
        const dy = mousePosition.y - circle.y;
        const distance = Math.sqrt(dx * dx + dy * dy);
        
        if (distance < 300) {
            const angle = Math.atan2(dy, dx);
            const force = (300 - distance) / 300;
            circle.x -= Math.cos(angle) * force * 2;
            circle.y -= Math.sin(angle) * force * 2;
        }

        // Boundary check
        if (circle.x + circle.radius > canvas.width || circle.x - circle.radius < 0) {
            circle.dx = -circle.dx;
        }
        if (circle.y + circle.radius > canvas.height || circle.y - circle.radius < 0) {
            circle.dy = -circle.dy;
        }

        drawCircle(circle);
    });

    requestAnimationFrame(animate);
}

// Handle window resize
window.addEventListener('resize', () => {
    if (canvas) {
        const dpr = window.devicePixelRatio || 1;
        canvas.width = window.innerWidth * dpr;
        canvas.height = window.innerHeight * dpr;
        canvas.style.width = `${window.innerWidth}px`;
        canvas.style.height = `${window.innerHeight}px`;
        ctx.scale(dpr, dpr);
    }
});

// Mobile Menu
function initMobileMenu() {
    const menuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    const closeButton = mobileMenu?.querySelector('.mobile-close');
    let isOpen = false;

    function toggleMenu(open) {
        isOpen = open;
        if (mobileMenu) {
            mobileMenu.style.transform = isOpen ? 'translateX(0)' : 'translateX(100%)';
        }
    }

    if (menuButton && mobileMenu) {
        // Open menu
        menuButton.addEventListener('click', () => {
            toggleMenu(true);
        });

        // Close menu
        closeButton?.addEventListener('click', () => {
            toggleMenu(false);
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (isOpen && 
                !menuButton.contains(e.target) && 
                !mobileMenu.contains(e.target)) {
                toggleMenu(false);
            }
        });

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && isOpen) {
                toggleMenu(false);
            }
        });

        // Prevent scrolling when menu is open
        const observer = new MutationObserver(() => {
            document.body.style.overflow = isOpen ? 'hidden' : '';
        });

        observer.observe(mobileMenu, { attributes: true, attributeFilter: ['style'] });
    }
}

// Course Sidebar Toggle
document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const courseSidebar = document.querySelector('.course-sidebar');
    
    if (menuToggle && courseSidebar) {
        menuToggle.addEventListener('click', () => {
            courseSidebar.classList.toggle('active');
            // Update aria-expanded state
            const isExpanded = courseSidebar.classList.contains('active');
            menuToggle.setAttribute('aria-expanded', isExpanded);
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (!courseSidebar.contains(e.target) && 
                !menuToggle.contains(e.target) && 
                courseSidebar.classList.contains('active')) {
                courseSidebar.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Handle module expansion
        const moduleHeaders = document.querySelectorAll('.module-header');
        moduleHeaders.forEach(header => {
            header.addEventListener('click', () => {
                const module = header.closest('.course-module');
                const lessons = module.querySelector('.module-lessons');
                const icon = header.querySelector('i');
                
                // Toggle lessons visibility
                lessons.style.display = lessons.style.display === 'none' ? 'flex' : 'none';
                
                // Rotate icon
                icon.style.transform = lessons.style.display === 'none' ? 'rotate(0deg)' : 'rotate(90deg)';
            });
        });

        // Handle lesson selection
        const lessonItems = document.querySelectorAll('.lesson-item');
        lessonItems.forEach(item => {
            item.addEventListener('click', () => {
                // Remove active class from all lessons
                lessonItems.forEach(lesson => lesson.classList.remove('active'));
                // Add active class to clicked lesson
                item.classList.add('active');
                
                // On mobile, close the sidebar after selecting a lesson
                if (window.innerWidth <= 1024) {
                    courseSidebar.classList.remove('active');
                    menuToggle.setAttribute('aria-expanded', 'false');
                }
            });
        });
    }
});

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    document.body.className = `min-h-screen ${theme}`;
    initCanvas();
    initMobileMenu();
});
