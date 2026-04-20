// JavaScript do layout (menu mobile, sidebar BEM, etc.)
let _layoutInitialized = false;
let _mobileMenuHandler = null;
let _dropdownClickHandler = null;
let _dropdownHoverHandlers = [];
let _sidebarOpenHandler = null;
let _sidebarCloseHandler = null;
let _sidebarOverlayHandler = null;
let _sidebarLinkClickHandler = null;
let _sidebarGroupHandlers = [];
let _sidebarUserHandler = null;
let _mobileMenuLinkHandler = null;
const _dropdownOpenTimers = new WeakMap();
const _dropdownCloseTimers = new WeakMap();
const DROPDOWN_DELAY_MS = 100;

function initLayout() {
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuBtn && mobileMenu) {
        if (_mobileMenuHandler) {
            mobileMenuBtn.removeEventListener('click', _mobileMenuHandler);
            _mobileMenuHandler = null;
        }

        _mobileMenuHandler = function() {
            mobileMenu.classList.toggle('hidden');
            if (!mobileMenu.classList.contains('hidden')) {
                mobileMenu.classList.add('flex');
            } else {
                mobileMenu.classList.remove('flex');
            }
        };

        mobileMenuBtn.addEventListener('click', _mobileMenuHandler);
    }

    // Close mobile-menu on link click (landing page)
    if (mobileMenu) {
        if (_mobileMenuLinkHandler) {
            mobileMenu.removeEventListener('click', _mobileMenuLinkHandler);
            _mobileMenuLinkHandler = null;
        }
        _mobileMenuLinkHandler = function(e) {
            const link = e.target.closest('[data-link]');
            if (link) {
                mobileMenu.classList.add('hidden');
                mobileMenu.classList.remove('flex');
            }
        };
        mobileMenu.addEventListener('click', _mobileMenuLinkHandler);
    }

    // Sidebar (authenticated area) - drawer on mobile + collapse/expand on desktop
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const sidebarOpenBtn = document.getElementById('sidebar-open-btn');
    const sidebarCloseBtn = document.getElementById('sidebar-close-btn');

    const isDesktop = () => window.matchMedia('(min-width: 768px)').matches;

    const openSidebarDrawer = () => {
        if (!sidebar || !sidebarOverlay) return;
        sidebar.classList.add('sidebar--open');
        sidebarOverlay.classList.add('sidebar__overlay--visible');
        sidebarOverlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeSidebarDrawer = () => {
        if (!sidebar || !sidebarOverlay) return;
        sidebar.classList.remove('sidebar--open');
        sidebarOverlay.classList.remove('sidebar__overlay--visible');
        sidebarOverlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    // Expose globally for SPA navigation
    window.closeSidebarDrawer = closeSidebarDrawer;

    // Open (mobile)
    if (sidebarOpenBtn && sidebar) {
        if (_sidebarOpenHandler) {
            sidebarOpenBtn.removeEventListener('click', _sidebarOpenHandler);
            _sidebarOpenHandler = null;
        }
        _sidebarOpenHandler = function () {
            openSidebarDrawer();
        };
        sidebarOpenBtn.addEventListener('click', _sidebarOpenHandler);
    }

    // Close (mobile)
    if (sidebarCloseBtn && sidebar) {
        if (_sidebarCloseHandler) {
            sidebarCloseBtn.removeEventListener('click', _sidebarCloseHandler);
            _sidebarCloseHandler = null;
        }
        _sidebarCloseHandler = function () {
            closeSidebarDrawer();
        };
        sidebarCloseBtn.addEventListener('click', _sidebarCloseHandler);
    }

    // Overlay click (mobile)
    if (sidebarOverlay) {
        if (_sidebarOverlayHandler) {
            sidebarOverlay.removeEventListener('click', _sidebarOverlayHandler);
            _sidebarOverlayHandler = null;
        }
        _sidebarOverlayHandler = function () {
            closeSidebarDrawer();
        };
        sidebarOverlay.addEventListener('click', _sidebarOverlayHandler);
    }

    // Close drawer on link click (mobile)
    if (sidebar) {
        if (_sidebarLinkClickHandler) {
            sidebar.removeEventListener('click', _sidebarLinkClickHandler);
            _sidebarLinkClickHandler = null;
        }
        _sidebarLinkClickHandler = function (e) {
            const link = e.target && e.target.closest ? e.target.closest('[data-link]') : null;
            if (!link) return;
            if (isDesktop()) return;
            closeSidebarDrawer();
        };
        sidebar.addEventListener('click', _sidebarLinkClickHandler);
    }

    // Dropdown menu - close on outside click
    if (_dropdownClickHandler) {
        document.removeEventListener('click', _dropdownClickHandler);
        _dropdownClickHandler = null;
    }

    _dropdownClickHandler = function(e) {
        const dropdownGroups = document.querySelectorAll('.relative.group');
        dropdownGroups.forEach(group => {
            const dropdownMenu = group.querySelector('.dropdown-menu');
            if (dropdownMenu && !group.contains(e.target)) {
                if (!dropdownMenu.classList.contains('opacity-0')) {
                    dropdownMenu.classList.add('opacity-0', 'invisible');
                    dropdownMenu.classList.remove('opacity-100', 'visible');
                }
            }
        });
    };

    document.addEventListener('click', _dropdownClickHandler);

    // Dropdown with hover delay (Solucoes menu)
    if (_dropdownHoverHandlers.length) {
        _dropdownHoverHandlers.forEach(({ element, enterHandler, leaveHandler }) => {
            element.removeEventListener('mouseenter', enterHandler);
            element.removeEventListener('mouseleave', leaveHandler);
        });
        _dropdownHoverHandlers = [];
    }

    const dropdownGroups = document.querySelectorAll('.nav-dropdown-buffer');

    const showPanel = (panel) => {
        panel.classList.remove('opacity-0', 'invisible', 'pointer-events-none', 'translate-y-2');
        panel.classList.add('opacity-100', 'visible', 'pointer-events-auto', 'translate-y-0');
    };

    const hidePanel = (panel) => {
        panel.classList.add('opacity-0', 'invisible', 'pointer-events-none', 'translate-y-2');
        panel.classList.remove('opacity-100', 'visible', 'pointer-events-auto', 'translate-y-0');
    };

    dropdownGroups.forEach(group => {
        const panel = group.querySelector('.nav-dropdown-panel');
        if (!panel) return;

        panel.classList.remove('group-hover:translate-y-0', 'group-hover:visible', 'group-hover:opacity-100', 'group-hover:pointer-events-auto');

        const enterHandler = () => {
            const closeTimer = _dropdownCloseTimers.get(group);
            if (closeTimer) {
                clearTimeout(closeTimer);
                _dropdownCloseTimers.delete(group);
            }
            const openTimer = setTimeout(() => {
                showPanel(panel);
            }, DROPDOWN_DELAY_MS);
            _dropdownOpenTimers.set(group, openTimer);
        };

        const leaveHandler = () => {
            const openTimer = _dropdownOpenTimers.get(group);
            if (openTimer) {
                clearTimeout(openTimer);
                _dropdownOpenTimers.delete(group);
            }
            const closeTimer = setTimeout(() => {
                hidePanel(panel);
            }, DROPDOWN_DELAY_MS);
            _dropdownCloseTimers.set(group, closeTimer);
        };

        hidePanel(panel);

        group.addEventListener('mouseenter', enterHandler);
        group.addEventListener('mouseleave', leaveHandler);

        _dropdownHoverHandlers.push({ element: group, panel, enterHandler, leaveHandler });
    });

    // Update year
    const currentYearElement = document.getElementById('current-year');
    if (currentYearElement) {
        currentYearElement.textContent = new Date().getFullYear();
    }

    // Active link
    updateActiveLink();

    // Native HTML5 <details> handles sidebar groups and user menu interactively.

    _layoutInitialized = true;
}

function resetLayout() {
    // Ensure no scroll lock from drawer/overlays after navigation
    document.body.classList.remove('overflow-hidden');

    // Close mobile-menu (landing page safety net)
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileMenu) {
        mobileMenu.classList.add('hidden');
        mobileMenu.classList.remove('flex');
    }

    // Close sidebar drawer on mobile (safety net)
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    if (sidebar) {
        sidebar.classList.remove('sidebar--open');
    }
    if (sidebarOverlay) {
        sidebarOverlay.classList.remove('sidebar__overlay--visible');
        sidebarOverlay.classList.add('hidden');
    }

    if (_mobileMenuHandler) {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        if (mobileMenuBtn) {
            mobileMenuBtn.removeEventListener('click', _mobileMenuHandler);
        }
        _mobileMenuHandler = null;
    }
    if (_mobileMenuLinkHandler) {
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenu) {
            mobileMenu.removeEventListener('click', _mobileMenuLinkHandler);
        }
        _mobileMenuLinkHandler = null;
    }
    if (_sidebarOpenHandler) {
        const sidebarOpenBtn = document.getElementById('sidebar-open-btn');
        if (sidebarOpenBtn) {
            sidebarOpenBtn.removeEventListener('click', _sidebarOpenHandler);
        }
        _sidebarOpenHandler = null;
    }
    if (_sidebarCloseHandler) {
        const sidebarCloseBtn = document.getElementById('sidebar-close-btn');
        if (sidebarCloseBtn) {
            sidebarCloseBtn.removeEventListener('click', _sidebarCloseHandler);
        }
        _sidebarCloseHandler = null;
    }
    if (_sidebarOverlayHandler) {
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        if (sidebarOverlay) {
            sidebarOverlay.removeEventListener('click', _sidebarOverlayHandler);
        }
        _sidebarOverlayHandler = null;
    }
    if (_sidebarLinkClickHandler) {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.removeEventListener('click', _sidebarLinkClickHandler);
        }
        _sidebarLinkClickHandler = null;
    }
    if (_dropdownClickHandler) {
        document.removeEventListener('click', _dropdownClickHandler);
        _dropdownClickHandler = null;
    }

    if (_dropdownHoverHandlers.length) {
        _dropdownHoverHandlers.forEach(({ element, panel, enterHandler, leaveHandler }) => {
            try {
                element.removeEventListener('mouseenter', enterHandler);
                element.removeEventListener('mouseleave', leaveHandler);
                if (panel) {
                    panel.classList.add('opacity-0', 'invisible', 'pointer-events-none', 'translate-y-2');
                    panel.classList.remove('opacity-100', 'visible', 'pointer-events-auto', 'translate-y-0');
                }
                const openTimer = _dropdownOpenTimers.get(element);
                if (openTimer) {
                    clearTimeout(openTimer);
                    _dropdownOpenTimers.delete(element);
                }
                const closeTimer = _dropdownCloseTimers.get(element);
                if (closeTimer) {
                    clearTimeout(closeTimer);
                    _dropdownCloseTimers.delete(element);
                }
            } catch (e) {
                // ignore
            }
        });
        _dropdownHoverHandlers = [];
    }
    if (_sidebarGroupHandlers.length) {
        _sidebarGroupHandlers.forEach(({ element, handler }) => {
            if (element && handler) {
                element.removeEventListener('click', handler);
            }
        });
        _sidebarGroupHandlers = [];
    }
    if (_sidebarUserHandler) {
        const userTrigger = document.querySelector('.sidebar__user-trigger');
        if (userTrigger) {
            userTrigger.removeEventListener('click', _sidebarUserHandler);
        }
        _sidebarUserHandler = null;
    }
    _layoutInitialized = false;
}

