/**
 * Nile POS - Authentication Guard
 * Protects routes from unauthenticated access
 * Include this script at the beginning of every protected page
 */

(function() {
    // Check if user is authenticated
    const token = localStorage.getItem('pos_jwt');
    const sessionExpiry = localStorage.getItem('pos_session_expiry');
    const currentPath = window.location.pathname;
    
    // List of public pages (no authentication required)
    const publicPages = [
        '/login.html',
        '/register.html', 
        '/forgot-password.html',
        '/reset-password.html',
        '/logout.html',
        '/index.html'
    ];
    
    // Check if current page is public
    const isPublicPage = publicPages.some(page => currentPath === page || currentPath.endsWith(page));
    
    if (isPublicPage) {
        return; // Allow access to public pages
    }
    
    // Validate session
    const isValidSession = token && sessionExpiry && new Date(sessionExpiry) > new Date();
    
    if (!isValidSession) {
        // Clear invalid data
        localStorage.removeItem('pos_jwt');
        localStorage.removeItem('pos_refresh_token');
        localStorage.removeItem('pos_user');
        localStorage.removeItem('pos_permissions');
        localStorage.removeItem('pos_session_expiry');
        localStorage.removeItem('pos_tier');
        
        // Redirect to login
        console.warn('Authentication required. Redirecting to login...');
        window.location.href = '/login.html';
        return;
    }
    
    // Extend session (8 hours from now)
    const expires = new Date();
    expires.setHours(expires.getHours() + 8);
    localStorage.setItem('pos_session_expiry', expires.toISOString());
    
    // Log successful authentication
    const user = JSON.parse(localStorage.getItem('pos_user') || '{}');
    console.log(`Authenticated: ${user.email || 'Unknown'} (${user.role || 'guest'})`);
})();
