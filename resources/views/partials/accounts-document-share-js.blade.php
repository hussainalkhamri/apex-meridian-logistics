function normalizeWhatsAppPhone(rawPhone) {
    let phone = (rawPhone || '').toString().trim();

    if (!phone) return '';

    phone = phone.replace(/^00/, '+');
    let digits = phone.replace(/[^\d+]/g, '');

    if (digits.startsWith('+')) {
        digits = digits.slice(1);
    }

    digits = digits.replace(/\D/g, '').replace(/^0+/, '');

    if (digits.length === 9 && digits.startsWith('7')) {
        digits = '967' + digits;
    }

    return digits;
}

function shareAccountsDocumentToWhatsApp(source) {
    const payload = source?.dataset?.accountsShare
        ? JSON.parse(source.dataset.accountsShare)
        : source;
    const phone = normalizeWhatsAppPhone(payload?.phone);

    if (!phone) {
        alert('رقم العميل غير متوفر لهذا المستند. يرجى تحديث رقم العميل في بيانات الطلب أولاً.');
        return;
    }

    const documentUrl = payload?.url
        ? payload.url
        : new URL(payload?.path || window.location.pathname, window.location.origin).toString();
    const label = payload?.label || 'المستند';
    const customerLine = payload?.customer ? `مرحباً ${payload.customer}` : 'مرحباً';
    const lines = [
        customerLine,
        `نرسل لكم ${label} رقم ${payload?.id || ''}`.trim(),
        payload?.bookingId ? `طلب الشحن: ${payload.bookingId}` : '',
        payload?.amount ? `المبلغ: ${payload.amount}` : '',
        `ملف PDF: ${documentUrl}`,
        'يمكنكم فتح الملف مباشرة أو تنزيله عند الحاجة.',
    ].filter(Boolean);

    window.open(`https://wa.me/${phone}?text=${encodeURIComponent(lines.join('\n'))}`, '_blank', 'noopener');
}
