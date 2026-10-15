export const PRICING_REMINDER_MS = 5 * 60 * 1000;

// Shared by page loads and tabs; reading a notification does not reset the
// pricing deadline. Only an empty pricing queue resets it.
export function advanceNotifications(snapshot, previous, now, { feedback = null, allowReminder = false } = {}) {
    const known = new Set(previous?.seenIds || []);
    const feedbackSeen = feedback && (previous?.feedbackId === feedback.id
        || feedback.notification_ids.some(id => known.has(id)));
    const sounds = new Set();
    if (feedback) {
        feedback.notification_ids.forEach(id => known.add(id));
        if (!feedbackSeen) feedback.sounds.forEach(sound => sounds.add(sound));
    }

    const incoming = previous
        ? snapshot.notifications.filter(notification => !known.has(notification.id))
        : [];
    incoming.forEach(notification => {
        if (notification.sound) sounds.add(notification.sound);
    });
    snapshot.notifications.forEach(notification => known.add(notification.id));

    let nextReminderAt = previous?.nextReminderAt || null;
    let reminder = false;
    if (snapshot.pending_pricing?.count > 0) {
        nextReminderAt ??= now + PRICING_REMINDER_MS;
        if (allowReminder && now >= nextReminderAt) {
            reminder = true;
            sounds.add('pricing');
            // No burst of missed reminders after sleep or a connection failure.
            nextReminderAt = now + PRICING_REMINDER_MS;
        }
    } else {
        nextReminderAt = null;
    }

    return {
        state: {
            // Retain every currently unread ID and a bounded recent history.
            seenIds: [...new Set([...Array.from(known).slice(-1000), ...snapshot.notifications.map(n => n.id)])],
            nextReminderAt,
            feedbackId: feedback?.id || previous?.feedbackId || null,
        },
        incoming,
        sounds: [...sounds],
        reminder,
    };
}
