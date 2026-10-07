const calendar = document.querySelector('#mobile-calendar');

document.querySelector('[data-calendar-open]')?.addEventListener('click', () => {
    calendar?.showModal();
});

document.querySelector('[data-calendar-close]')?.addEventListener('click', () => {
    calendar?.close();
});

calendar?.addEventListener('click', (event) => {
    if (event.target === calendar) {
        calendar.close();
    }
});

let installPrompt = null;
const installButtons = document.querySelectorAll('[data-pwa-install]');
const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

const installationId = () => {
    const storageKey = 'futebol-tv-installation-id';
    let id = null;

    try {
        id = window.localStorage.getItem(storageKey);
    } catch (_) {
        // Some privacy modes block local storage. An ephemeral ID still lets the request complete.
    }

    if (!id) {
        id = window.crypto?.randomUUID?.() || `${Date.now()}-${Math.random().toString(36).slice(2)}`;
        try {
            window.localStorage.setItem(storageKey, id);
        } catch (_) {
            // Keep the generated ID only for this page session when storage is unavailable.
        }
    }

    return id;
};

const recordInstallation = (event) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) return;

    fetch('/pwa/instalacoes', {
        method: 'POST',
        credentials: 'same-origin',
        keepalive: true,
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ installation_id: installationId(), event }),
    }).catch(() => {});
};

const showInstallButtons = () => {
    if (!isStandalone) {
        installButtons.forEach((button) => {
            button.classList.remove('hidden');
            if (button.hasAttribute('data-pwa-floating')) {
                button.classList.add('flex');
            }
        });
    }
};

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
    showInstallButtons();
});

installButtons.forEach((button) => {
    button.addEventListener('click', async () => {
        if (installPrompt) {
            installPrompt.prompt();
            const choice = await installPrompt.userChoice;
            if (choice.outcome === 'accepted') {
                recordInstallation('installed');
            }
            installPrompt = null;
            installButtons.forEach((item) => {
                item.classList.add('hidden');
                item.classList.remove('flex');
            });
            return;
        }

        if (isIos) {
            recordInstallation('interest');
            window.alert('No Safari, toque em Compartilhar e depois em “Adicionar à Tela de Início”.');
        }
    });
});

window.addEventListener('appinstalled', () => {
    recordInstallation('installed');
    installPrompt = null;
    installButtons.forEach((button) => {
        button.classList.add('hidden');
        button.classList.remove('flex');
    });
});

if (isIos) showInstallButtons();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' });
    });
}
