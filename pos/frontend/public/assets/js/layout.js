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
            badge.textContent = count > 0 ? count : '';
            badge.style.display = count > 0 ? 'block' : 'none';
        }
    }
    
    showNotifications() {
        const notifications = [
            { title: 'New order #12345', time: '2 min ago', unread: true },
            { title: 'Low stock alert: Running Shoes', time: '1 hour ago', unread: true },
            { title: 'Daily sales report ready', time: '3 hours ago', unread: false }
        ];
        
        const notifHtml = notifications.map(n => 
            `<div style="padding: 12px; border-bottom: 1px solid #e2e8f0; ${n.unread ? 'background: #eff6ff;' : ''}">
                <div style="font-weight: 500;">${n.title}</div>
                <div style="font-size: 11px; color: #64748b;">${n.time}</div>
            </div>`
        ).join('');
        
        const modal = document.createElement('div');
        modal.className = 'notification-modal';
        modal.style.cssText = `
            position: fixed; top: 70px; right: 20px; width: 300px;
            background: white; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            z-index: 1000; overflow: hidden;
        `;
        modal.innerHTML = `<div style="padding: 12px; background: #f1f5f9; font-weight: 600;">Notifications</div>${notifHtml}<div style="padding: 8px; text-align: center;"><a href="#" style="color: #2563eb; text-decoration: none; font-size: 12px;">View all</a></div>`;
        document.body.appendChild(modal);
        
        setTimeout(() => modal.remove(), 5000);
        modal.addEventListener('click', () => modal.remove());
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
        window.location.href = `/search.html?q=${encodeURIComponent(query)}`;
    }
    
    setupResponsive() {
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && this.isMobileMenuOpen) {
                this.closeMobileMenu();
            }
        });
    }
    
    closeMobileMenu() {
        this.isMobileMenuOpen = false;
        if (this.sidebar) {
            this.sidebar.classList.remove('mobile-open');
        }
        document.body.style.overflow = '';
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
}

// Initialize layout when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.layout = new LayoutController();
});

// Keyboard shortcut: Ctrl+Shift+L for logout
document.addEventListener('keydown', (e) => {
    if (e.ctrlKey && e.shiftKey && e.key === 'L') {
        e.preventDefault();
        if (confirm('Quick logout?')) {
            window.location.href = '/logout.html';
        }
    }
});
