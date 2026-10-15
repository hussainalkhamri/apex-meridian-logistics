import { advanceNotifications } from './notification-state.js';

const POLL_MS = 15_000;
const SOUND_STORAGE_KEY = 'shipmate:notification-sound-enabled';

function readSoundPreference() {
    try {
        return localStorage.getItem(SOUND_STORAGE_KEY) === '1';
    } catch {
        return false;
    }
}

function writeSoundPreference(enabled) {
    try {
        localStorage.setItem(SOUND_STORAGE_KEY, enabled ? '1' : '0');
    } catch {
        // Storage may be unavailable in private browsing or restricted contexts.
    }
}

function createSoundPlayer(config) {
    let context;
    let scheduledUntil = 0;
    let flushing = false;
    let enabled = readSoundPreference();
    const buffers = new Map();
    const pending = new Map();
    const buttons = [...document.querySelectorAll('[data-notification-sound-button]')];

    function status(key) {
        buttons.forEach(button => {
            button.title = config.strings[key];
            button.setAttribute('aria-label', config.strings[key]);
            const label = button.querySelector('[data-notification-sound-label]');
            if (label) label.textContent = config.strings[key];
        });
    }

    function syncFromStorage() {
        enabled = readSoundPreference();
        status(enabled ? 'sound_ready' : 'enable_sound');
        if (enabled && context && context.state !== 'running' && document.visibilityState === 'visible') {
            void unlock();
        }
    }

    function getContext() {
        if (!context) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextClass) throw new Error('Audio is unavailable');
            context = new AudioContextClass();
        }
        return context;
    }

    async function bufferFor(type) {
        if (!buffers.has(type)) {
            const loading = fetch(config.sounds[type], { credentials: 'same-origin' })
                .then(response => {
                    if (!response.ok) throw new Error('Unable to load notification sound');
                    return response.arrayBuffer();
                })
                .then(data => getContext().decodeAudioData(data))
                .catch(error => { buffers.delete(type); throw error; });
            buffers.set(type, loading);
        }
        return buffers.get(type);
    }

    async function flush() {
        if (flushing) return;
        flushing = true;
        try {
            const audioContext = getContext();
            if (audioContext.state !== 'running') {
                status('enable_sound');
                return;
            }
            status('sound_ready');
            while (pending.size) {
                const [type, item] = pending.entries().next().value;
                if (Date.now() - item.at > 60_000) { pending.delete(type); continue; }
                const buffer = await bufferFor(type);
                // A reminder can be cancelled while its asset is being loaded.
                if (pending.get(type) !== item) continue;
                if (audioContext.state !== 'running') { status('enable_sound'); break; }
                pending.delete(type);
                const source = audioContext.createBufferSource();
                source.buffer = buffer;
                source.connect(audioContext.destination);
                const start = Math.max(audioContext.currentTime, scheduledUntil);
                source.start(start);
                scheduledUntil = start + buffer.duration + 0.12;
            }
        } catch {
            status('sound_unavailable');
        } finally {
            flushing = false;
        }
    }

    function play(type = 'request', reason = 'event') {
        if (!Object.hasOwn(config.sounds, type)) return;
        pending.set(type, { at: Date.now(), reason });
        void flush();
    }

    function unlock() {
        enabled = true;
        writeSoundPreference(true);
        try {
            const audioContext = getContext();
            // Keep resume inside the actual user gesture. Only report success
            // after the browser has really allowed audio playback.
            audioContext.resume().then(() => {
                if (audioContext.state === 'running') {
                    status('sound_ready');
                    void flush();
                    return;
                }
                enabled = readSoundPreference();
                status(enabled ? 'sound_ready' : 'enable_sound');
            }).catch(() => {
                enabled = readSoundPreference();
                status(enabled ? 'sound_ready' : 'enable_sound');
            });
        } catch {
            status('sound_unavailable');
        }
    }

    ['pointerdown', 'keydown', 'touchstart'].forEach(event => {
        document.addEventListener(event, () => {
            if (!context || context.state !== 'running' || pending.size) unlock();
        }, { passive: true });
    });
    buttons.forEach(button => button.addEventListener('click', () => {
        unlock();
        play('request');
    }));
    window.addEventListener('storage', event => {
        if (event.key === SOUND_STORAGE_KEY) syncFromStorage();
    });
    syncFromStorage();

    return {
        play,
        unlock,
        cancelReminder() {
            if (pending.get('pricing')?.reason === 'reminder') pending.delete('pricing');
        },
    };
}

