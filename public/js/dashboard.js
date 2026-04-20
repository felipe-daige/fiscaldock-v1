// Inicialização básica do dashboard
// Evita warnings de função ausente no SPA ao navegar para /dashboard

if (!window.initDashboard) {
    window._dashboardInitialized = false;

    window.initDashboard = function initDashboard() {
        if (window._dashboardInitialized) return;
        window._dashboardInitialized = true;

        // Coloque aqui inicializações reais do dashboard quando existirem
        console.info('[Dashboard] initDashboard executado');
    };
}

// Opcionalmente, exponha cleanup para futuras limpezas (no-op por enquanto)
if (!window._cleanupFunctions) {
    window._cleanupFunctions = {};
}
if (!window._cleanupFunctions.initDashboard) {
    window._cleanupFunctions.initDashboard = function cleanupDashboard() {
        window._dashboardInitialized = false;
    };
}
