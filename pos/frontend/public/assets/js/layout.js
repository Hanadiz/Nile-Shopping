/**
 * Nile POS - Layout Controller
 * Manages sidebar, header, and overall layout behavior
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
    }
    
    setupSidebar() {
        // Create sidebar toggle button
        const toggleBtn = document.createElement('div');
        toggleBtn.className = 'sidebar-toggle';
        toggleBtn.innerHTML = '◀';
        toggleBtn.addEventListener('click', () => this.toggleSidebar());
        this.sidebar?.appendChild(toggleBtn);
        
        // Mobile close button
        const closeBtn = document.createElement('div');
        closeBtn.className = 'mobile-close';
        closeBtn.innerHTML = '✕';
        closeBtn.style.cssText = 'display:none; position:absolute; top:16px; right:16px; font-size:24px; cursor:pointer;';
        closeBtn.addEventListener('click', () => this.closeMobileMenu());
        this.sidebar?.appendChild(closeBtn);
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
        // Mobile menu toggle
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        if (mobileToggle) {
            mobileToggle.addEventListener('click', () => this.toggleMobileMenu());
        }
        
        // Header search
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
    
    closeMobileMenu() {
        this.isMobileMenuOpen = false;
        if (this.sidebar) {
            this.sidebar.classList.remove('mobile-open');
        }
        document.body.style.overflow = '';
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
        
        // Populate user info
        this.updateUserInfo();
    }
    
    updateUserInfo() {
        const user = window.rbac?.getCurrentUser();
        if (user) {
            const userNameSpan = document.querySelector('.user-name');
            const userRoleSpan = document.querySelector('.user-role');
            const userAvatar = document.querySelector('.user-avatar');
            
            if (userNameSpan) userNameSpan.textContent = user.name || user.email?.split('@')[0];
            if (userRoleSpan) userRoleSpan.textContent = user.role?.toUpperCase() || 'User';
            if (userAvatar) userAvatar.textContent = (user.name?.charAt(0) || 'U').toUpperCase();
        }
    }
    
    setupNotifications() {
        const notificationBtn = document.querySelector('.notification-btn');
        if (notificationBtn) {
            notificationBtn.addEventListener('click', () => this.showNotifications());
        }
        
        // Fetch notification count
        this.updateNotificationBadge();
    }
    
    updateNotificationBadge() {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            // Demo: random count or fetch from API
            const count = Math.floor(Math.random() * 5);
            badge.textContent = count > 0 ? count : '';
            badge.style.display = count > 0 ? 'block' : 'none';
        }
    }
    
    showNotifications() {
        // Show notification panel
        const notifications = [
            { title: 'New order #12345', time: '2 min ago', unread: true },
            { title: 'Low stock alert: Running Shoes', time: '1 hour ago', unread: true },
            { title: 'Daily sales report ready', time: '3 hours ago', unread: false }
        ];
        
        // Create modal or dropdown
        alert('Notifications:\n' + notifications.map(n => `• ${n.title} (${n.time})`).join('\n'));
    }
    
    setupBreadcrumb() {
        const breadcrumb = document.querySelector('.breadcrumb');
        if (breadcrumb) {
            const path = window.location.pathname;
            const pageName = path.split('/').pop().replace('.html', '').replace(/-/g, ' ');
            const pageTitle = pageName.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
            
            const titleEl = document.querySelector('.page-title');
            if (titleEl) titleEl.textContent = pageTitle;
        }
    }
    
    handleGlobalSearch(query) {
        if (query.length < 2) return;
        
        // Search through products, customers, transactions
        console.log('Searching for:', query);
        // Implement search functionality
        alert(`Search results for "${query}" would appear here`);
    }
    
    setupResponsive() {
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && this.isMobileMenuOpen) {
                this.closeMobileMenu();
            }
        });
    }
    
    applyStoredState() {
        if (this.isSidebarCollapsed) {
            this.sidebar?.classList.add('collapsed');
            this.mainContent?.classList.add('expanded');
        }
    }
    
    updatePageTitle(title) {
        document.title = `${title} | Nile POS`;
        const pageTitleEl = document.querySelector('.page-title');
        if (pageTitleEl) pageTitleEl.textContent = title;
    }
    
    setActiveNavItem() {
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-item').forEach(item => {
            const href = item.getAttribute('href');
            if (href && currentPath.includes(href)) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }
}

// Initialize layout when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.layout = new LayoutController();
    window.layout.setActiveNavItem();
});
