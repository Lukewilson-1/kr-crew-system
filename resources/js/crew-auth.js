function attachCrewAuthHandlers() {
    const loginButton = document.querySelector('[data-crew-login]');
    const logoutButton = document.querySelector('[data-crew-logout]');
    const loginInput = document.getElementById('lUser');
    const passwordInput = document.getElementById('lPass');

    loginButton?.addEventListener('click', (event) => {
        event.preventDefault();
        window.doLogin?.();
    });

    logoutButton?.addEventListener('click', (event) => {
        event.preventDefault();
        window.doLogout?.();
    });

    [loginInput, passwordInput].forEach((field) => {
        field?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                window.doLogin?.();
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachCrewAuthHandlers);
} else {
    attachCrewAuthHandlers();
}
