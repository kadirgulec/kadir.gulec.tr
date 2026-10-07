/**
 * The installable app: registers the service worker (public/sw.js), keeps
 * this device's push subscription in step with the account, and offers
 * window.kgPush to the notifications settings page.
 *
 * Push follows the account, not the session:
 * - A session that runs out leaves push on; tapping a notification leads to
 *   the login and from there to its page.
 * - Signing out stops it: the server forgets the device and the next page
 *   (<meta name="kg-push-forget">, App\Listeners\ForgetPushDevice) has the
 *   browser unsubscribe too.
 * - Signing in again on a device where the same member had push on switches
 *   it back on by itself (<meta name="kg-push"> names who is signed in).
 */

const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

/** The member (user id) who switched push on on this device; kept across sign-outs. */
const OWNER_KEY = 'kg-push-owner';

/** The endpoint already sent to the server while the app is open. */
const SAVED_KEY = 'kg-push-saved';

/** Web storage can be missing or throw (private mode, blocked site data). */
function readStorage(area, key) {
    try {
        return window[area].getItem(key);
    } catch {
        return null;
    }
}

function writeStorage(area, key, value) {
    try {
        if (value === null) {
            window[area].removeItem(key);
        } else {
            window[area].setItem(key, value);
        }
    } catch {
        // Without storage push simply is not switched back on by itself.
    }
}

/** The signed-in member as the page tells it, or null when signed out. */
function member() {
    const meta = document.querySelector('meta[name="kg-push"]');

    return meta ? { id: meta.content, key: meta.dataset.key, save: meta.dataset.save, token: meta.dataset.token } : null;
}

/** The VAPID public key (base64url) as the bytes PushManager wants. */
function keyBytes(base64url) {
    const base64 = (base64url + '='.repeat((4 - (base64url.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');

    return Uint8Array.from(atob(base64), (char) => char.charCodeAt(0));
}

function contentEncoding() {
    const encodings = PushManager.supportedContentEncodings ?? ['aesgcm'];

    return encodings.includes('aes128gcm') ? 'aes128gcm' : 'aesgcm';
}

async function pushManager() {
    return (await navigator.serviceWorker.ready).pushManager;
}

function serialize(subscription) {
    return { ...subscription.toJSON(), contentEncoding: contentEncoding() };
}

/** Subscribes with the current key (needs the permission already given). */
async function subscribeWith(publicKey) {
    const manager = await pushManager();
    const options = { userVisibleOnly: true, applicationServerKey: keyBytes(publicKey) };

    try {
        return await manager.subscribe(options);
    } catch {
        // Subscribed with an older key: start over with the current one.
        await (await manager.getSubscription())?.unsubscribe();

        return manager.subscribe(options);
    }
}

/** Sends the subscription to the server (App\Http\Controllers\Account\PushDeviceController). */
async function save(subscription, signedIn) {
    const response = await fetch(signedIn.save, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': signedIn.token },
        body: JSON.stringify(serialize(subscription)),
    });

    if (response.ok) {
        writeStorage('sessionStorage', SAVED_KEY, subscription.endpoint);
    }
}

/** Brings this browser's subscription in line with who is (or was) signed in. */
async function keepInStep() {
    const subscription = await (await pushManager()).getSubscription();

    if (document.querySelector('meta[name="kg-push-forget"]')) {
        await subscription?.unsubscribe();

        return;
    }

    const signedIn = member();

    // Signed out because the session ran out: push keeps coming.
    if (!signedIn) {
        return;
    }

    const owner = readStorage('localStorage', OWNER_KEY);

    // Someone else signed in on this device: push stops for whoever switched it on.
    if (owner !== null && owner !== signedIn.id) {
        await subscription?.unsubscribe();
        writeStorage('localStorage', OWNER_KEY, null);

        return;
    }

    if ((owner === null && !subscription) || Notification.permission !== 'granted') {
        return;
    }

    // Also adopts devices that had push on before the owner was kept.
    writeStorage('localStorage', OWNER_KEY, signedIn.id);

    const current = subscription ?? (await subscribeWith(signedIn.key));

    if (readStorage('sessionStorage', SAVED_KEY) !== current.endpoint) {
        await save(current, signedIn);
    }
}

let settle;

window.kgPush = {
    supported,

    /** Resolves once the subscription is in step (see keepInStep). */
    ready: new Promise((resolve) => (settle = resolve)),

    serialize,

    async current() {
        return (await pushManager()).getSubscription();
    },

    /** Asks for permission (needs a click) and subscribes; null when it was not given. */
    async subscribe(publicKey) {
        if ((await Notification.requestPermission()) !== 'granted') {
            return null;
        }

        const subscription = await subscribeWith(publicKey);
        writeStorage('localStorage', OWNER_KEY, member()?.id ?? null);

        return serialize(subscription);
    },

    /** Unsubscribes this browser; returns the endpoint so the server can forget it. */
    async unsubscribe() {
        writeStorage('localStorage', OWNER_KEY, null);

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
            await navigator.serviceWorker.register('/sw.js');

            if (supported) {
                await keepInStep();
            }
        } catch {
            // No service worker (private mode, an old browser): the site works without it.
        } finally {
            settle();
        }
    });
} else {
    settle();
}
