/**
 * Nile POS - Role-Based Access Control (RBAC)
 * Manages user permissions, menu visibility, and feature access
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
        
        // Complete permission matrix
        this.permissionMatrix = {
            // Dashboard permissions
            'dashboard.view': { roles: ['admin', 'manager', 'supervisor', 'cashier', 'stock_clerk', 'viewer'], tier: 'freemium' },
            'dashboard.real_time': { roles: ['admin', 'manager', 'supervisor'], tier: 'professional' },
            
            // POS / Transaction permissions
            'pos.access': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'transaction.create': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'transaction.view_own': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'transaction.view_all': { roles: ['admin', 'manager', 'supervisor'], tier: 'professional' },
            'transaction.void': { roles: ['admin', 'manager', 'supervisor'], tier: 'basic' },
            'transaction.refund': { roles: ['admin', 'manager', 'supervisor'], tier: 'basic' },
            'transaction.refund_max': { roles: ['admin'], tier: 'premium' },
            
            // Product permissions
            'product.view': { roles: ['admin', 'manager', 'supervisor', 'cashier', 'stock_clerk', 'viewer'], tier: 'freemium' },
            'product.create': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'basic' },
            'product.update': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'basic' },
            'product.delete': { roles: ['admin', 'manager'], tier: 'professional' },
            'product.import': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'professional' },
            'product.export': { roles: ['admin', 'manager'], tier: 'professional' },
            
            // Inventory permissions
            'inventory.view': { roles: ['admin', 'manager', 'supervisor', 'stock_clerk'], tier: 'basic' },
            'inventory.count': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'basic' },
            'inventory.adjust': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'professional' },
            'inventory.transfer': { roles: ['admin', 'manager'], tier: 'professional' },
            
            // Customer permissions
            'customer.view': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'customer.create': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'customer.update': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'customer.delete': { roles: ['admin', 'manager'], tier: 'professional' },
            'customer.loyalty': { roles: ['admin', 'manager'], tier: 'professional' },
            
            // Report permissions
            'report.sales': { roles: ['admin', 'manager', 'supervisor'], tier: 'basic' },
            'report.inventory': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'professional' },
            'report.employee': { roles: ['admin', 'manager'], tier: 'professional' },
            'report.advanced': { roles: ['admin', 'manager'], tier: 'premium' },
            'report.export': { roles: ['admin', 'manager'], tier: 'professional' },
            
            // User management
            'user.view': { roles: ['admin', 'manager'], tier: 'professional' },
            'user.create': { roles: ['admin'], tier: 'premium' },
            'user.update': { roles: ['admin', 'manager'], tier: 'professional' },
            'user.delete': { roles: ['admin'], tier: 'premium' },
            'user.role_assign': { roles: ['admin'], tier: 'premium' },
            
            // Settings
            'settings.view': { roles: ['admin', 'manager'], tier: 'basic' },
            'settings.update': { roles: ['admin'], tier: 'professional' },
            'settings.system': { roles: ['admin'], tier: 'enterprise' },
            
            // Store management
            'store.view': { roles: ['admin', 'manager'], tier: 'professional' },
            'store.create': { roles: ['admin'], tier: 'enterprise' },
            'store.update': { roles: ['admin'], tier: 'enterprise' },
            
            // API & Integrations
            'api.access': { roles: ['admin', 'manager'], tier: 'professional' },
            'webhook.manage': { roles: ['admin'], tier: 'premium' },
            
            // Backup & System
            'system.backup': { roles: ['admin'], tier: 'premium' },
            'system.restore': { roles: ['admin'], tier: 'enterprise' },
            'system.audit': { roles: ['admin'], tier: 'professional' }
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
    
    hasAllPermissions(permissions) {
        return permissions.every(perm => this.hasPermission(perm));
    }
    
    getRoleLevel() {
        return this.roleHierarchy[this.currentUser?.role] || 0;
    }
    
    isAtLeast(role) {
        return this.getRoleLevel() >= (this.roleHierarchy[role] || 0);
    }
    
    getFeatureTier(feature) {
        for (const [perm, config] of Object.entries(this.permissionMatrix)) {
            if (perm === feature) {
                return config.tier;
            }
        }
        return 'enterprise';
    }
    
    canAccessRoute(route) {
        const routePermissions = this.getRoutePermissions(route);
        if (!routePermissions || routePermissions.length === 0) return true;
        return this.hasAnyPermission(routePermissions);
    }
    
    getRoutePermissions(route) {
        const routeMap = {
            '/': ['dashboard.view'],
            '/dashboard': ['dashboard.view'],
            '/pos': ['pos.access', 'transaction.create'],
            '/products': ['product.view'],
            '/products/new': ['product.create'],
            '/inventory': ['inventory.view'],
            '/inventory/count': ['inventory.count'],
            '/customers': ['customer.view'],
            '/customers/new': ['customer.create'],
            '/transactions': ['transaction.view_own', 'transaction.view_all'],
            '/reports': ['report.sales'],
            '/reports/advanced': ['report.advanced'],
            '/settings': ['settings.view'],
            '/users': ['user.view'],
            '/api': ['api.access'],
            '/backup': ['system.backup']
        };
        return routeMap[route] || [];
    }
    
    filterMenuByPermissions(menuItems) {
        return menuItems.filter(item => {
            if (item.permission) {
                return this.hasPermission(item.permission);
            }
            if (item.permissions) {
                return this.hasAnyPermission(item.permissions);
            }
            if (item.role) {
                return this.isAtLeast(item.role);
            }
            return true;
        });
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
            { icon: '⭐', text: 'Loyalty', path: '/loyalty.html', permission: 'customer.loyalty', tier: 'professional' },
            { icon: '📊', text: 'Reports', path: '/reports-custom.html', permission: 'report.sales' },
            { icon: '🎟️', text: 'Promotions', path: '/promotions.html', permission: 'promotion.view', tier: 'premium' },
            { icon: '👔', text: 'Staff', path: '/time-clock.html', permission: 'employee.view', tier: 'professional' },
            { icon: '🏢', text: 'Fleet', path: '/fleet-management.html', permission: 'store.view', tier: 'professional' },
            { icon: '🔧', text: 'Settings', path: '/settings.html', permission: 'settings.view' },
            { icon: '👥', text: 'Users', path: '/users.html', permission: 'user.view', tier: 'professional' },
            { icon: '🔒', text: 'Audit Log', path: '/audit-log.html', permission: 'system.audit', tier: 'professional' },
            { icon: '🔄', text: 'Offline', path: '/offline.html', permission: 'offline.access', tier: 'basic' },
            { icon: '🏪', text: 'Tenants', path: '/tenants.html', permission: 'tenant.view', tier: 'enterprise' },
            { icon: '⚖️', text: 'Compliance', path: '/compliance.html', permission: 'compliance.view', tier: 'enterprise' }
        ];
        
        // Filter by permission and tier
        return allMenuItems.filter(item => {
            // Check permission
            if (item.permission && !this.hasPermission(item.permission)) return false;
            
            // Check tier requirement
            if (item.tier) {
                const userTier = this.currentUser?.tier || 'freemium';
                const tierLevels = { freemium: 0, basic: 1, professional: 2, premium: 3, enterprise: 4 };
                if (tierLevels[userTier] < tierLevels[item.tier]) return false;
            }
            
            return true;
        });
    }
    
    updateUIByPermissions() {
        // Hide/show sidebar menu items
        const menuItems = this.getSidebarMenu();
        const sidebarNav = document.querySelector('.sidebar-nav');
        if (sidebarNav) {
            // Re-render sidebar based on permissions
            this.renderSidebar(menuItems);
        }
        
        // Hide/show action buttons
        document.querySelectorAll('[data-permission]').forEach(el => {
            const requiredPerm = el.dataset.permission;
            if (!this.hasPermission(requiredPerm)) {
                el.style.display = 'none';
            }
        });
        
        // Hide/show role-based elements
        document.querySelectorAll('[data-role]').forEach(el => {
            const requiredRole = el.dataset.role;
            if (!this.isAtLeast(requiredRole)) {
                el.style.display = 'none';
            }
        });
        
        // Hide/show tier-based elements
        document.querySelectorAll('[data-tier]').forEach(el => {
            const requiredTier = el.dataset.tier;
            const userTier = this.currentUser?.tier || 'freemium';
            const tierLevels = { freemium: 0, basic: 1, professional: 2, premium: 3, enterprise: 4 };
            if (tierLevels[userTier] < tierLevels[requiredTier]) {
                el.style.display = 'none';
            }
        });
    }
    
    renderSidebar(menuItems) {
        const sidebarNav = document.querySelector('.sidebar-nav');
        if (!sidebarNav) return;
        
        // Group menu items by section
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
            else if (['Settings', 'Staff', 'Fleet'].includes(item.text)) sections.settings.items.push(item);
            else sections.system.items.push(item);
        });
        
        let html = '';
        for (const [key, section] of Object.entries(sections)) {
            if (section.items.length > 0) {
                html += `
                    <div class="nav-section">
                        <div class="nav-section-title">${section.title}</div>
                        ${section.items.map(item => `
                            <a href="${item.path}" class="nav-item ${window.location.pathname === item.path ? 'active' : ''}">
                                <span class="nav-icon">${item.icon}</span>
                                <span class="nav-text">${item.text}</span>
                            </a>
                        `).join('')}
                    </div>
                `;
            }
        }
        
        sidebarNav.innerHTML = html;
    }
    
    getCurrentUser() {
        return this.currentUser;
    }
    
    getTierLimits() {
        const tiers = {
            freemium: { maxProducts: 500, maxTransactions: 100, maxRegisters: 1, offline: false },
            basic: { maxProducts: 5000, maxTransactions: 5000, maxRegisters: 2, offline: true },
            professional: { maxProducts: 50000, maxTransactions: 50000, maxRegisters: 5, offline: true },
            premium: { maxProducts: 500000, maxTransactions: 999999, maxRegisters: 999, offline: true },
            enterprise: { maxProducts: 9999999, maxTransactions: 9999999, maxRegisters: 9999, offline: true }
        };
        return tiers[this.currentUser?.tier || 'freemium'];
    }
    
    checkTierLimit(resource, currentCount) {
        const limits = this.getTierLimits();
        const limitMap = {
            products: limits.maxProducts,
            transactions: limits.maxTransactions,
            registers: limits.maxRegisters
        };
        return currentCount < (limitMap[resource] || Infinity);
    }
}

// Initialize RBAC globally
window.rbac = new RBACManager();
