import test from 'node:test';
import assert from 'node:assert/strict';
import { advanceNotifications, PRICING_REMINDER_MS } from '../resources/js/notification-state.js';

const snapshot = (count = 0, notifications = []) => ({
    notifications,
    pending_pricing: { count, interval_ms: PRICING_REMINDER_MS },
});
const tick = (feed, state, at) => advanceNotifications(feed, state, at, { allowReminder: true });

test('pricing reminder repeats every five minutes and navigation/reading does not reset it', () => {
    const first = tick(snapshot(2, [{ id: 'request-1', sound: 'request' }]), null, 1000);
    assert.deepEqual(first.sounds, []); // Historic unread requests are not announced again.
    const reload = tick(snapshot(2), first.state, 121000);
    assert.equal(reload.state.nextReminderAt, 301000);
    assert.equal(tick(snapshot(2), reload.state, 300999).reminder, false);
    const due = tick(snapshot(2), reload.state, 301000);
    assert.equal(due.reminder, true);
    assert.deepEqual(due.sounds, ['pricing']);
    assert.equal(tick(snapshot(1), due.state, 600999).reminder, false);
    assert.equal(tick(snapshot(1), due.state, 601000).reminder, true);
});

test('pricing, archiving, or deleting the last pending request stops reminders; new queue starts a new deadline', () => {
    const first = tick(snapshot(1), null, 0);
    const empty = tick(snapshot(0), first.state, PRICING_REMINDER_MS);
    assert.equal(empty.reminder, false);
    assert.equal(empty.state.nextReminderAt, null);
    const newQueue = tick(snapshot(1), empty.state, 500000);
    assert.equal(newQueue.state.nextReminderAt, 800000);
    assert.equal(newQueue.reminder, false);
});

test('stale page snapshots cannot trigger reminders; recovery emits once without a catch-up burst', () => {
    const first = tick(snapshot(1), null, 0);
    const stale = advanceNotifications(snapshot(1), first.state, 3600000);
    assert.equal(stale.reminder, false);
    const fresh = tick(snapshot(1), stale.state, 3600000);
    assert.equal(fresh.reminder, true);
    assert.equal(fresh.state.nextReminderAt, 3900000);
    assert.equal(tick(snapshot(1), fresh.state, 3600001).reminder, false);
});

test('incoming request and pricing events use their own sounds once across tabs and page loads', () => {
    const first = tick(snapshot(), null, 0);
    const notes = [
        { id: 'n1', sound: 'request', booking_id: 'b1' },
        { id: 'n2', sound: 'request', booking_id: 'b1' },
        { id: 'n3', sound: 'pricing', booking_id: 'b2' },
    ];
    const incoming = tick(snapshot(0, notes), first.state, 15000);
    assert.deepEqual(incoming.sounds, ['request', 'pricing']);
    assert.equal(incoming.incoming.length, 3);
    const anotherTab = tick(snapshot(0, notes), incoming.state, 15001);
    assert.deepEqual(anotherTab.sounds, []);
    assert.deepEqual(anotherTab.incoming, []);
});

test('sender gets one acknowledgement even without permission to see recipients and no replay on back', () => {
    const feedback = { id: 'submission-1', sounds: ['request'], notification_ids: ['n1', 'n2'] };
    const sender = advanceNotifications(snapshot(), null, 0, { feedback });
    assert.deepEqual(sender.sounds, ['request']);
    const redirected = advanceNotifications(snapshot(1, [{ id: 'n1', sound: 'request' }]), sender.state, 10, { feedback });
    assert.deepEqual(redirected.sounds, []);
    assert.deepEqual(redirected.incoming, []);
});

test('users without a pricing queue never receive a pricing reminder', () => {
    const first = tick({ notifications: [], pending_pricing: null }, null, 0);
    const later = tick({ notifications: [], pending_pricing: null }, first.state, 999999999);
    assert.equal(later.reminder, false);
    assert.equal(later.state.nextReminderAt, null);
});
