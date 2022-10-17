/**
 * @source https://web-push-book.gauntface.com/subscribing-a-user/
 * @returns {Promise<boolean>}
 */
function askPermission() {
    return new Promise(function (resolve, reject) {
        const permissionResult = Notification.requestPermission(function (result) {
            resolve(result);
        });

        if (permissionResult) {
            permissionResult.then(resolve, reject);
        }
    })
        .then(function (permissionResult) {
            if (permissionResult !== 'granted') {
                throw new Error('We weren\'t granted permission.');
            }
        });
}

/**
 * @source https://web-push-book.gauntface.com/subscribing-a-user/
 * @returns Uint8Array
 */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}


/**
 * @source https://web-push-book.gauntface.com/subscribing-a-user/
 * @returns {Promise<boolean>}
 */
function getNotificationPermissionState() {
    if (navigator.permissions) {
        return navigator.permissions.query({name: 'notifications'})
            .then((result) => {
                return result.state;
            });
    }

    return new Promise((resolve) => {
        resolve(Notification.permission);
    });
}

if ('serviceWorker' in navigator && 'PushManager' in window) {
    const url = globals.rootdir + '/modules/3cx_centralino/ajax/chiamate.php';

    navigator.serviceWorker.register(globals.rootdir + '/modules/3cx_centralino/ajax/caller.js')
        .then(function (registration) {
            return registration.pushManager
        })
        .then(async function (manager) {
            const subscription = await manager.getSubscription();
            if (subscription) {
                return subscription;
            }

            const response = await fetch(url + '?public_key=true');
            const vapidPublicKey = await response.text();

            const convertedVapidKey = urlBase64ToUint8Array(vapidPublicKey);

            return manager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: convertedVapidKey
            });
        }).then(function (subscription) {
            fetch(url + '?register=true', {
                method: 'post',
                headers: {
                    'Content-type': 'application/json'
                },
                body: JSON.stringify(subscription),
            });
        });
}

self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('push', function (event) {
    const chiamata = event.data.json();
    const descrizione = chiamata.anagrafica ? chiamata.anagrafica.ragione_sociale : 'Contatto sconosciuto';

    // Data visibile
    const data = new Date(chiamata.timestamp);

    // Collegamento alla scheda di informazioni
    const actions = [{
        action: 'info',
        title: 'Info'
    }];

    // Collegamento alla scheda anagrafica solo se presente
    if (chiamata.anagrafica) {
        actions.push({
            action: 'anagrafica',
            title: 'Anagrafica'
        });
    }

    self.registration.showNotification('Chiamata in ingresso', {
        tag: 'call_' + chiamata.id,
        body: 'Chiamata da ' + chiamata.numero + ': ' + descrizione + ' [' + data.toLocaleString() + ']',
        data: chiamata,
        timestamp: data.unix,
        actions: actions,
    });
});

// Notification click event listener
self.addEventListener('notificationclick', async function (event) {
    // Close the notification popout
    event.notification.close();

    const chiamata = event.notification.data;

    let url;
    if (event.action === 'anagrafica') {
        url = chiamata.url.anagrafica;
    } else if (event.action === 'info') {
        url = chiamata.url.info;
    } else {
        url = chiamata.url.predefinito;
    }

    // Get all the Window clients
    event.waitUntil(self.clients.matchAll({ type: 'window' }).then(windows => {
        const windowIndex = windows.findIndex(windowClient => windowClient.url === url);

        // If a Window tab matching the targeted URL already exists, focus that;
        if (windowIndex !== -1) {
            windows[windowIndex].focus()
        }
        // Otherwise, open a new tab to the applicable URL and focus it.
        else {
            self.clients.openWindow(url).then(windowClient => windowClient ? windowClient.focus() : null);
        }
    }));
});
