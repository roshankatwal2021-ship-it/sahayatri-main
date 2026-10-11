// system/assets/js/app.js
// SAHAYATRI Main Frontend Engine: Persistent Tabs (URL Hash + LocalStorage), Modals & Toast Alerts

(function() {
    function getTargetTabId() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab && document.getElementById(tab)) {
            return tab;
        }
        return null;
    }

    function activateTab(targetId, updateHistory = true) {
        if (!targetId) return;

        const targetContent = document.getElementById(targetId);
        const targetBtns = document.querySelectorAll(`[data-tab-target="${targetId}"]`);

        if (!targetContent) return;

        // Deactivate all tabs & links
        document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
        document.querySelectorAll('.nav-link').forEach(nl => nl.classList.remove('active'));

        // Activate target tab & matching nav-links
        targetContent.classList.add('active');
        targetBtns.forEach(btn => btn.classList.add('active'));

        // Proper URL-based routing (pushState)
        if (updateHistory) {
            try {
                const url = new URL(window.location.href);
                if (url.searchParams.get('tab') !== targetId) {
                    url.searchParams.set('tab', targetId);
                    window.history.pushState({ tab: targetId }, '', url);
                }
            } catch(e) {}
        }

        // Trigger specific tab loaders
        if (targetId === 'tab-map') {
            if (window.sahayatriMap) {
                setTimeout(() => window.sahayatriMap.invalidateSize(), 150);
                setTimeout(() => window.sahayatriMap.invalidateSize(), 400);
            } else if (typeof window.initSahayatriMap === 'function') {
                window.initSahayatriMap();
            }
        }
        if (targetId === 'tab-team' && typeof window.loadTravelerTeams === 'function') {
            window.loadTravelerTeams();
        }
        if (targetId === 'tab-agency-chat' && typeof window.loadTravelerChat === 'function') {
            window.loadTravelerChat();
        }
        if (targetId === 'tab-admin-teams' && typeof window.loadAdminTeams === 'function') {
            window.loadAdminTeams();
        }
        if (targetId === 'tab-admin-hotels' && typeof window.loadAdminHotels === 'function') {
            window.loadAdminHotels();
        }
        if (targetId === 'tab-admin-packages' && typeof window.loadAdminPackages === 'function') {
            window.loadAdminPackages();
        }
        if (targetId === 'tab-admin-broadcast' && typeof window.loadAdminBroadcasts === 'function') {
            window.loadAdminBroadcasts();
        }
        if (targetId === 'tab-admin-zones' && typeof window.loadAdminZones === 'function') {
            window.loadAdminZones();
        }
        if (targetId === 'tab-admin-sos' && typeof window.loadAdminSOS === 'function') {
            window.loadAdminSOS();
        }
    }

    // Expose activateTab globally
    window.activateTab = activateTab;

    // Delegated click event listener for instant responsiveness
    document.addEventListener('click', (e) => {
        // Handle Sidebar Tab Target Click
        const tabBtn = e.target.closest('[data-tab-target]');
        if (tabBtn) {
            e.preventDefault();
            const targetId = tabBtn.getAttribute('data-tab-target');
            if (targetId) {
                activateTab(targetId);
            }
            return;
        }

        // Handle Sidebar Slide/Collapse Toggle Button
        const slideBtn = e.target.closest('#sidebar-slide-btn');
        if (slideBtn) {
            e.preventDefault();
            const appContainer = document.querySelector('.app-container');
            if (appContainer) {
                appContainer.classList.toggle('sidebar-collapsed');
            }
            const sidebar = document.getElementById('dashboard-sidebar');
            if (sidebar) {
                sidebar.classList.toggle('collapsed');
            }
            return;
        }
    });

    function initTabs() {
        // Restore active tab on load/refresh
        const activeId = getTargetTabId();
        if (activeId) {
            activateTab(activeId, false);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTabs);
    } else {
        initTabs();
    }

    window.addEventListener('load', () => {
        const activeId = getTargetTabId();
        if (activeId) activateTab(activeId, false);
    });

    window.addEventListener('popstate', (e) => {
        const activeId = getTargetTabId();
        if (activeId) {
            activateTab(activeId, false);
        } else {
            // Revert to default tab if no tab in URL
            const defaultTab = document.querySelector('.tab-content:first-of-type');
            if (defaultTab) activateTab(defaultTab.id, false);
        }
    });

    // Modal controls
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'flex';
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'none';
    };

    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.style.display = 'none';
            }
        });
    });

    // Toast controls
    window.showToast = function(message, type = 'info') {
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        let bg = '#0284C7';
        if (type === 'success') bg = '#10B981';
        if (type === 'danger' || type === 'error') bg = '#EF4444';
        if (type === 'warning') bg = '#F59E0B';

        toast.style.cssText = `background:${bg};color:#fff;padding:14px 20px;border-radius:12px;box-shadow:0 4px 14px rgba(0,0,0,0.15);font-size:14px;font-weight:600;min-width:280px;animation:slideIn 0.3s ease;`;
        toast.innerHTML = message;

        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    };
})();
