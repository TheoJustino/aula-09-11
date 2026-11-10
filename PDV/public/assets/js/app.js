document.addEventListener('DOMContentLoaded', () => {
    const shell = document.querySelector('[data-app-shell]');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const closeButton = document.querySelector('[data-sidebar-close]');

    if (!shell || !toggle) return;

    const mobile = window.matchMedia('(max-width: 900px)');
    toggle.setAttribute('aria-expanded', String(!mobile.matches));

    const closeMobileMenu = () => {
        shell.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', () => {
        if (mobile.matches) {
            const isOpen = shell.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(isOpen));
            return;
        }

        const isCollapsed = shell.classList.toggle('is-collapsed');
        toggle.setAttribute('aria-expanded', String(!isCollapsed));
    });

    closeButton?.addEventListener('click', closeMobileMenu);

    shell.querySelectorAll('.sidebar-nav a').forEach((link) => {
        link.addEventListener('click', () => {
            if (mobile.matches) closeMobileMenu();
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobile.matches) closeMobileMenu();
    });

    mobile.addEventListener('change', () => {
        closeMobileMenu();
        if (!mobile.matches) {
            shell.classList.remove('is-collapsed');
            toggle.setAttribute('aria-expanded', 'true');
        }
    });
});