function startNotifications(config) {
    const player = createSoundPlayer(config);
    window.playSystemNotificationSound = player.play;
    window.unlockSystemNotificationSound = player.unlock;

    const customer = config.identity.startsWith('customer:');
    const prefix = customer ? 'customer-' : '';
    const list = document.getElementById(`${prefix}notification-list`);
    const badge = document.getElementById(`${prefix}notification-badge`);
    const markAll = document.getElementById(customer ? 'customer-mark-all-read-btn' : 'mark-all-read-btn');
    const storageKey = `shipmate:notifications:v1:${config.identity}`;
    const tabId = `${Date.now()}:${Math.random().toString(36).slice(2)}`;
    let memoryState = null;
    let pendingFeedback = config.feedback;
    let timer;
    let busy = false;
    let stopped = false;
    let snapshot = config.snapshot;
    let nextReminderAt = null;

    const notices = document.createElement('div');
    notices.className = 'notification-live-notices';
    notices.setAttribute('aria-live', 'polite');
    notices.setAttribute('aria-relevant', 'additions');
    document.body.append(notices);

    const reminderBanner = document.createElement('a');
    reminderBanner.className = 'notification-pricing-reminder';
    reminderBanner.hidden = true;
    reminderBanner.setAttribute('role', 'status');
    document.querySelector('main')?.prepend(reminderBanner);

    function announce(message, url) {
        const toast = document.createElement('div');
        toast.className = 'notification-live-toast';
        const content = document.createElement(url ? 'a' : 'span');
        content.textContent = message;
        if (url) content.href = url;
        const close = document.createElement('button');
        close.type = 'button';
        close.textContent = '×';
        close.setAttribute('aria-label', config.strings.dismiss);
        close.addEventListener('click', () => toast.remove());
        toast.append(content, close);
        notices.append(toast);
        while (notices.children.length > 3) notices.firstElementChild.remove();
        window.setTimeout(() => toast.remove(), 15_000);
    }

    function render(current) {
        if (badge) {
            badge.hidden = current.unread_count === 0;
            badge.textContent = current.unread_count > 99 ? '99+' : current.unread_count;
        }
        if (markAll) markAll.hidden = current.unread_count === 0;
        if (list) {
            list.replaceChildren();
            current.notifications.slice(0, 30).forEach(notification => {
                const item = document.createElement('li');
                item.className = 'py-2.5 text-xs';
                const link = document.createElement('a');
                link.className = 'block rounded px-1 hover:bg-secondary/20';
                link.href = notification.url;
                const message = document.createElement('div');
                message.className = 'font-medium text-foreground';
                message.textContent = notification.message;
                const time = document.createElement('div');
                time.className = 'mt-1 text-[10px] text-muted-foreground';
                time.textContent = notification.time;
                link.append(message, time);
                item.append(link);
                list.append(item);
            });
            if (!current.unread_count) {
                const empty = document.createElement('li');
                empty.className = 'py-6 text-center text-muted-foreground text-xs';
                empty.textContent = config.strings.empty;
                list.append(empty);
            }
        }
        if (current.pending_pricing?.count > 0) {
            reminderBanner.textContent = current.pending_pricing.message;
            reminderBanner.href = current.pending_pricing.url;
            reminderBanner.title = config.strings.open_pricing;
        } else {
            reminderBanner.hidden = true;
            player.cancelReminder();
        }
    }

    function readState() {
        try { return JSON.parse(localStorage.getItem(storageKey)) || memoryState; }
        catch { return memoryState; }
    }

    async function withTabLock(update) {
        if (navigator.locks) return navigator.locks.request(storageKey, update);

        // HTTP LAN origins do not offer Web Locks. A short, expiring lease
        // prevents two tabs from playing the same event/reminder there too.
        const leaseKey = `${storageKey}:lease`;
        const ownsLease = () => JSON.parse(localStorage.getItem(leaseKey))?.owner === tabId;
        try {
            const lease = JSON.parse(localStorage.getItem(leaseKey));
            if (lease && lease.owner !== tabId && lease.expires > Date.now()) return null;
            localStorage.setItem(leaseKey, JSON.stringify({ owner: tabId, expires: Date.now() + 5000 }));
        } catch {
            return update(); // Storage disabled: preserve single-tab operation.
        }
        await new Promise(resolve => window.setTimeout(resolve, 60));
        try {
            if (!ownsLease()) return null;
            return update();
        } finally {
            try { if (ownsLease()) localStorage.removeItem(leaseKey); } catch { /* storage unavailable */ }
        }
    }

    async function consume(current, options = {}) {
        const update = () => {
            const result = advanceNotifications(current, readState(), Date.now(), { ...options, feedback: pendingFeedback });
            memoryState = result.state;
            nextReminderAt = result.state.nextReminderAt;
            try { localStorage.setItem(storageKey, JSON.stringify(result.state)); } catch { /* private storage */ }
            return result;
        };
        const result = await withTabLock(update);
        if (!result) return;
        pendingFeedback = null;

        const groups = new Set();
        result.incoming.slice().reverse().forEach(notification => {
            const group = `${notification.booking_id || notification.id}:${notification.sound}`;
            if (groups.has(group)) return;
            groups.add(group);
            announce(notification.message, notification.url);
        });
        if (result.reminder) {
            reminderBanner.hidden = false;
            announce(current.pending_pricing.message, current.pending_pricing.url);
        }
        result.sounds.forEach(sound => player.play(sound,
            sound === 'pricing' && result.reminder && !result.incoming.some(n => n.sound === 'pricing')
                ? 'reminder' : 'event'));
    }

    function schedule(delay = POLL_MS) {
        clearTimeout(timer);
        if (!config.feed_url || stopped) return;
        if (nextReminderAt) delay = Math.min(delay, Math.max(1000, nextReminderAt - Date.now()));
        timer = window.setTimeout(poll, delay);
    }

    async function poll() {
        if (busy || stopped || !config.feed_url) return;
        busy = true;
        clearTimeout(timer);
        let retryDelay = POLL_MS;
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 10_000);
        try {
            const response = await fetch(config.feed_url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
            });
            if (response.redirected || [401, 403, 419].includes(response.status)) {
                stopped = true;
                reminderBanner.hidden = true;
                player.cancelReminder();
                return;
            }
            if (!response.ok) throw new Error('Notification feed unavailable');
            snapshot = await response.json();
            render(snapshot);
            // Only fresh successful server responses may trigger a reminder.
            await consume(snapshot, { allowReminder: true });
        } catch {
            retryDelay = 30_000;
            // Never repeatedly alert from stale data while offline.
            nextReminderAt = null;
        } finally {
            window.clearTimeout(timeout);
            busy = false;
            schedule(retryDelay);
        }
    }

    window.addEventListener('notifications:refresh', () => void poll());
    window.addEventListener('online', () => void poll());
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') void poll();
    });
    window.addEventListener('pagehide', () => { stopped = true; clearTimeout(timer); });
    window.addEventListener('pageshow', event => {
        if (event.persisted) { stopped = false; void poll(); }
    });
    render(snapshot);
    void consume(snapshot).then(() => schedule());
}

document.addEventListener('DOMContentLoaded', () => {
    const element = document.getElementById('notification-config');
    if (element) startNotifications(JSON.parse(element.textContent));
});
