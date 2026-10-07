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
            await installPrompt.userChoice;
            installPrompt = null;
            installButtons.forEach((item) => {
                item.classList.add('hidden');
                item.classList.remove('flex');
            });
            return;
        }

        if (isIos) {
            window.alert('No Safari, toque em Compartilhar e depois em “Adicionar à Tela de Início”.');
        }
    });
});

window.addEventListener('appinstalled', () => {
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