// Expose globally
window.resetLayout = resetLayout;
window.initLayout = initLayout;

// Update active link
function updateActiveLink() {
    const currentPath = window.location.pathname;
    const allLinks = document.querySelectorAll('[data-link]');
    const sidebarLinks = document.querySelectorAll('[data-sidebar-link]');
    const sidebarGroupItems = document.querySelectorAll('[data-sidebar-group-item]');
    const sidebarUserLinks = document.querySelectorAll('[data-sidebar-user-link]');
    const sidebarGroupTriggers = document.querySelectorAll('[data-sidebar-group-trigger]');
    const userTriggers = document.querySelectorAll('.sidebar__user-trigger');
    const isSidebarLink = (link) => link.matches('[data-sidebar-link], [data-sidebar-group-item], [data-sidebar-user-link]');
    const normalizePath = (value) => {
        if (!value) {
            return '';
        }

        try {
            const url = new URL(value, window.location.origin);
            return url.pathname.replace(/\/+$/, '') || '/';
        } catch (error) {
            return String(value).replace(/[?#].*$/, '').replace(/\/+$/, '') || '/';
        }
    };
    const matchesSidebarPath = (href, path) => {
        const normalizedHref = normalizePath(href);
        const normalizedPath = normalizePath(path);

        if (!normalizedHref || normalizedHref === '#') {
            return false;
        }

        return normalizedPath === normalizedHref || normalizedPath.startsWith(normalizedHref + '/');
    };

    // Remove active classes from all links
    allLinks.forEach(link => {
        if (link.hasAttribute('data-no-active')) {
            return;
        }
        if (isSidebarLink(link)) {
            return;
        }
        const isButton = link.classList.contains('btn-accent') || link.classList.contains('btn-primary') || link.classList.contains('btn-secondary');

        if (isButton) {
            link.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2');
        } else {
            link.classList.remove('text-blue-500', 'font-semibold');
            link.classList.add('text-gray-600');
        }
    });

    sidebarLinks.forEach(link => {
        link.classList.remove('sidebar__item--active');
    });
    sidebarGroupItems.forEach(link => {
        link.classList.remove('sidebar__group-menu-item--active');
    });
    sidebarUserLinks.forEach(link => {
        link.classList.remove('sidebar__user-menu-item--active');
    });
    sidebarGroupTriggers.forEach(trigger => {
        trigger.classList.remove('sidebar__group-trigger--active');
    });
    userTriggers.forEach(trigger => {
        trigger.classList.remove('sidebar__user-trigger--active');
    });

    // Add active classes to current link
    allLinks.forEach(link => {
        if (link.hasAttribute('data-no-active')) {
            return;
        }
        if (isSidebarLink(link)) {
            return;
        }
        if (link.getAttribute('href') === currentPath) {
            const isButton = link.classList.contains('btn-accent') || link.classList.contains('btn-primary') || link.classList.contains('btn-secondary');

            if (isButton) {
                link.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2');
            } else {
                link.classList.remove('text-gray-600');
                link.classList.add('text-blue-500');
            }
        }
    });

    let bestSidebarLink = null;
    let bestSidebarLinkLen = -1;
    sidebarLinks.forEach(link => {
        if (matchesSidebarPath(link.getAttribute('href'), currentPath)) {
            const len = normalizePath(link.getAttribute('href')).length;
            if (len > bestSidebarLinkLen) {
                bestSidebarLinkLen = len;
                bestSidebarLink = link;
            }
        }
    });
    if (bestSidebarLink) {
        bestSidebarLink.classList.add('sidebar__item--active');
    }

    let bestGroupItem = null;
    let bestGroupItemLen = -1;
    sidebarGroupItems.forEach(link => {
        if (matchesSidebarPath(link.getAttribute('href'), currentPath)) {
            const len = normalizePath(link.getAttribute('href')).length;
            if (len > bestGroupItemLen) {
                bestGroupItemLen = len;
                bestGroupItem = link;
            }
        }
    });
    if (bestGroupItem) {
        bestGroupItem.classList.add('sidebar__group-menu-item--active');

        const group = bestGroupItem.closest('[data-sidebar-group]');
        const trigger = group ? group.querySelector('[data-sidebar-group-trigger]') : null;
        if (group) {
            group.setAttribute('open', 'open');
        }
        if (trigger) {
            trigger.classList.add('sidebar__group-trigger--active');
        }
    }

    sidebarUserLinks.forEach(link => {
        if (matchesSidebarPath(link.getAttribute('href'), currentPath)) {
            link.classList.add('sidebar__user-menu-item--active');

            const userDetails = link.closest('details');
            const userTrigger = userDetails ? userDetails.querySelector('.sidebar__user-trigger') : null;
            if (userDetails) {
                userDetails.setAttribute('open', 'open');
            }
            if (userTrigger) {
                userTrigger.classList.add('sidebar__user-trigger--active');
            }
        }
    });
}

// Called by SPA when page changes
function setActiveLink(path) {
    const allLinks = document.querySelectorAll('[data-link]');
    const sidebarLinks = document.querySelectorAll('[data-sidebar-link]');
    const sidebarGroupItems = document.querySelectorAll('[data-sidebar-group-item]');
    const sidebarUserLinks = document.querySelectorAll('[data-sidebar-user-link]');
    const sidebarGroupTriggers = document.querySelectorAll('[data-sidebar-group-trigger]');
    const userTriggers = document.querySelectorAll('.sidebar__user-trigger');
    const isSidebarLink = (link) => link.matches('[data-sidebar-link], [data-sidebar-group-item], [data-sidebar-user-link]');
    const normalizePath = (value) => {
        if (!value) {
            return '';
        }

        try {
            const url = new URL(value, window.location.origin);
            return url.pathname.replace(/\/+$/, '') || '/';
        } catch (error) {
            return String(value).replace(/[?#].*$/, '').replace(/\/+$/, '') || '/';
        }
    };
    const matchesSidebarPath = (href, currentPath) => {
        const normalizedHref = normalizePath(href);
        const normalizedPath = normalizePath(currentPath);

        if (!normalizedHref || normalizedHref === '#') {
            return false;
        }

        return normalizedPath === normalizedHref || normalizedPath.startsWith(normalizedHref + '/');
    };

    allLinks.forEach(link => {
        if (link.hasAttribute('data-no-active')) {
            return;
        }
        if (isSidebarLink(link)) {
            return;
        }
        const isButton = link.classList.contains('btn-accent') || link.classList.contains('btn-primary') || link.classList.contains('btn-secondary');

        if (isButton) {
            link.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2');
        } else {
            link.classList.remove('text-blue-500', 'font-semibold');
            link.classList.add('text-gray-600');
        }
    });

    allLinks.forEach(link => {
        if (link.hasAttribute('data-no-active')) {
            return;
        }
        if (isSidebarLink(link)) {
            return;
        }
        const linkHref = link.getAttribute('href');
        if (linkHref && path.startsWith(linkHref)) {
            const isButton = link.classList.contains('btn-accent') || link.classList.contains('btn-primary') || link.classList.contains('btn-secondary');

            if (isButton) {
                link.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2');
            } else {
                link.classList.remove('text-gray-600');
                link.classList.add('text-blue-500');
            }

            // If link is inside a submenu, expand the parent group
            const groupMenu = link.closest('.sidebar__group-menu');
            if (groupMenu) {
                const group = groupMenu.closest('.sidebar__group');
                if (group && group.tagName === 'DETAILS' && !group.open) {
                    group.open = true;
                }
            }

            // If link is inside user menu, expand it
            const userMenu = link.closest('.sidebar__user-menu');
            if (userMenu) {
                const userDetails = userMenu.closest('details');
                if (userDetails && !userDetails.open) {
                    userDetails.open = true;
                }
            }
        }
    });

    sidebarLinks.forEach(link => {
        link.classList.remove('sidebar__item--active');
    });
    let bestSpaLink = null;
    let bestSpaLinkLen = -1;
    sidebarLinks.forEach(link => {
        if (matchesSidebarPath(link.getAttribute('href'), path)) {
            const len = normalizePath(link.getAttribute('href')).length;
            if (len > bestSpaLinkLen) {
                bestSpaLinkLen = len;
                bestSpaLink = link;
            }
        }
    });
    if (bestSpaLink) {
        bestSpaLink.classList.add('sidebar__item--active');
    }

    sidebarGroupItems.forEach(link => {
        link.classList.remove('sidebar__group-menu-item--active');
    });
    sidebarGroupTriggers.forEach(trigger => {
        trigger.classList.remove('sidebar__group-trigger--active');
    });
    let bestSpaGroupItem = null;
    let bestSpaGroupItemLen = -1;
    sidebarGroupItems.forEach(link => {
        if (matchesSidebarPath(link.getAttribute('href'), path)) {
            const len = normalizePath(link.getAttribute('href')).length;
            if (len > bestSpaGroupItemLen) {
                bestSpaGroupItemLen = len;
                bestSpaGroupItem = link;
            }
        }
    });
    if (bestSpaGroupItem) {
        bestSpaGroupItem.classList.add('sidebar__group-menu-item--active');

        const group = bestSpaGroupItem.closest('[data-sidebar-group]');
        const trigger = group ? group.querySelector('[data-sidebar-group-trigger]') : null;
        if (group) {
            group.setAttribute('open', 'open');
        }
        if (trigger) {
            trigger.classList.add('sidebar__group-trigger--active');
        }
    }

    sidebarUserLinks.forEach(link => {
        link.classList.remove('sidebar__user-menu-item--active');
    });
    userTriggers.forEach(trigger => {
        trigger.classList.remove('sidebar__user-trigger--active');
    });
    sidebarUserLinks.forEach(link => {
        if (matchesSidebarPath(link.getAttribute('href'), path)) {
            link.classList.add('sidebar__user-menu-item--active');

            const userDetails = link.closest('details');
            const userTrigger = userDetails ? userDetails.querySelector('.sidebar__user-trigger') : null;
            if (userDetails) {
                userDetails.setAttribute('open', 'open');
            }
            if (userTrigger) {
                userTrigger.classList.add('sidebar__user-trigger--active');
            }
        }
    });
}

// Initialize on first load
document.addEventListener('DOMContentLoaded', () => {
    try {
        initLayout();
    } catch (e) {
        // ignore
    }
});
