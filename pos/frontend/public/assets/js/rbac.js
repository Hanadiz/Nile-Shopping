/**
 * Nile POS - Role-Based Access Control (RBAC)
 * Version: 2.0.0
 */

class RBACManager {
    constructor() {
        this.currentUser = null;
        this.permissions = [];
        this.roleHierarchy = {
            'admin': 100,
            'manager': 80,
            'supervisor': 60,
            'cashier': 40,
            'stock_clerk': 30,
            'viewer': 10
        };
        
        this.tierLevels = {
            'freemium': 0,
            'basic': 1,
            'professional': 2,
            'premium': 3,
            'enterprise': 4
        };
        
        this.permissionMatrix = {
            'dashboard.view': { roles: ['admin', 'manager', 'supervisor', 'cashier', 'stock_clerk', 'viewer'], tier: 'freemium' },
            'pos.access': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'transaction.create': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'transaction.view_own': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'transaction.view_all': { roles: ['admin', 'manager', 'supervisor'], tier: 'professional' },
            'transaction.void': { roles: ['admin', 'manager', 'supervisor'], tier: 'basic' },
            'transaction.refund': { roles: ['admin', 'manager', 'supervisor'], tier: 'basic' },
            'product.view': { roles: ['admin', 'manager', 'supervisor', 'cashier', 'stock_clerk', 'viewer'], tier: 'freemium' },
            'product.create': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'basic' },
            'product.update': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'basic' },
            'product.delete': { roles: ['admin', 'manager'], tier: 'professional' },
            'inventory.view': { roles: ['admin', 'manager', 'supervisor', 'stock_clerk'], tier: 'basic' },
            'inventory.count': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'basic' },
            'inventory.adjust': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'professional' },
            'customer.view': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'customer.create': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'customer.update': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'report.sales': { roles: ['admin', 'manager', 'supervisor'], tier: 'basic' },
            'report.inventory': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'professional' },
            'settings.view': { roles: ['admin', 'manager'], tier: 'basic' },
            'settings.update': { roles: ['admin'], tier: 'professional' },
            'user.view': { roles: ['admin', 'manager'], tier: 'professional' },
            'user.create': { roles: ['admin'], tier: 'premium' },
            'offline.access': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'basic' },
            'loyalty.view': { roles: ['admin', 'manager'], tier: 'professional' },
            'giftcard.view': { roles: ['admin', 'manager', 'cashier'], tier: 'basic' },
            'system.audit': { roles: ['admin'], tier: 'professional' },
            'api.access': { roles: ['admin', 'manager'], tier: 'professional' },
            'tenant.view': { roles: ['admin'], tier: 'enterprise' },
            'compliance.view': { roles: ['admin'], tier: 'enterprise' }
        };
        
        this.init();
    }
    
    init() {
        this.loadCurrentUser();
        this.loadPermissions();
    }
    
    loadCurrentUser() {
        const userStr = localStorage.getItem('pos_user');
        if (userStr) {
            this.currentUser = JSON.parse(userStr);
        } else {
            // Demo user for testing
            this.currentUser = {
                id: 1,
                name: 'Demo User',
                email: 'demo@nile.com',
                role: 'cashier',
                tier: 'freemium'
            };
        }
    }
    
    loadPermissions() {
        const storedPermissions = localStorage.getItem('pos_permissions');
        if (storedPermissions) {
            this.permissions = JSON.parse(storedPermissions);
        } else if (this.currentUser) {
            this.permissions = this.getPermissionsForRole(this.currentUser.role);
            localStorage.setItem('pos_permissions', JSON.stringify(this.permissions));
        }
    }
    
    getPermissionsForRole(role) {
        const permissions = [];
        for (const [permission, config] of Object.entries(this.permissionMatrix)) {
            if (config.roles.includes(role)) {
                permissions.push(permission);
            }
        }
        return permissions;
    }
    
    hasPermission(permission) {
        if (!this.currentUser) return false;
        if (this.currentUser.role === 'admin') return true;
        return this.permissions.includes(permission);
    }
    
    hasAnyPermission(permissions) {
        return permissions.some(perm => this.hasPermission(perm));
    }
    
    getRoleLevel() {
        return this.roleHierarchy[this.currentUser?.role] || 0;
    }
    
    isAtLeast(role) {
        return this.getRoleLevel() >= (this.roleHierarchy[role] || 0);
    }
    
    getTierLevel() {
        return this.tierLevels[this.currentUser?.tier] || 0;
    }
    
    hasTierAccess(requiredTier) {
        return this.getTierLevel() >= (this.tierLevels[requiredTier] || 0);
    }
    
    getSidebarMenu() {
        const allMenuItems = [
            { icon: '📊', text: 'Dashboard', path: '/dashboard.html', permission: 'dashboard.view' },
            { icon: '🛒', text: 'Point of Sale', path: '/index.html', permission: 'pos.access' },
            { icon: '📦', text: 'Products', path: '/products.html', permission: 'product.view' },
            { icon: '📋', text: 'Inventory', path: '/inventory-count.html', permission: 'inventory.view' },
            { icon: '👥', text: 'Customers', path: '/customer-management.html', permission: 'customer.view' },
            { icon: '🔄', text: 'Returns', path: '/returns-refunds.html', permission: 'transaction.refund' },
            { icon: '🎁', text: 'Gift Cards', path: '/gift-cards.html', permission: 'giftcard.view', tier: 'basic' },
            { icon: '⭐', text: 'Loyalty', path: '/loyalty.html', permission: 'loyalty.view', tier: 'professional' },
            { icon: '📊', text: 'Reports', path: '/reports-custom.html', permission: 'report.sales' },
            { icon: '👔', text: 'Time Clock', path: '/time-clock.html', permission: 'employee.view', tier: 'professional' },
            { icon: '🏢', text: 'Fleet', path: '/fleet-management.html', permission: 'store.view', tier: 'professional' },
            { icon: '🔧', text: 'Settings', path: '/settings.html', permission: 'settings.view' },
            { icon: '🔒', text: 'Audit Log', path: '/audit-log.html', permission: 'system.audit', tier: 'professional' },
            { icon: '📡', text: 'Offline', path: '/offline.html', permission: 'offline.access', tier: 'basic' },
            { icon: '🏪', text: 'Tenants', path: '/tenants.html', permission: 'tenant.view', tier: 'enterprise' },
            { icon: '⚖️', text: 'Compliance', path: '/compliance.html', permission: 'compliance.view', tier: 'enterprise' }
        ];
        
        return allMenuItems.filter(item => {
            if (item.permission && !this.hasPermission(item.permission)) return false;
            if (item.tier && !this.hasTierAccess(item.tier)) return false;
            return true;
        });
    }
    
    updateUIByPermissions() {
        document.querySelectorAll('[data-permission]').forEach(el => {
            const perm = el.dataset.permission;
            if (!this.hasPermission(perm)) {
                el.style.display = 'none';
            }
        });
        
        document.querySelectorAll('[data-role]').forEach(el => {
            const role = el.dataset.role;
            if (!this.isAtLeast(role)) {
                el.style.display = 'none';
            }
        });
        
        document.querySelectorAll('[data-tier]').forEach(el => {
            const tier = el.dataset.tier;
            if (!this.hasTierAccess(tier)) {
                el.style.display = 'none';
            }
        });
        
        // Update active nav item
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-item').forEach(item => {
            const href = item.getAttribute('href');
            if (href && currentPath.includes(href)) {
                item.classList.add('active');
            }
        });
    }
    
    renderSidebar() {
        const menuItems = this.getSidebarMenu();
        const sidebarNav = document.querySelector('.sidebar-nav');
        if (!sidebarNav) return;
        
        const sections = {
            main: { title: 'Main', items: [] },
            sales: { title: 'Sales', items: [] },
            inventory: { title: 'Inventory', items: [] },
            customers: { title: 'Customers', items: [] },
            reports: { title: 'Reports', items: [] },
            settings: { title: 'Settings', items: [] },
            system: { title: 'System', items: [] }
        };
        
        menuItems.forEach(item => {
            if (item.text === 'Dashboard') sections.main.items.push(item);
            else if (item.text === 'Point of Sale') sections.sales.items.push(item);
            else if (['Products', 'Inventory'].includes(item.text)) sections.inventory.items.push(item);
            else if (['Customers', 'Loyalty', 'Gift Cards'].includes(item.text)) sections.customers.items.push(item);
            else if (['Reports', 'Returns'].includes(item.text)) sections.reports.items.push(item);
            else if (['Settings', 'Time Clock', 'Fleet'].includes(item.text)) sections.settings.items.push(item);
            else sections.system.items.push(item);
        });
        
        let html = '';
        for (const [key, section] of Object.entries(sections)) {
            if (section.items.length > 0) {
                html += `
                    <div class="nav-section">
                        <div class="nav-section-title">${section.title}</div>
                        ${section.items.map(item => `
                            <a href="${item.path}" class="nav-item">
                                <span class="nav-icon">${item.icon}</span>
                                <span class="nav-text">${item.text}</span>
                            </a>
                        `).join('')}
                    </div>
                `;
            }
        }
        
        sidebarNav.innerHTML = html;
        this.updateUIByPermissions();
    }
    
    getCurrentUser() {
        return this.currentUser;
    }
    
    getTierLimits() {
        const limits = {
            freemium: { maxProducts: 500, maxTransactions: 100, maxRegisters: 1, offline: false },
            basic: { maxProducts: 5000, maxTransactions: 5000, maxRegisters: 2, offline: true },
            professional: { maxProducts: 50000, maxTransactions: 50000, maxRegisters: 5, offline: true },
            premium: { maxProducts: 500000, maxTransactions: 999999, maxRegisters: 999, offline: true },
            enterprise: { maxProducts: 9999999, maxTransactions: 9999999, maxRegisters: 9999, offline: true }
        };
        return limits[this.currentUser?.tier || 'freemium'];
    }
}

// Initialize globally
window.rbac = new RBACManager();
