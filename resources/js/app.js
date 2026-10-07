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
        navigator.serviceWorker.register('/sw.js?v=2', { scope: '/', updateViaCache: 'none' });
    });
}

const pushDialog = document.querySelector('#push-preferences');
const pushButtons = document.querySelectorAll('[data-push-open]');
const pushEnable = document.querySelector('[data-push-enable]');
const pushDisable = document.querySelector('[data-push-disable]');
const pushDaily = document.querySelector('[data-push-daily]');
const pushReminders = document.querySelector('[data-push-reminders]');
const pushStatus = document.querySelector('[data-push-status]');
const pushPublicKey = document.querySelector('meta[name="webpush-public-key"]')?.content;
const pushSupported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window && Boolean(pushPublicKey);

const pushHeaders = () => ({
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
});

const applicationServerKey = (value) => {
    const padding = '='.repeat((4 - value.length % 4) % 4);
    const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);

    return Uint8Array.from([...raw].map((character) => character.charCodeAt(0)));
};

const getPushSubscription = async () => {
    const registration = await navigator.serviceWorker.ready;
    return registration.pushManager.getSubscription();
};

const setPushUi = (subscribed) => {
    pushButtons.forEach((button) => {
        button.querySelector('[data-push-label]').textContent = subscribed ? 'Alertas ativos' : 'Ativar alertas';
    });
    pushEnable?.classList.toggle('hidden', subscribed);
    pushDisable?.classList.toggle('hidden', !subscribed);
};

const showPushButtons = () => {
    pushButtons.forEach((button) => {
        button.classList.remove('hidden');
        button.classList.add('flex');
    });
};

const loadPushPreferences = async () => {
    const response = await fetch('/push/status', {
        method: 'POST',
        credentials: 'same-origin',
        headers: pushHeaders(),
        body: JSON.stringify({ installation_id: installationId() }),
    });
    if (!response.ok) return;
    const preferences = await response.json();
    pushDaily.checked = preferences.daily_summary;
    pushReminders.checked = preferences.kickoff_reminders;
};

const savePushSubscription = async (subscription) => {
    const data = subscription.toJSON();
    const response = await fetch('/push/assinar', {
        method: 'POST',
        credentials: 'same-origin',
        headers: pushHeaders(),
        body: JSON.stringify({
            installation_id: installationId(),
            subscription: {
                endpoint: data.endpoint,
                keys: data.keys,
                content_encoding: 'aes128gcm',
            },
            daily_summary: pushDaily.checked,
            kickoff_reminders: pushReminders.checked,
        }),
    });
    if (!response.ok) throw new Error('Não foi possível salvar a assinatura.');
};

if (pushSupported) {
    showPushButtons();
    getPushSubscription().then((subscription) => setPushUi(Boolean(subscription))).catch(() => {});

    pushButtons.forEach((button) => button.addEventListener('click', async () => {
        if (isIos && !isStandalone) {
            window.alert('No iPhone, primeiro adicione o Futebol na TV à Tela de Início. Depois abra o aplicativo instalado para ativar os alertas.');
            return;
        }

        pushStatus.textContent = '';
        await loadPushPreferences().catch(() => {});
        setPushUi(Boolean(await getPushSubscription().catch(() => null)));
        pushDialog?.showModal();
    }));
}

document.querySelector('[data-push-close]')?.addEventListener('click', () => pushDialog?.close());
pushDialog?.addEventListener('click', (event) => {
    if (event.target === pushDialog) pushDialog.close();
});

pushEnable?.addEventListener('click', async () => {
    pushEnable.disabled = true;
    pushStatus.textContent = 'Solicitando autorização…';

    try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            pushStatus.textContent = 'A permissão não foi concedida. Você pode alterá-la nas configurações do navegador.';
            return;
        }

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription() || await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: applicationServerKey(pushPublicKey),
        });
        await savePushSubscription(subscription);
        setPushUi(true);
        pushStatus.textContent = 'Alertas ativados com sucesso.';
    } catch (_) {
        pushStatus.textContent = 'Não foi possível ativar os alertas neste dispositivo.';
    } finally {
        pushEnable.disabled = false;
    }
});

[pushDaily, pushReminders].forEach((input) => input?.addEventListener('change', async () => {
    const subscription = await getPushSubscription().catch(() => null);
    if (!subscription) return;
    savePushSubscription(subscription)
        .then(() => { pushStatus.textContent = 'Preferências atualizadas.'; })
        .catch(() => { pushStatus.textContent = 'Não foi possível atualizar as preferências.'; });
}));

pushDisable?.addEventListener('click', async () => {
    pushDisable.disabled = true;
    const subscription = await getPushSubscription().catch(() => null);

    try {
        if (subscription) {
            await fetch('/push/assinar', {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: pushHeaders(),
                body: JSON.stringify({ installation_id: installationId(), endpoint: subscription.endpoint }),
            });
            await subscription.unsubscribe();
        }
        setPushUi(false);
        pushStatus.textContent = 'Alertas desativados neste dispositivo.';
    } catch (_) {
        pushStatus.textContent = 'Não foi possível desativar os alertas.';
    } finally {
        pushDisable.disabled = false;
    }
});
