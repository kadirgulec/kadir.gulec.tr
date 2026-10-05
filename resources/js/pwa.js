/**
 * The installable app: registers the service worker (public/sw.js) and
 * offers window.kgPush to the notifications settings page.
 *
 * Push only reaches devices someone is signed in on: a page rendered for a
 * signed-out visitor (<html data-signed-in="false">) drops this browser's
 * push subscription. The server forgets the device on logout as well.
 */

const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

/** The VAPID public key (base64url) as the bytes PushManager wants. */
function keyBytes(base64url) {
    const base64 = (base64url + '='.repeat((4 - (base64url.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');

    return Uint8Array.from(atob(base64), (char) => char.charCodeAt(0));
}

function contentEncoding() {
    const encodings = PushManager.supportedContentEncodings ?? ['aesgcm'];

    return encodings.includes('aes128gcm') ? 'aes128gcm' : 'aesgcm';
}

async function registration() {
    return navigator.serviceWorker.ready;
}

window.kgPush = {
    supported,

    serialize(subscription) {
        return { ...subscription.toJSON(), contentEncoding: contentEncoding() };
    },

    async current() {
        return (await registration()).pushManager.getSubscription();
    },

    /** Asks for permission (needs a click) and subscribes; null when it was not given. */
    async subscribe(publicKey) {
        if ((await Notification.requestPermission()) !== 'granted') {
            return null;
        }

        const pushManager = (await registration()).pushManager;
        const options = { userVisibleOnly: true, applicationServerKey: keyBytes(publicKey) };
        let subscription;

        try {
            subscription = await pushManager.subscribe(options);
        } catch (error) {
            // Subscribed with an older key: start over with the current one.
            await (await pushManager.getSubscription())?.unsubscribe();
            subscription = await pushManager.subscribe(options);
        }

        return this.serialize(subscription);
    },

    /** Unsubscribes this browser; returns the endpoint so the server can forget it. */
    async unsubscribe() {
        const subscription = await this.current();

        if (!subscription) {
            return null;
        }

        await subscription.unsubscribe();

        return subscription.endpoint;
    },
};

if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            const worker = await navigator.serviceWorker.register('/sw.js');

            if (supported && document.documentElement.dataset.signedIn === 'false') {
                await (await worker.pushManager.getSubscription())?.unsubscribe();
            }
        } catch {
            // No service worker (private mode, an old browser): the site works without it.
        }
    });
}
