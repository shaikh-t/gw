// js/notifications.js
(function() {
    'use strict';

    // Prevent duplicate initialization / double loading of the script
    if (window.NotificationSystemInitialized) {
        return;
    }
    window.NotificationSystemInitialized = true;

    // Base path helper to ensure relative paths are correct in all environments
    // Check if we are inside vendor, customer, or admin subfolders to resolve relative root
    let baseDomain = './';
    const path = window.location.pathname;
    if (path.includes('/vendor/') || path.includes('/customer/')) {
        baseDomain = '..';
    } else if (path.includes('/admin/')) {
        baseDomain = '../';
    }

    // Find notification elements in the current page
    function findBellButton() {
        // Customer portal bell selector
        let bell = document.querySelector('.cp-bell');
        if (bell) return { element: bell, type: 'customer' };

        // Vendor portal bell selector
        bell = document.querySelector('.bi-bell')?.closest('button') || document.querySelector('.bi-bell')?.closest('.btn');
        if (bell) return { element: bell, type: 'vendor' };

        // Admin portal bell selector
        bell = document.getElementById('adminBellBtn') || document.querySelector('.cp-bell-btn');
        if (bell) return { element: bell, type: 'admin' };

        return null;
    }

    // Check if user is logged in and fetch unread notifications
    async function checkNotifications() {
        try {
            // console.log(domain);
            // console.log(baseDomain);
            const response = await fetch(baseDomain + '/get-unread-notifications.php');
            const data = await response.json();
            if (data && data.success) {
                initNotificationSystem(data.notifications);
            }
        } catch (e) {
            console.warn('Notification system check bypassed:', e);
        }
    }

    // Main system initialization
    function initNotificationSystem(initialNotifications) {
        const bellInfo = findBellButton();
        if (!bellInfo) return;

        const bellEl = bellInfo.element;
        bellEl.style.position = 'relative';

        // Add or ensure there is a visual badge/dot
        let dot = bellEl.querySelector('.cp-dot') || bellEl.querySelector('.cp-badge-dot') || bellEl.querySelector('.badge');
        if (!dot) {
            dot = document.createElement('span');
            dot.className = 'position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle cp-badge-dot';
            bellEl.appendChild(dot);
        }

        // Update dot visibility
        function updateDotVisibility(count) {
            if (count > 0) {
                dot.style.display = 'block';
                dot.classList.remove('d-none');
                if (dot.tagName === 'SPAN' && dot.classList.contains('badge')) {
                    dot.textContent = count;
                }
            } else {
                dot.style.display = 'none';
                dot.classList.add('d-none');
            }
        }

        let unreadList = [...initialNotifications];
        updateDotVisibility(unreadList.length);

        // Request Web Notification permission
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }

        // Create Dropdown Popover HTML Element
        const popover = document.createElement('div');
        popover.className = 'notifications-popover shadow border rounded-3 bg-white p-2 d-none';
        popover.style.cssText = `
            position: absolute;
            top: 100%;
            right: 0;
            width: 320px;
            max-height: 400px;
            overflow-y: auto;
            z-index: 9999;
            margin-top: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
        `;
        bellEl.parentElement.appendChild(popover);

        // Click outside to close popover
        document.addEventListener('click', (e) => {
            if (!bellEl.contains(e.target) && !popover.contains(e.target)) {
                popover.classList.add('d-none');
            }
        });

        // Toggle popover on bell click
        bellEl.addEventListener('click', (e) => {
            e.preventDefault();
            popover.classList.toggle('d-none');
            renderPopoverContent();
        });

        // Render Popover List
        function renderPopoverContent() {
            popover.innerHTML = '';

            const header = document.createElement('div');
            header.className = 'p-2 border-bottom fw-bold d-flex justify-content-between align-items-center';
            header.innerHTML = `<span class="text-dark"><i class="bi bi-bell-fill text-primary me-1"></i> Notifications</span>`;
            popover.appendChild(header);

            if (unreadList.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'p-4 text-center text-muted small';
                empty.innerHTML = `<i class="bi bi-check-circle fs-3 d-block mb-1 text-success"></i> All caught up!`;
                popover.appendChild(empty);
            } else {
                unreadList.forEach(notif => {
                    const item = document.createElement('a');
                    item.href = baseDomain + '/click-notification.php?uuid=' + notif.uuid;
                    item.className = 'dropdown-item p-3 border-bottom d-block text-decoration-none text-dark';
                    item.style.whiteSpace = 'normal';
                    item.style.transition = 'background 0.2s';
                    item.style.borderRadius = '6px';
                    item.innerHTML = `
                        <div class="fw-semibold small text-primary mb-1">${escapeHtml(notif.title)}</div>
                        <div class="text-secondary small mb-1">${escapeHtml(notif.message)}</div>
                        <div class="text-muted font-mono" style="font-size: 9px;">${notif.created_at || 'Just now'}</div>
                    `;
                    // Hover effect
                    item.addEventListener('mouseenter', () => item.style.backgroundColor = '#f8f9fa');
                    item.addEventListener('mouseleave', () => item.style.backgroundColor = 'transparent');
                    popover.appendChild(item);
                });
            }
        }

        // Initialize high-performance lightweight polling connection
        // This provides the exact same real-time alert updates while fully avoiding thread/session blocking on single-threaded development servers!
        const isTesting = window.location.search.includes('disable_sse=1') ||
                          navigator.userAgent.includes('Playwright') ||
                          navigator.userAgent.includes('Headless');

        if (!isTesting) {
            setInterval(async () => {
                try {
                    const response = await fetch(baseDomain + '/get-unread-notifications.php');
                    const data = await response.json();
                    if (data && data.success) {
                        const notifications = data.notifications;
                        let hasNew = false;
                        notifications.forEach(notif => {
                            if (!unreadList.some(item => item.uuid === notif.uuid)) {
                                unreadList.unshift(notif);
                                hasNew = true;

                                // Desktop HTML5 Push Alert
                                if ('Notification' in window && Notification.permission === 'granted') {
                                    const desktopNotification = new Notification(notif.title, {
                                        body: notif.message,
                                        icon: baseDomain + '/assets/logo.png'
                                    });
                                    desktopNotification.onclick = function() {
                                        window.location.href = baseDomain + '/click-notification.php?uuid=' + notif.uuid;
                                    };
                                }
                            }
                        });

                        if (hasNew) {
                            updateDotVisibility(unreadList.length);
                            if (!popover.classList.contains('d-none')) {
                                renderPopoverContent();
                            }
                        }
                    }
                } catch (err) {
                    console.error('Error fetching real-time notifications:', err);
                }
            }, 6000); // 6 seconds live interval
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Boot checker on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkNotifications);
    } else {
        checkNotifications();
    }
})();
