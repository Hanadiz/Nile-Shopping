/**
 * Nile POS - Role-Based Access Control (RBAC) System
 * Manages user roles, permissions, and menu visibility
 */

class RBACManager {
    constructor() {
        this.currentUser = null;
        this.permissions = [];
        
        // Role hierarchy (higher number = more permissions)
        this.roleHierarchy = {
            'admin': 100,
            'manager': 80,
            'supervisor': 60,
            'cashier': 40,
            'stock_clerk': 30,
            'viewer': 10
        };
        
        // Tier levels
        this.tierLevels = {
            'freemium': 0,
            'basic': 1,
            'professional': 2,
            'premium': 3,
            'enterprise': 4
        };
        
        // Complete permission matrix
        this.permissionMatrix = {
            // Dashboard
            'dashboard.view': { roles: ['admin', 'manager', 'supervisor', 'cashier', 'stock_clerk', 'viewer'], tier: 'freemium' },
            'dashboard.real_time': { roles: ['admin', 'manager', 'supervisor'], tier: 'professional' },
            
            // POS / Transactions
            'pos.access': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'transaction.create': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'transaction.view_own': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'transaction.view_all': { roles: ['admin', 'manager', 'supervisor'], tier: 'professional' },
            'transaction.void': { roles: ['admin', 'manager', 'supervisor'], tier: 'basic' },
            'transaction.refund': { roles: ['admin', 'manager', 'supervisor'], tier: 'basic' },
            'transaction.refund_max_500': { roles: ['admin', 'manager', 'supervisor'], tier: 'professional' },
            'transaction.refund_unlimited': { roles: ['admin'], tier: 'premium' },
            
            // Products
            'product.view': { roles: ['admin', 'manager', 'supervisor', 'cashier', 'stock_clerk', 'viewer'], tier: 'freemium' },
            'product.create': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'basic' },
            'product.update': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'basic' },
            'product.delete': { roles: ['admin', 'manager'], tier: 'professional' },
            'product.import': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'professional' },
            'product.export': { roles: ['admin', 'manager'], tier: 'professional' },
            
            // Inventory
            'inventory.view': { roles: ['admin', 'manager', 'supervisor', 'stock_clerk'], tier: 'basic' },
            'inventory.count': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'basic' },
            'inventory.adjust': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'professional' },
            'inventory.transfer': { roles: ['admin', 'manager'], tier: 'professional' },
            
            // Customers
            'customer.view': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'customer.create': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'customer.update': { roles: ['admin', 'manager', 'supervisor', 'cashier'], tier: 'freemium' },
            'customer.delete': { roles: ['admin', 'manager'], tier: 'professional' },
            'customer.loyalty': { roles: ['admin', 'manager'], tier: 'professional' },
            
            // Reports
            'report.sales': { roles: ['admin', 'manager', 'supervisor'], tier: 'basic' },
            'report.inventory': { roles: ['admin', 'manager', 'stock_clerk'], tier: 'professional' },
            'report.employee': { roles: ['admin', 'manager'], tier: 'professional' },
            'report.advanced': { roles: ['admin', 'manager'], tier: 'premium' },
            'report.export': { roles: ['admin', 'manager'], tier: 'professional' },
            
            // User Management
            'user.view': { roles: ['admin', 'manager'], tier: 'professional' },
            'user.create': { roles: ['admin'], tier: 'premium' },
            'user.update': { roles: ['admin', 'manager'], tier: 'professional' },
            'user.delete': { roles: ['admin'], tier: 'premium' },
            'user.role_assign': { roles: ['admin'], tier: 'premium' },
            
            // Settings
            'settings.view': { roles: ['admin', 'manager'], tier: 'basic' },
            'settings.update': { roles: ['admin'], tier: 'professional' },
            'settings.system': { roles: ['admin'], tier: 'enterprise' },
            
            // Store Management
            'store.view': { roles: ['admin', 'manager'], tier: 'professional' },
            'store.create': { roles: ['admin'], tier: 'enterprise' },
            'store.update': { roles: ['admin'], tier: 'enterprise' },
            
            // Security
            'security.mfa': { roles: ['admin', 'manager'], tier: 'premium' },
            'security.biometric': { roles: ['admin', 'manager', 'cashier'], tier: 'professional' },
            'security.audit': { roles: ['admin'], tier: 'professional' },
            
            // API
            'api.access': { roles: ['admin', 'manager'], tier: 'professional' },
            'webhook.manage': { roles: ['admin'], tier: 'premium' },
            
            // System
            'system.backup': { roles: ['admin'], tier: 'premium' },
            'system.restore': { roles: ['admin'], tier: 'enterprise' },
            'system.tenant': { roles: ['admin'], tier: 'enterprise' }
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
    
    getTierLevel() {
        return this.tierLevels[this.currentUser?.tier] || 0;
    }
    
    hasTierAccess(requiredTier) {
        return this.getTierLevel() >= (this.tierLevels[requiredTier] || 0);
    }
    
    getCurrentUser() {
        return this.currentUser;
    }
    
    getUserRole() {
        return this.currentUser?.role || 'guest';
    }
    
    getUserTier() {
        return this.currentUser?.tier || 'freemium';
    }
    
    isAuthenticated() {
        return !!this.currentUser && !!localStorage.getItem('pos_jwt');
    }
    
    logout() {
        localStorage.removeItem('pos_jwt');
        localStorage.removeItem('pos_refresh_token');
        localStorage.removeItem('pos_user');
        localStorage.removeItem('pos_permissions');
        localStorage.removeItem('pos_session_expiry');
        localStorage.removeItem('pos_tier');
        window.location.href = '/login.html';
    }
    
    updateUIByPermissions() {
        // Hide elements based on permission
        document.querySelectorAll('[data-permission]').forEach(el => {
            const perm = el.dataset.permission;
            if (!this.hasPermission(perm)) {
                el.style.display = 'none';
            }
        });
        
        // Hide elements based on role
        document.querySelectorAll('[data-role]').forEach(el => {
            const role = el.dataset.role;
            if (!this.isAtLeast(role)) {
                el.style.display = 'none';
            }
        });
        
        // Hide elements based on tier
        document.querySelectorAll('[data-tier]').forEach(el => {
            const tier = el.dataset.tier;
            if (!this.hasTierAccess(tier)) {
                el.style.display = 'none';
            }
        });
    }
    
    canAccessRoute(route) {
        const routePermissions = this.getRoutePermissions(route);
        if (!routePermissions || routePermissions.length === 0) return true;
        return this.hasAnyPermission(routePermissions);
    }
    
    getRoutePermissions(route) {
        const routeMap = {
            '/dashboard.html': ['dashboard.view'],
            '/index.html': ['pos.access'],
            '/products.html': ['product.view'],
            '/customers.html': ['customer.view'],
            '/inventory.html': ['inventory.view'],
            '/reports.html': ['report.sales'],
            '/settings.html': ['settings.view'],
            '/users.html': ['user.view'],
            '/api-debug.html': ['api.access']
        };
        return routeMap[route] || [];
    }
}

// Initialize RBAC globally
window.rbac = new RBACManager();
