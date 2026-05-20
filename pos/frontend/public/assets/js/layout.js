/**
 * Nile POS - Layout Controller
 * Version: 2.0.0
 */

class LayoutController {
    constructor() {
        this.sidebar = document.querySelector('.sidebar');
        this.mainContent = document.querySelector('.main-content');
        this.isSidebarCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
        this.isMobileMenuOpen = false;
        
        this.init();
    }
    
    init() {
        this.setupSidebar();
        this.setupHeader();
        this.setupUserDropdown();
        this.setupNotifications();
        this.setupResponsive();
        this.setupBreadcrumb();
        this.applyStoredState();
        
        // Load components if using dynamic loading
        if (document.getElementById('sidebarContainer')) {
            this.loadComponents();
        }
    }
    
    async loadComponents() {
        try {
            // Load sidebar if container exists
            if (document.getElementById('sidebarContainer') && !document.querySelector('.sidebar')) {
                const sidebarResp = await fetch('/components/Sidebar.html');
                const sidebarHtml = await sidebarResp.text();
                document.getElementById('sidebarContainer').innerHTML = sidebarHtml;
                this.sidebar = document.querySelector('.sidebar');
                this.setupSidebar();
            }
            
            // Load header if container exists
            if (document.getElementById('headerContainer') && !document.querySelector('.app-header')) {
                const headerResp = await fetch('/components/Header.html');
                const headerHtml = await headerResp.text();
                document.getElementById('headerContainer').innerHTML = headerHtml;
                this.setupHeader();
                this.setupUserDropdown();
            }
            
            // Load footer if container exists
            if (document.getElementById('footerContainer') && !document.querySelector('.app-footer')) {
                const footerResp = await fetch('/components/Footer.html');
                const footerHtml = await footerResp.text();
                document.getElementById('footerContainer').innerHTML = footerHtml;
            }
            
            // Load hero if container exists
            if (document.getElementById('heroContainer') && !document.querySelector('.hero-section')) {
                const heroResp = await fetch('/components/Hero.html');
                const heroHtml = await heroResp.text();
                document.getElementById('heroContainer').innerHTML = heroHtml;
            }
            
            // Reinitialize RBAC after components load
            if (window.rbac) {
                window.rbac.renderSidebar();
                window.rbac.updateUIByPermissions();
            }
            
            // Update user info
            this.updateUserInfo();
            
        } catch (error) {
            console.warn('Could not load components:', error);
        }
    }
    
    setupSidebar() {
        if (!this.sidebar) return;
        
        // Create toggle button if not exists
        if (!document.querySelector('.sidebar-toggle')) {
            const toggleBtn = document.createElement('div');
            toggleBtn.className = 'sidebar-toggle';
            toggleBtn.innerHTML = '◀';
            toggleBtn.addEventListener('click', () => this.toggleSidebar());
            this.sidebar.appendChild(toggleBtn);
        }
        
        // Render sidebar menu if RBAC available
        if (window.rbac) {
            window.rbac.renderSidebar();
        }
    }
    
    toggleSidebar() {
        this.isSidebarCollapsed = !this.isSidebarCollapsed;
        localStorage.setItem('sidebar_collapsed', this.isSidebarCollapsed);
        
        if (this.sidebar) {
            this.sidebar.classList.toggle('collapsed', this.isSidebarCollapsed);
        }
        if (this.mainContent) {
            this.mainContent.classList.toggle('expanded', this.isSidebarCollapsed);
        }
    }
    
    setupHeader() {
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        if (mobileToggle) {
            mobileToggle.addEventListener('click', () => this.toggleMobileMenu());
        }
        
        const searchInput = document.querySelector('.header-search input');
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.handleGlobalSearch(e.target.value);
                }
            });
        }
    }
    
    toggleMobileMenu() {
        this.isMobileMenuOpen = !this.isMobileMenuOpen;
        if (this.sidebar) {
            this.sidebar.classList.toggle('mobile-open', this.isMobileMenuOpen);
        }
        document.body.style.overflow = this.isMobileMenuOpen ? 'hidden' : '';
    }
    
    setupUserDropdown() {
        const userDropdown = document.querySelector('.user-dropdown');
        if (userDropdown) {
            userDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('active');
            });
            
            document.addEventListener('click', () => {
                userDropdown.classList.remove('active');
            });
        }
        
        // Setup logout button
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (confirm('Are you sure you want to logout?')) {
                    window.location.href = '/logout.html';
                }
            });
        }
        
        this.updateUserInfo();
    }
    
    updateUserInfo() {
        const user = window.rbac?.getCurrentUser();
        if (user) {
            const userNameSpans = document.querySelectorAll('.user-name');
            const userRoleSpans = document.querySelectorAll('.user-role');
            const userAvatars = document.querySelectorAll('.user-avatar');
            
            userNameSpans.forEach(span => {
                span.textContent = user.name || user.email?.split('@')[0] || 'User';
            });
            
            userRoleSpans.forEach(span => {
                span.textContent = user.role?.toUpperCase() || 'Cashier';
            });
            
            userAvatars.forEach(avatar => {
                avatar.textContent = (user.name?.charAt(0) || 'U').toUpperCase();
            });
        }
    }
    
    setupNotifications() {
        const notificationBtn = document.querySelector('.notification-btn');
        if (notificationBtn) {
            notificationBtn.addEventListener('click', () => this.showNotifications());
        }
        this.updateNotificationBadge();
    }
    
    updateNotificationBadge() {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            const count = Math.floor(Math.random() * 5);
