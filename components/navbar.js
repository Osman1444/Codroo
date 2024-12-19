function createNavbar() {
    return `
    <header class="header">
        <div class="container">
            <div class="header-content">
                <!-- Logo -->
                <a href="/" class="logo">
                    <img src="/assets/images/logo.png" alt="Codro Logo" class="logo-icon">
                    <span class="logo-text">Codro</span>
                </a>

                <!-- Desktop Navigation -->
                <nav class="desktop-nav">
                    <a href="courses.html" class="nav-link">Courses</a>
                    <a href="community.html" class="nav-link">Community</a>
                    <button onclick="toggleTheme()" class="theme-toggle">
                        <i data-lucide="sun" class="sun-icon"></i>
                        <i data-lucide="moon" class="moon-icon"></i>
                    </button>
                    <a href="sign-in.html" class="nav-link">Sign In</a>
                    <a href="sign-up.html" class="btn btn-primary">Sign Up</a>
                </nav>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-button" class="mobile-menu-button">
                    <i data-lucide="menu"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="mobile-menu">
            <div class="container">
                <div class="mobile-menu-header">
                    <a href="/" class="logo">
                        <img src="/assets/images/logo.png" alt="Codro Logo" class="logo-icon">
                        <span class="logo-text">Codro</span>
                    </a>
                    <button id="mobile-close" class="mobile-close">
                        <i data-lucide="x"></i>
                    </button>
                </div>
                <nav class="mobile-nav">
                    <a href="courses.html" class="nav-link">Courses</a>
                    <a href="community.html" class="nav-link">Community</a>
                    <a href="sign-in.html" class="nav-link">Sign In</a>
                    <a href="sign-up.html" class="btn btn-primary">Sign Up</a>
                    <button onclick="toggleTheme()" class="theme-toggle-mobile">
                        <i data-lucide="sun" class="sun-icon"></i>
                        <i data-lucide="moon" class="moon-icon"></i>
                        <span>Toggle Theme</span>
                    </button>
                </nav>
            </div>
        </div>
    </header>`;
}

// Function to initialize the navbar
function initNavbar() {
    // Insert navbar at the start of the body
    document.body.insertAdjacentHTML('afterbegin', createNavbar());
    
    // Initialize mobile menu functionality
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileCloseButton = document.getElementById('mobile-close');
    const mobileMenu = document.getElementById('mobile-menu');

    mobileMenuButton?.addEventListener('click', () => {
        mobileMenu.style.transform = 'translateX(0)';
    });

    mobileCloseButton?.addEventListener('click', () => {
        mobileMenu.style.transform = 'translateX(100%)';
    });
}

// Export the initialization function
export { initNavbar }; 