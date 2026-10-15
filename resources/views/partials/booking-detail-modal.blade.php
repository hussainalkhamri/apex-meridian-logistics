{{-- Booking Detail Dialog --}}
<div id="detail-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-2xl bg-card rounded-2xl border border-border shadow-elegant overflow-hidden max-h-[90vh] flex flex-col" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b p-5 bg-secondary/20">
            <div>
                <div id="modal-booking-id" class="font-mono text-xs text-muted-foreground"></div>
                <h3 class="font-extrabold text-lg">تفاصيل طلب الشحن</h3>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="closeDetailModal()" class="h-8 w-8 rounded-lg hover:bg-secondary/40 flex items-center justify-center">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
        </div>
        <div class="p-6 overflow-y-auto flex-1 text-xs">
            <div id="modal-loading" class="flex flex-col items-center justify-center py-10 gap-3">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-solid border-accent border-r-transparent"></div>
                <span class="text-muted-foreground text-sm">جاري التحميل...</span>
            </div>
            <div id="modal-details" class="hidden space-y-4 text-sm">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border p-4 bg-secondary/20 space-y-1">
                        <div class="text-xs font-bold uppercase tracking-wider text-muted-foreground border-b pb-1 mb-2">بيانات العميل</div>
                        <div class="font-extrabold" id="det-company"></div>
                        <div class="text-xs text-muted-foreground">المسؤول: <span id="det-contact"></span></div>
                        <div class="text-xs text-muted-foreground" dir="ltr">هاتف: <span id="det-phone"></span></div>
                        <div class="text-xs text-muted-foreground" dir="ltr">بريد: <span id="det-email"></span></div>
                    </div>
                    <div class="rounded-xl border p-4 bg-secondary/20 space-y-2">
                        <div class="text-xs font-bold uppercase tracking-wider text-muted-foreground border-b pb-1 mb-2">المسار</div>
                        <div class="flex items-center gap-2 font-extrabold">
                            <span id="det-pol"></span>
                            <i data-lucide="arrow-left" class="h-4 w-4 text-accent"></i>
                            <span id="det-pod"></span>
                        </div>
                        <div id="det-route-details" class="hidden space-y-1 text-xs text-muted-foreground"></div>
                        <div class="text-xs text-muted-foreground">النوع: <span class="font-semibold text-foreground" id="det-cargo"></span></div>
                    </div>
                </div>
                <div class="rounded-xl border p-4 space-y-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-muted-foreground border-b pb-1 mb-2">بيانات البضاعة</div>
                    <div class="grid gap-3 text-xs sm:grid-cols-3">
                        <div><span class="text-muted-foreground block">الوزن</span><span class="font-bold" id="det-weight"></span></div>
                        <div><span class="text-muted-foreground block">الحجم</span><span class="font-bold" id="det-volume"></span></div>
                        <div><span class="text-muted-foreground block">DG</span><span class="font-bold" id="det-dangerous"></span></div>
                        <div class="col-span-3"><span class="text-muted-foreground block">الوصف</span><p class="font-medium mt-1 p-2 bg-secondary/30 rounded border border-border/40 text-xs" id="det-description"></p></div>
                        <div class="col-span-3"><span class="text-muted-foreground block">ملاحظات إضافية</span><p class="font-medium mt-1 p-2 bg-secondary/30 rounded border border-border/40 text-xs" id="det-notes"></p></div>
                        <div class="col-span-3">
                            <div class="mb-2 flex items-center gap-2">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-accent/10 text-accent"><i data-lucide="package-check" class="h-3.5 w-3.5"></i></span>
                                <span class="text-sm font-bold text-foreground">تفاصيل الشحنة الإضافية</span>
                            </div>
                            <div class="rounded-xl border border-border/40 bg-gradient-to-br from-secondary/20 to-background p-3 text-xs" id="det-cargo-details"></div>
                        </div>
                        <div class="col-span-3"><span class="text-muted-foreground block">المستندات والملفات</span><div class="font-medium mt-1 p-2 bg-secondary/30 rounded border border-border/40 text-xs" id="det-attachments"></div></div>
                    </div>
                </div>
                <div id="det-repricing-block" class="hidden rounded-xl border border-warning/30 bg-warning/10 p-4 text-sm">
                    <div class="flex items-center gap-2 font-black text-warning-foreground">
                        <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                        <span>{{ __('messages.orders_status_needs_pricing') }}</span>
                    </div>
                    <p class="mt-2 text-xs leading-6 text-muted-foreground">تم إرجاع الطلب من المبيعات للتسعير، وسيظهر السعر الجديد بعد اعتماد التسعير مرة أخرى.</p>
                </div>
                <div id="det-pricing-block" class="hidden rounded-xl border p-4 space-y-2 text-xs">
                    <div class="text-xs font-bold uppercase tracking-wider text-muted-foreground border-b pb-1 mb-2" id="p-pricing-heading">تفصيل الأسعار</div>
                    <div id="p-standard-pricing-lines" class="space-y-2">
                        <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">Ocean Freight</span><span class="font-bold" id="p-ocean"></span></div>
                        <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">Local Charges</span><span class="font-bold" id="p-origin"></span></div>
                        <div id="p-exw-row" class="hidden flex justify-between border-b pb-1"><span class="text-muted-foreground">{{ __('messages.exw_charges_details') }}</span><span class="font-bold" id="p-exw"></span></div>
                        <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">DTHC</span><span class="font-bold" id="p-dthc"></span></div>
                        <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">War Risk / BAF</span><span class="font-bold" id="p-war"></span></div>
                        <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">Other</span><span class="font-bold" id="p-other"></span></div>
                        <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">{{ __('messages.destination_port_change_fee_details') }}</span><span class="font-bold" id="p-destination-port-change"></span></div>
                        <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">{{ __('messages.other_penalties_details') }}</span><span class="font-bold" id="p-other-penalties"></span></div>
                    </div>
                    <div id="p-land-pricing-lines" class="hidden space-y-2"></div>
                    <div class="flex justify-between items-center pt-2 font-extrabold text-sm"><span class="text-primary">الإجمالي</span><span class="text-lg text-primary" id="p-total"></span></div>
                    <div id="p-agent-accounting-block" class="mt-3 rounded-lg border border-border bg-secondary/25 p-3 space-y-1">
                        <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">الوكيل</span><span class="font-bold" id="p-agent"></span></div>
                        <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">كمية الشراء</span><span class="font-bold" id="p-agent-qty"></span></div>
                        <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">سعر الشراء من الوكيل</span><span class="font-bold text-warning-foreground" id="p-agent-cost"></span></div>
                        <div class="flex justify-between pt-1"><span class="font-bold text-muted-foreground">الربح / الخسارة</span><span class="font-black" id="p-profit"></span></div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <a id="det-pdf-link" href="#" target="_blank" class="inline-flex h-8 items-center justify-center rounded-lg bg-gradient-accent px-3 text-xs font-bold text-accent-foreground">
                            <i data-lucide="printer" class="ml-1.5 h-3.5 w-3.5"></i> طباعة العرض
                        </a>
                    </div>
                </div>

                <div id="det-shipping-block" class="hidden rounded-xl border p-4 space-y-2 text-xs bg-secondary/15">
                    <div class="text-xs font-bold uppercase tracking-wider text-muted-foreground border-b pb-1 mb-2">بيانات بوليصة الشحن والعمليات (MBL / Shipping Details)</div>
                    <div class="grid gap-3 sm:grid-cols-2 text-xs">
                        <div class="col-span-2 border-b pb-1"><span class="text-muted-foreground">الشاحن / Shipper:</span> <p class="font-semibold mt-0.5 whitespace-pre-wrap" id="det-mbl-shipper"></p></div>
                        <div class="col-span-2 border-b pb-1"><span class="text-muted-foreground">المرسل إليه / Consignee:</span> <p class="font-semibold mt-0.5 whitespace-pre-wrap" id="det-mbl-consignee"></p></div>
                        <div class="col-span-2 border-b pb-1"><span class="text-muted-foreground">الطرف المخطر / Notify Party:</span> <p class="font-semibold mt-0.5 whitespace-pre-wrap" id="det-mbl-notify"></p></div>
                        <div><span class="text-muted-foreground">نوع البوليصة / Bill Type:</span> <strong class="text-foreground" id="det-mbl-bill-type"></strong></div>
                        <div><span class="text-muted-foreground">نوع الحركة / Move Type:</span> <strong class="text-foreground" id="det-mbl-move-type"></strong></div>
                        <div><span class="text-muted-foreground">رقم الوظيفة / Job No:</span> <strong class="text-foreground" id="det-mbl-job-no"></strong></div>
                        <div><span class="text-muted-foreground">الوكيل الخارجي / Agent:</span> <strong class="text-foreground" id="det-mbl-agent"></strong></div>
                        <div><span class="text-muted-foreground">مكان التسليم / Delivery:</span> <strong class="text-foreground" id="det-mbl-delivery"></strong></div>
                        <div><span class="text-muted-foreground">الكمية والطرود / Packages:</span> <strong class="text-foreground" id="det-mbl-packages"></strong></div>
                        <div><span class="text-muted-foreground">الحجم الفعلي / Volume:</span> <strong class="text-foreground" id="det-mbl-meas-cbm"></strong></div>
                        <div class="col-span-2 border-t pt-1"><span class="text-muted-foreground">الحاويات والأختام / Seal Nos:</span> <p class="font-semibold mt-0.5 whitespace-pre-wrap" id="det-mbl-seals"></p></div>
                        <div class="col-span-2 border-t pt-1"><span class="text-muted-foreground">ملاحظات العمليات / Ops Notes:</span> <p class="font-semibold mt-0.5 whitespace-pre-wrap" id="det-mbl-notes"></p></div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button id="det-mbl-edit" type="button" class="hidden inline-flex h-8 items-center justify-center rounded-lg border border-border bg-card px-3 text-xs font-bold hover:bg-secondary/40">
                            <i data-lucide="edit-3" class="ml-1.5 h-3.5 w-3.5"></i> {{ __('messages.bookings_edit') }}
                        </button>
                        <a id="det-mbl-link" href="#" target="_blank" class="inline-flex h-8 items-center justify-center rounded-lg bg-primary text-white border border-primary/20 px-3 text-xs font-bold hover:bg-primary/95">
                            <i data-lucide="file-text" class="ml-1.5 h-3.5 w-3.5"></i> مسودة بوليصة الشحن (Draft MBL)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fallback floating toolbar (fixed) in case modal buttons are hidden by layout issues -->
<div id="agent-forward-toolbar" style="display:none; position:fixed; right:20px; bottom:20px; z-index:200000;">
    <div class="inline-flex gap-2">
        <button type="button" id="toolbar-agent-whatsapp" disabled class="inline-flex h-9 items-center justify-center rounded-lg bg-green-600 px-4 text-xs font-bold text-white">{{ __('messages.agent_forward_whatsapp') }}</button>
        <button type="button" id="toolbar-agent-email" disabled class="inline-flex h-9 items-center justify-center rounded-lg bg-blue-600 px-4 text-xs font-bold text-white">{{ __('messages.agent_forward_email') }}</button>
        <button type="button" id="toolbar-agent-cancel" class="inline-flex h-9 items-center justify-center rounded-lg border border-border bg-card px-4 text-xs font-bold">{{ __('messages.agent_forward_cancel') }}</button>
    </div>
</div>

<!-- Toast container for non-blocking notifications -->
<div id="copilot-toast-container" style="position:fixed; right:20px; bottom:20px; z-index:1000000; display:flex; flex-direction:column; gap:8px; pointer-events:none;"></div>

<script>
    let notificationSoundContext = null;
    let notificationSoundUnlocked = false;
    let notificationSoundQueued = false;

    function ensureNotificationSound() {
        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextClass) return false;

            if (!notificationSoundContext) {
                notificationSoundContext = new AudioContextClass();
            }

            if (!notificationSoundUnlocked) {
                notificationSoundUnlocked = true;
                if (notificationSoundContext.state === 'suspended') {
                    notificationSoundContext.resume().catch(() => {});
                }
            }

            return true;
        } catch (e) {
            return false;
        }
    }

    function unlockNotificationSound() {
        if (notificationSoundUnlocked) return;
        ensureNotificationSound();
        if (notificationSoundContext && notificationSoundContext.state === 'running') {
            if (notificationSoundQueued) {
                notificationSoundQueued = false;
                playNotificationSound();
            }
        }
    }

    function playNotificationSound() {
        if (window.playSystemNotificationSound) {
            window.playSystemNotificationSound();
            return;
        }

        try {
            if (!ensureNotificationSound()) return;

            if (notificationSoundContext.state === 'suspended') {
                notificationSoundQueued = true;
                notificationSoundContext.resume().catch(() => {});
                return;
            }

            const context = notificationSoundContext;
            const notes = [
                { freq: 523.25, start: 0, duration: 0.25, volume: 0.35 },  // C5
                { freq: 659.25, start: 0.1, duration: 0.25, volume: 0.35 }, // E5
                { freq: 783.99, start: 0.2, duration: 0.35, volume: 0.4 }   // G5
            ];

            notes.forEach(note => {
                const osc = context.createOscillator();
                const gainNode = context.createGain();
                
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(note.freq, context.currentTime + note.start);
                
                gainNode.gain.setValueAtTime(0.0001, context.currentTime + note.start);
                gainNode.gain.exponentialRampToValueAtTime(note.volume, context.currentTime + note.start + 0.02);
                gainNode.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + note.start + note.duration);
                
                osc.connect(gainNode);
                gainNode.connect(context.destination);
                
                osc.start(context.currentTime + note.start);
                osc.stop(context.currentTime + note.start + note.duration);
            });
        } catch (e) {}
    }

    ['pointerdown', 'keydown', 'touchstart'].forEach((eventName) => {
        document.addEventListener(eventName, unlockNotificationSound, { once: true });
    });

    function showToast(message, type = 'info', timeout = 4000) {
        const container = document.getElementById('copilot-toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.style.pointerEvents = 'auto';
        toast.style.minWidth = '200px';
        toast.style.maxWidth = '320px';
        toast.style.padding = '10px 12px';
        toast.style.borderRadius = '8px';
        toast.style.boxShadow = '0 6px 18px rgba(0,0,0,0.12)';
        toast.style.color = '#fff';
        toast.style.fontSize = '13px';
        toast.style.lineHeight = '1.2';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(8px)';
        toast.style.transition = 'opacity 200ms ease, transform 200ms ease';

        if (type === 'success') toast.style.background = '#16a34a';
        else if (type === 'error') toast.style.background = '#dc2626';
        else toast.style.background = '#374151';

        toast.textContent = message;
        container.appendChild(toast);

        // animate in
        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        });

        const hide = () => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(8px)';
            setTimeout(() => { try { container.removeChild(toast); } catch (e) {} }, 220);
        };

        const timer = setTimeout(hide, timeout);
        toast.addEventListener('click', () => { clearTimeout(timer); hide(); });

        playNotificationSound();
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('detail-modal');
        if (modal) {
            modal.addEventListener('click', closeDetailModal);
        }
    });

    function formatBookingCargoLabel(cargoData) {
        const cargoDetails = cargoData?.cargoDetails || {};
        const containerSummary = cargoData?.containerSummary || cargoDetails.container_summary || (Array.isArray(cargoDetails.containers)
            ? cargoDetails.containers
                .map(container => {
                    const qty = parseInt(container?.qty || 0, 10);
                    const type = String(container?.type || '').trim();
                    return qty > 0 && type ? `${qty}x${type}` : '';
                })
                .filter(Boolean)
                .join(' + ')
            : '');
        let cargo = cargoData?.shipmentType || '';

        if (containerSummary) {
            return `${cargo} (${containerSummary})`;
        }

        if (cargoData?.containerType) {
            cargo += ` (${cargoData.containerQty}x ${cargoData.containerType})`;
        }

        return cargo;
    }

    function escapeDetailHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function bookingAttachmentUrl(bookingId, attachmentPath) {
        const encodedBookingId = encodeURIComponent(String(bookingId ?? ''));
        const encodedPath = String(attachmentPath ?? '')
            .replace(/\\/g, '/')
            .replace(/^\/+/, '')
            .split('/')
            .map(segment => encodeURIComponent(segment))
            .join('/');

        return `/orders/${encodedBookingId}/attachments/${encodedPath}`;
    }

    function openDetailModal(id) {
        const modal = document.getElementById('detail-modal');
        if (!modal) return;
        modal.classList.remove('hidden');
        document.getElementById('modal-loading').classList.remove('hidden');
        document.getElementById('modal-details').classList.add('hidden');
        document.getElementById('modal-booking-id').innerText = id;

        const detailUrl = typeof window.bookingDetailEndpoint === 'function'
            ? window.bookingDetailEndpoint(id)
            : `/orders/${encodeURIComponent(id)}`;

        fetch(detailUrl, {
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('modal-loading').classList.add('hidden');
                document.getElementById('modal-details').classList.remove('hidden');

                document.getElementById('det-company').innerText = data.customer.company;
                document.getElementById('det-contact').innerText = data.customer.contactName;
                document.getElementById('det-phone').innerText = data.customer.phone;
                document.getElementById('det-email').innerText = data.customer.email;
                document.getElementById('det-pol').innerText = data.route.pol;
                document.getElementById('det-pod').innerText = data.route.pod;
                const routeDetailsBlock = document.getElementById('det-route-details');
                const routeDetails = data.route.details || null;
                routeDetailsBlock.replaceChildren();

                if (routeDetails && routeDetails.mode) {
                    const routeRows = [
                        ['شرط الشحن', routeDetails.mode],
                        ['دولة الشحن', routeDetails.origin?.country],
                        ['مدينة الشحن', routeDetails.origin?.city],
                        ['دولة الوصول', routeDetails.destination?.country],
                        ['مدينة الوصول', routeDetails.destination?.city],
                    ].filter(([, value]) => value);

                    routeRows.forEach(([label, value]) => {
                        const row = document.createElement('div');
                        row.className = 'flex justify-between gap-3';

                        const labelSpan = document.createElement('span');
                        labelSpan.textContent = label;

                        const valueSpan = document.createElement('span');
                        valueSpan.className = 'font-semibold text-foreground text-left';
                        valueSpan.textContent = value;

                        row.append(labelSpan, valueSpan);
                        routeDetailsBlock.appendChild(row);
                    });
                    routeDetailsBlock.classList.remove('hidden');
                } else {
                    routeDetailsBlock.classList.add('hidden');
                }

                document.getElementById('det-cargo').innerText = formatBookingCargoLabel(data.cargo);
                document.getElementById('det-weight').innerText = parseFloat(data.cargo.weightKg).toLocaleString() + ' كجم';
                document.getElementById('det-volume').innerText = data.cargo.volumeCbm + ' CBM';
                document.getElementById('det-dangerous').innerText = data.cargo.dangerous ? 'نعم — DG' : 'لا';
                document.getElementById('det-description').innerText = data.cargo.goodsDescription;
                document.getElementById('det-notes').innerText = data.cargo.notes || '—';

                const cargoDetailsBlock = document.getElementById('det-cargo-details');
                const cargoDetails = data.cargo.cargoDetails || null;
                const cargoDetailLabelMap = {
                    service_type: 'نوع الخدمة',
                    shipping_category: 'تصنيف الشحن',
                    route_mode: 'طريقة المسار',
                    shipmentType: 'نوع الشحنة',
                    shipment_type: 'نوع الشحنة',
                    main_transport_mode: 'وسيلة النقل الرئيسية',
                    origin_airport: 'مطار الشحن',
                    destination_airport: 'مطار الوصول',
                    origin_country: 'دولة الشحن',
                    origin_city: 'مدينة الشحن',
                    destination_country: 'دولة الوصول',
                    destination_city: 'مدينة الوصول',
                    pickup_address: 'عنوان الاستلام',
                    pickup_contact_name: 'مسؤول الاستلام',
                    pickup_phone: 'هاتف الاستلام',
                    delivery_address: 'عنوان التسليم',
                    delivery_contact_name: 'مسؤول التسليم',
                    delivery_phone: 'هاتف التسليم',
                    cargo_name: 'اسم البضاعة',
                    goods_description: 'وصف البضاعة',
                    cargo_type: 'نوع البضاعة',
                    hs_code: 'رمز النظام المنسق HS',
                    country_of_origin: 'بلد المنشأ',
                    packages: 'الطرود',
                    package_type: 'نوع التغليف',
                    pieces_count: 'عدد القطع',
                    packages_count: 'عدد الطرود',
                    pieces: 'عدد القطع',
                    qty: 'الكمية',
                    truck_qty: 'عدد الشاحنات',
                    from_city: 'من المدينة',
                    to_city: 'إلى المدينة',
                    origin_city: 'مدينة الانطلاق',
                    destination_city: 'مدينة الوصول',
                    piece_weight: 'وزن القطعة',
                    total_weight: 'إجمالي الوزن',
                    gross_weight_kg: 'إجمالي الوزن',
                    net_weight_kg: 'صافي الوزن',
                    volume_cbm: 'الحجم',
                    volumetric_weight_kg: 'الوزن الحجمي',
                    chargeable_weight_kg: 'الوزن المحتسب',
                    length: 'الطول',
                    length_cm: 'الطول',
                    length_m: 'الطول',
                    width: 'العرض',
                    width_cm: 'العرض',
                    width_m: 'العرض',
                    height: 'الارتفاع',
                    height_cm: 'الارتفاع',
                    height_m: 'الارتفاع',
                    cbm: 'CBM',
                    piece_cbm: 'CBM للقطعة الواحدة',
                    total_cbm: 'إجمالي CBM',
                    dimensions: 'الأبعاد',
                    packaging_type: 'نوع التغليف',
                    lifting_method: 'طريقة الرفع',
                    stackable: 'يمكن الرص',
                    vehicle_type: 'نوع المركبة',
                    manufacturer: 'الشركة المصنعة',
                    model: 'الموديل',
                    year: 'سنة الصنع',
                    color: 'اللون',
                    vin: 'VIN',
                    chassis_number: 'رقم الهيكل',
                    engine_number: 'رقم المحرك',
                    plate_number: 'رقم اللوحة',
                    weight: 'الوزن',
                    weight_kg: 'الوزن',
                    running: 'تمشي؟',
                    tow_required: 'السحب مطلوب',
                    fuel_level: 'كمية الوقود',
                    has_key: 'مفتاح',
                    has_items: 'أغراض داخل السيارة',
                    value: 'قيمة السيارة',
                    reefer_temperature: 'درجة البرودة',
                    land_shipments: 'مسارات الشحن البري',
                    land_shipment_summary: 'ملخص الشحن البري',
                    land_shipment_total_qty: 'إجمالي الشاحنات',
                    land_pallet_count: 'عدد الطبليات',
                    land_carton_count: 'عدد الكراتين',
                    declared_value: 'القيمة المصرح بها',
                    commercial_value: 'القيمة التجارية',
                    currency: 'العملة',
                    incoterm: 'شرط الشحن',
                    ready_date: 'تاريخ الجاهزية',
                    pickup_ready_date: 'تاريخ جاهزية الاستلام',
                    pickup_time_from: 'وقت الاستلام من',
                    pickup_time_to: 'وقت الاستلام إلى',
                    delivery_required_date: 'تاريخ التسليم المطلوب',
                    preferred_pickup_time: 'وقت الاستلام المفضل',
                    delivery_notes: 'ملاحظات التسليم',
                    service: 'الخدمات',
                    service_level: 'مستوى الخدمة',
                    air_service_level: 'مستوى خدمة الشحن الجوي',
                    direct_flight_preferred: 'تفضيل رحلة مباشرة',
                    insurance_required: 'التأمين مطلوب',
                    customs_clearance_origin: 'تخليص جمركي في بلد الشحن',
                    customs_clearance_destination: 'تخليص جمركي في بلد الوصول',
                    pickup_required: 'الاستلام مطلوب',
                    delivery_required: 'التسليم مطلوب',
                    export_customs_clearance: 'تخليص الصادر',
                    import_customs_clearance: 'تخليص الوارد',
                    dangerous_goods: 'بيانات البضاعة الخطرة',
                    is_dangerous: 'بضاعة خطرة',
                    dangerous: 'بضاعة خطرة',
                    un_number: 'رقم UN',
                    iata_class: 'تصنيف IATA',
                    packing_group: 'مجموعة التعبئة',
                    proper_shipping_name: 'اسم الشحن الصحيح',
                    msds_available: 'ملف MSDS متوفر',
                    msds_required: 'ملف MSDS مطلوب',
                    trade: 'بيانات التجارة',
                    notes: 'ملاحظات',
                };
                const cargoDetailValueMap = {
                    sea: 'شحن بحري',
                    air: 'شحن جوي',
                    land: 'شحن بري',
                    fcl: 'حاوية كاملة FCL',
                    lcl: 'شحن جزئي LCL',
                    bulk: 'بضائع سائبة Bulk',
                    roro: 'شحن سيارات RoRo',
                    'ro-ro': 'شحن سيارات RoRo',
                    door_to_door: 'من الباب إلى الباب',
                    warehouse_to_door: 'من المستودع إلى الباب',
                    door_to_port: 'من الباب إلى الميناء',
                    port_to_door: 'من الميناء إلى الباب',
                    AIR: 'جوي',
                    SEA: 'بحري',
                    LAND: 'بري',
                    DOOR_TO_DOOR: 'من الباب إلى الباب',
                    standard: 'قياسي',
                    express: 'سريع',
                    economy: 'اقتصادي',
                    general: 'بضائع عامة',
                    dangerous: 'بضائع خطرة',
                    perishable: 'قابلة للتلف',
                    pharmaceutical: 'أدوية ومستحضرات طبية',
                    valuable: 'بضاعة ثمينة',
                    fragile: 'قابلة للكسر',
                    live_animals: 'حيوانات حية',
                    temperature_controlled: 'تحكم بدرجة الحرارة',
                    oversized: 'أحجام كبيرة',
                    carton: 'كرتون',
                    Carton: 'كرتون',
                    Pallet: 'طبليات',
                    pallet: 'طبليات',
                    Bundle: 'ربطة',
                    bundle: 'ربطة',
                    Crate: 'صندوق خشبي',
                    crate: 'صندوق خشبي',
                    Box: 'صندوق',
                    box: 'صندوق',
                    Bag: 'كيس',
                    bag: 'كيس',
                    Drum: 'برميل',
                    drum: 'برميل',
                    Loose: 'سائب',
                    loose: 'سائب',
                    Forklift: 'رافعة شوكية',
                    forklift: 'رافعة شوكية',
                    Crane: 'رافعة',
                    crane: 'رافعة',
                    Manual: 'يدوي',
                    manual: 'يدوي',
                    full: 'ممتلئ',
                    half: 'نصف',
                    quarter: 'ربع',
                    empty: 'فارغ',
                    morning: 'صباحًا',
                    afternoon: 'بعد الظهر',
                    evening: 'مساءً',
                };
                const cargoDetailHiddenKeys = new Set([
                    'container_summary',
                    'container_total_qty',
                    'pricing_currency',
                    'fcl_pricing_lines',
                    'fcl_standard_charges',
                    'agent_purchase_lines',
                    'agent_purchase_amount',
                    'agent_purchase_quantity',
                    'agent_purchase_currency',
                    'agent_purchase_notes',
                    'profit_amount',
                    'land_pricing_lines',
                    'land_pricing_total',
                    'land_pricing_quantity',
                    'land_pricing_basis',
                    'door_to_door_pricing',
                    'package_dimension_unit',
                    'air_pricing',
                ]);
                const landDetailKeys = new Set([
                    'service_type',
                    'land_carton_count',
                    'land_shipments',
                    'land_shipment_summary',
                    'land_shipment_total_qty',
                ]);

                const cargoDetailLabel = (key) => cargoDetailLabelMap[key] || String(key || '').replace(/_/g, ' ');
                const cargoDetailValue = (key, value) => {
                    if (value === null || value === '' || value === undefined) {
                        return '—';
                    }

                    if (typeof value === 'boolean') {
                        return value ? 'نعم' : 'لا';
                    }

                    if (typeof value === 'number') {
                        const unit = String(key || '').endsWith('_kg') || key === 'weight' || key === 'piece_weight' || key === 'total_weight' ? ' كجم'
                            : (String(key || '').includes('cbm') || key === 'volume_cbm' ? ' CBM'
                                : (String(key || '').endsWith('_cm') ? ' سم'
                                    : (String(key || '').endsWith('_m') || ['length', 'width', 'height'].includes(key) ? ' م' : '')));
                        return `${value.toLocaleString()}${unit}`;
                    }

                    if (Array.isArray(value)) {
                        const simpleValues = value.filter(item => item !== null && typeof item !== 'object');
                        if (simpleValues.length === value.length) {
                            return simpleValues.map(item => cargoDetailValue(key, item)).join('، ');
                        }

                        return value.map((item, index) => {
                            if (!item || typeof item !== 'object') {
                                return cargoDetailValue(key, item);
                            }

                            const parts = Object.entries(item)
                                .filter(([childKey, childValue]) => !cargoDetailHiddenKeys.has(childKey) && childValue !== null && childValue !== '' && childValue !== undefined)
                                .map(([childKey, childValue]) => `${cargoDetailLabel(childKey)}: ${cargoDetailValue(childKey, childValue)}`);

                            return parts.length ? `#${index + 1} - ${parts.join(' | ')}` : '';
                        }).filter(Boolean).join('\n');
                    }

                    if (typeof value === 'object') {
                        return Object.entries(value)
                            .filter(([childKey, childValue]) => !cargoDetailHiddenKeys.has(childKey) && childValue !== null && childValue !== '' && childValue !== undefined)
                            .map(([childKey, childValue]) => `${cargoDetailLabel(childKey)}: ${cargoDetailValue(childKey, childValue)}`)
                            .join('\n');
                    }

                    const normalized = String(value).trim();
                    return cargoDetailValueMap[normalized] || cargoDetailValueMap[normalized.toLowerCase()] || normalized;
                };
                const hasDetailValue = (value) => {
                    if (value === null || value === '' || value === undefined) return false;
                    if (Array.isArray(value)) return value.length > 0;
                    if (typeof value === 'object') return Object.keys(value).length > 0;
                    return true;
                };
                const formatPlainNumber = (value) => {
                    const numeric = Number(value || 0);

                    if (!Number.isFinite(numeric)) {
                        return '—';
                    }

                    return numeric.toLocaleString(undefined, {
                        maximumFractionDigits: 2,
                    });
                };
                const createCargoStat = (label, value) => {
                    const stat = document.createElement('div');
                    stat.className = 'rounded-lg border border-border/40 bg-background/75 p-2';

                    const labelElement = document.createElement('div');
                    labelElement.className = 'text-[11px] font-semibold text-muted-foreground';
                    labelElement.textContent = label;

                    const valueElement = document.createElement('div');
                    valueElement.className = 'mt-1 break-words text-sm font-extrabold text-foreground';
                    valueElement.textContent = value;

                    stat.append(labelElement, valueElement);
                    return stat;
                };
                const createLandRouteCell = (label, value) => {
                    const cell = document.createElement('div');
                    cell.className = 'min-w-0';

                    const labelElement = document.createElement('div');
                    labelElement.className = 'text-[10px] font-bold text-muted-foreground';
                    labelElement.textContent = label;

                    const valueElement = document.createElement('div');
                    valueElement.className = 'mt-0.5 break-words font-bold text-foreground';
                    valueElement.textContent = value || '—';

                    cell.append(labelElement, valueElement);
                    return cell;
                };
                const appendLandCargoDetails = () => {
                    const isLandDetails = Boolean(
                        data.cargo?.isLand
                        || cargoDetails?.service_type === 'land'
                        || String(data.cargo?.shipmentType || '').toUpperCase() === 'LAND'
                    );

                    if (!isLandDetails) {
                        return false;
                    }

                    const payloadLines = Array.isArray(data.cargo?.landShipmentLines) ? data.cargo.landShipmentLines : [];
                    const detailLines = Array.isArray(cargoDetails?.land_shipments) ? cargoDetails.land_shipments : [];
                    const landLines = (payloadLines.length ? payloadLines : detailLines)
                        .map(line => {
                            const qty = Number(line?.qty ?? line?.truck_qty ?? 0);
                            const fromCity = String(line?.from_city ?? line?.origin_city ?? '').trim();
                            const toCity = String(line?.to_city ?? line?.destination_city ?? '').trim();

                            if (qty <= 0 && fromCity === '' && toCity === '') {
                                return null;
                            }

                            return {
                                qty,
                                fromCity,
                                toCity,
                            };
                        })
                        .filter(Boolean);

                    const totalTrucks = Number(data.cargo?.landShipmentTotalQty || 0)
                        || landLines.reduce((total, line) => total + Number(line.qty || 0), 0);
                    const palletCount = Number(data.cargo?.landPalletCount || 0);
                    const cartonCount = Number(data.cargo?.landCartonCount || cargoDetails?.land_carton_count || 0);

                    if (!landLines.length && totalTrucks <= 0 && palletCount <= 0 && cartonCount <= 0) {
                        return false;
                    }

                    const wrapper = document.createElement('div');
                    wrapper.className = 'space-y-3';

                    const stats = document.createElement('div');
                    stats.className = 'grid gap-2 sm:grid-cols-3';
                    stats.appendChild(createCargoStat('نوع الخدمة', 'شحن بري'));

                    if (totalTrucks > 0) {
                        stats.appendChild(createCargoStat('إجمالي الشاحنات', formatPlainNumber(totalTrucks)));
                    }

                    if (palletCount > 0) {
                        stats.appendChild(createCargoStat('عدد الطبليات', formatPlainNumber(palletCount)));
                    }

                    if (cartonCount > 0) {
                        stats.appendChild(createCargoStat('عدد الكراتين', formatPlainNumber(cartonCount)));
                    }

                    wrapper.appendChild(stats);

                    if (landLines.length) {
                        const routesTitle = document.createElement('div');
                        routesTitle.className = 'flex items-center gap-2 border-t border-border/60 pt-3 text-xs font-black text-foreground';

                        const routeIcon = document.createElement('span');
                        routeIcon.className = 'inline-flex h-6 w-6 items-center justify-center rounded-full bg-accent/10 text-accent';
                        routeIcon.textContent = '↔';

                        const routeText = document.createElement('span');
                        routeText.textContent = 'مسارات الشحن البري';
                        routesTitle.append(routeIcon, routeText);
                        wrapper.appendChild(routesTitle);

                        const routeList = document.createElement('div');
                        routeList.className = 'space-y-2';

                        landLines.forEach((line, index) => {
                            const row = document.createElement('div');
                            row.className = 'rounded-lg border border-border/50 bg-background/80 p-2';

                            const title = document.createElement('div');
                            title.className = 'mb-2 text-[11px] font-black text-accent';
                            title.textContent = `مسار ${index + 1}`;

                            const grid = document.createElement('div');
                            grid.className = 'grid gap-2 sm:grid-cols-[90px_minmax(0,1fr)_minmax(0,1fr)]';
                            grid.append(
                                createLandRouteCell('الشاحنات', line.qty > 0 ? formatPlainNumber(line.qty) : '—'),
                                createLandRouteCell('من', line.fromCity),
                                createLandRouteCell('إلى', line.toCity)
                            );

                            row.append(title, grid);
                            routeList.appendChild(row);
                        });

                        wrapper.appendChild(routeList);
                    }

                    cargoDetailsBlock.appendChild(wrapper);
                    return true;
                };

                cargoDetailsBlock.replaceChildren();
                const landDetailsRendered = appendLandCargoDetails();
                const rows = cargoDetails
                    ? Object.entries(cargoDetails)
                        .filter(([key, value]) => key !== 'containers' && !cargoDetailHiddenKeys.has(key) && !(landDetailsRendered && landDetailKeys.has(key)) && hasDetailValue(value))
                        .map(([key, value]) => {
                            const row = document.createElement('div');
                            row.className = 'mb-1 rounded border border-border/40 bg-background/70 p-2';
                            const labelElement = document.createElement('div');
                            labelElement.className = 'text-[11px] font-semibold text-muted-foreground';
                            labelElement.textContent = cargoDetailLabel(key);
                            const valueElement = document.createElement('div');
                            valueElement.className = 'mt-1 whitespace-pre-line break-words font-semibold text-foreground';
                            valueElement.textContent = cargoDetailValue(key, value);
                            row.append(labelElement, valueElement);
                            return row;
                        })
                    : [];

                if (rows.length) {
                    const grid = document.createElement('div');
                    grid.className = landDetailsRendered ? 'mt-3 grid gap-2 border-t border-border/60 pt-3 sm:grid-cols-2' : 'grid gap-2 sm:grid-cols-2';
                    rows.forEach(row => grid.appendChild(row));
                    cargoDetailsBlock.appendChild(grid);
                }

                if (!landDetailsRendered && !rows.length) {
                    cargoDetailsBlock.innerText = '—';
                }

                const attachmentsBlock = document.getElementById('det-attachments');
                if (data.cargo.attachments && data.cargo.attachments.length) {
                    attachmentsBlock.innerHTML = data.cargo.attachments.map(att => {
                        const fileName = String(att || '').split(/[\\/]/).pop();
                        return `<div class="mb-1"><a href="${escapeDetailHtml(bookingAttachmentUrl(data.id, att))}" target="_blank" rel="noopener" class="text-accent underline">${escapeDetailHtml(fileName)}</a></div>`;
                    }).join('');
                } else {
                    attachmentsBlock.innerText = '—';
                }

                const pricingBlock = document.getElementById('det-pricing-block');
                const repricingBlock = document.getElementById('det-repricing-block');
                if (repricingBlock) {
                    repricingBlock.classList.toggle('hidden', !data.repricingRequired);
                }
                if (data.pricing) {
                    if (repricingBlock) repricingBlock.classList.add('hidden');
                    pricingBlock.classList.remove('hidden');
                    const standardPricingLines = document.getElementById('p-standard-pricing-lines');
                    const landPricingLines = document.getElementById('p-land-pricing-lines');
                    const isLandPricing = data.pricing.isLand && Array.isArray(data.pricing.landPricingLines) && data.pricing.landPricingLines.length;
                    const pricingCurrency = data.pricing.currency || 'USD';
                    const pricingCurrencySymbol = data.pricing.currencySymbol || '$';
                    const moneyValue = (value) => `${pricingCurrencySymbol} ${Number(value || 0).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}`;

                    document.getElementById('p-pricing-heading').innerText = `تفصيل الأسعار (${pricingCurrency})`;

                    if (standardPricingLines) standardPricingLines.classList.toggle('hidden', isLandPricing);
                    if (landPricingLines) {
                        landPricingLines.classList.toggle('hidden', !isLandPricing);
                        landPricingLines.innerHTML = isLandPricing
                            ? data.pricing.landPricingLines.map((line) => {
                                const plate = line.plate_number ? `<span class="text-muted-foreground"> | اللوحة: ${escapeDetailHtml(line.plate_number)}</span>` : '';
                                const qty = parseFloat(line.qty || 0).toLocaleString();
                                const unit = parseFloat(line.unit_price || 0).toLocaleString();
                                const total = parseFloat(line.total || 0).toLocaleString();

                                return `<div class="rounded-lg border border-border/60 bg-secondary/20 p-2">
                                    <div class="font-bold">${escapeDetailHtml(line.from_city)} → ${escapeDetailHtml(line.to_city)}${plate}</div>
                                    <div class="mt-1 grid grid-cols-3 gap-2 text-[11px]">
                                        <span class="text-muted-foreground">العدد: <strong class="text-foreground">${qty}</strong></span>
                                        <span class="text-muted-foreground">سعر الوحدة: <strong class="text-foreground" dir="ltr">${moneyValue(line.unit_price)}</strong></span>
                                        <span class="text-muted-foreground">المجموع: <strong class="text-primary" dir="ltr">${moneyValue(line.total)}</strong></span>
                                    </div>
                                </div>`;
                            }).join('')
                            : '';
                    }

                    if (!isLandPricing) {
                        document.getElementById('p-ocean').innerText = moneyValue(data.pricing.oceanFreight);
                        document.getElementById('p-origin').innerText = moneyValue(data.pricing.originCharges);
                        document.getElementById('p-exw').innerText = moneyValue(data.pricing.exwCharges);
                        document.getElementById('p-exw-row').classList.toggle('hidden', !data.pricing.showExwCharges);
                        document.getElementById('p-dthc').innerText = moneyValue(data.pricing.dthc);
                        document.getElementById('p-war').innerText = moneyValue(data.pricing.warRiskSurcharge);
                        document.getElementById('p-other').innerText = moneyValue(data.pricing.otherCharges);
                        document.getElementById('p-destination-port-change').innerText = moneyValue(data.pricing.destinationPortChangeFee);
                        document.getElementById('p-other-penalties').innerText = moneyValue(data.pricing.otherPenalties);
                    }

                    document.getElementById('p-total').innerText = moneyValue(data.pricing.total);
                    const agentAccountingBlock = document.getElementById('p-agent-accounting-block');
                    const hiddenAccountingPages = ['/sales', '/bookings', '/orders'];
                    const shouldHideAgentAccounting = hiddenAccountingPages.some(path => window.location.pathname === path || window.location.pathname.startsWith(`${path}/`));
                    agentAccountingBlock.classList.toggle('hidden', shouldHideAgentAccounting);
                    document.getElementById('p-agent').innerText = data.pricing.agent?.name || '—';
                    document.getElementById('p-agent-qty').innerText = data.pricing.agentPurchaseQuantity ? parseFloat(data.pricing.agentPurchaseQuantity).toLocaleString() : '—';
                    document.getElementById('p-agent-cost').innerText = moneyValue(data.pricing.agentPurchaseAmount);
                    const profitElement = document.getElementById('p-profit');
                    const profitAmount = parseFloat(data.pricing.profitAmount || 0);
                    profitElement.innerText = moneyValue(profitAmount);
                    profitElement.classList.toggle('text-success', profitAmount >= 0);
                    profitElement.classList.toggle('text-destructive', profitAmount < 0);
                    document.getElementById('det-pdf-link').href = `/quote/${id}`;
                } else {
                    pricingBlock.classList.add('hidden');
                }

                const shippingBlock = document.getElementById('det-shipping-block');
                if (data.status === 'approved' || data.status === 'booked') {
                    shippingBlock.classList.remove('hidden');
                    document.getElementById('det-mbl-shipper').innerText = data.shipper || '—';
                    document.getElementById('det-mbl-consignee').innerText = data.consignee || '—';
                    document.getElementById('det-mbl-notify').innerText = data.notifyParty || '—';
                    document.getElementById('det-mbl-bill-type').innerText = data.billType || '—';
                    document.getElementById('det-mbl-move-type').innerText = data.typeOfMove || '—';
                    document.getElementById('det-mbl-job-no').innerText = data.jobNo || '—';
                    document.getElementById('det-mbl-agent').innerText = data.overseasAgent || '—';
                    document.getElementById('det-mbl-delivery').innerText = data.placeOfDelivery || '—';
                    document.getElementById('det-mbl-packages').innerText = data.quantityPackages || '—';
                    document.getElementById('det-mbl-meas-cbm').innerText = data.measurementCbm ? data.measurementCbm + ' CBM' : '—';
                    document.getElementById('det-mbl-seals').innerText = data.containerSealNo || '—';
                    document.getElementById('det-mbl-notes').innerText = data.customNotes || '—';
                    document.getElementById('det-mbl-link').href = `/quote/${id}/draft-mbl`;
                    const editMblButton = document.getElementById('det-mbl-edit');
                    if (editMblButton) {
                        const canEditCarrier = data.status === 'booked' && typeof window.editCarrierBooking === 'function';
                        editMblButton.classList.toggle('hidden', !canEditCarrier);
                        editMblButton.onclick = canEditCarrier ? (event) => {
                            event.stopPropagation();
                            closeDetailModal();
                            window.editCarrierBooking(data.id);
                        } : null;
                    }
                } else {
                    shippingBlock.classList.add('hidden');
                    document.getElementById('det-mbl-edit')?.classList.add('hidden');
                }

                if (window.lucide) window.lucide.createIcons();
                if (typeof window.__bookingDetailSet === 'function') window.__bookingDetailSet(data.id, data.status || data.state || null);
            });
    }

    function closeDetailModal() {
        const modal = document.getElementById('detail-modal');
        if (modal) modal.classList.add('hidden');
    }
</script>

<!-- Agent forward modal (used from booking detail) -->
<div id="agent-forward-modal" class="fixed inset-0 hidden items-center justify-center bg-black/40" style="display:none; z-index:99999;">
    <div class="w-full max-w-lg rounded-lg bg-card p-6" style="overflow:visible; position:relative;">
        <h3 class="text-lg font-bold mb-3">{{ __('messages.agent_forward_title') }}</h3>

        <div class="mb-4">
            <label class="text-sm font-bold">{{ __('messages.agent_type_label') }}</label>
            <div class="mt-2 flex flex-wrap gap-3">
                <button type="button" id="agent-type-local" class="px-4 py-2 rounded border">{{ __('messages.agent_type_local') }}</button>
                <button type="button" id="agent-type-external" class="px-4 py-2 rounded border">{{ __('messages.agent_type_external') }}</button>
            </div>
        </div>

        <div class="mb-4">
            <div class="text-sm font-bold">{{ __('messages.agent_agents_label') }}</div>
            <p id="agent-select-multiple-hint" class="mt-1 text-xs text-muted-foreground">{{ __('messages.agent_select_multiple_hint') }}</p>
            <div id="agents-list" aria-describedby="agent-select-multiple-hint" class="mt-2 space-y-2 max-h-48 overflow-auto"></div>
        </div>

        <div class="mb-4">
            <label for="agent-forward-message" class="text-sm font-bold">{{ __('messages.agent_message_optional') }}</label>
            <textarea id="agent-forward-message" rows="3" class="w-full p-2 border rounded"></textarea>
        </div>

        <p class="text-xs text-muted-foreground">{{ __('messages.agent_manual_send_hint') }}</p>
        <div id="agent-forward-results" role="status" class="mt-3 space-y-2 text-sm" hidden></div>

        <div class="flex justify-end gap-3 flex-wrap mt-4" style="z-index:100000; position:relative;">
            <button type="button" id="agent-forward-cancel"
                style="display:inline-flex !important;"
                class="h-9 items-center justify-center rounded-lg border border-border bg-card px-4 text-xs font-bold">
                {{ __('messages.agent_forward_cancel') }}
            </button>

            <button type="button" id="agent-forward-whatsapp" disabled
                style="display:inline-flex !important; background:#16a34a; color:white;"
                class="h-9 items-center justify-center rounded-lg px-4 text-xs font-bold">
                {{ __('messages.agent_forward_whatsapp') }}
            </button>

            <button type="button" id="agent-forward-email" disabled
                style="display:inline-flex !important; background:#2563eb; color:white;"
                class="h-9 items-center justify-center rounded-lg px-4 text-xs font-bold">
                {{ __('messages.agent_forward_email') }}
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        const openBtn = document.getElementById('open-agent-forward');
        const modal = document.getElementById('agent-forward-modal');
        const listEl = document.getElementById('agents-list');
        const btnLocal = document.getElementById('agent-type-local');
        const btnExternal = document.getElementById('agent-type-external');
        const btnCancel = document.getElementById('agent-forward-cancel');
        const btnWhats = document.getElementById('agent-forward-whatsapp');
        const btnEmail = document.getElementById('agent-forward-email');
        const messageEl = document.getElementById('agent-forward-message');
        const resultsEl = document.getElementById('agent-forward-results');
        const strings = {{ \Illuminate\Support\Js::from(\Illuminate\Support\Arr::only(__('messages'), ['agent_loading', 'agent_no_agents_for_type', 'agent_error_fetch_agents', 'agent_select_multiple_hint', 'agent_no_booking', 'agent_session_error', 'agent_send_failed', 'agent_whatsapp_opened', 'agent_whatsapp_partial', 'agent_email_prepared', 'agent_email_partial', 'agent_popup_links_hint'])) }};
        const t = (key, values = {}) => Object.entries(values).reduce((text, [name, value]) => text.replaceAll(`:${name}`, String(value)), strings[key] || key);

        const selectedAgents = new Map();
        let currentBookingId = null;
        let sending = false;
        let agentFetchVersion = 0;
        let modalVersion = 0;

        function syncButtons() {
            const disabled = sending || selectedAgents.size === 0;
            [btnWhats, btnEmail, document.getElementById('toolbar-agent-whatsapp'), document.getElementById('toolbar-agent-email')].forEach(button => {
                if (!button) return;
                button.disabled = disabled;
                button.classList.toggle('opacity-60', disabled);
                button.classList.toggle('pointer-events-none', disabled);
            });
            [btnLocal, btnExternal, btnCancel, document.getElementById('toolbar-agent-cancel'), messageEl].forEach(control => {
                if (control) control.disabled = sending;
            });
            listEl.querySelectorAll('input').forEach(input => { input.disabled = sending; });
        }

        function listMessage(key, error = false) {
            const message = document.createElement('div');
            message.className = error ? 'text-sm text-red-500' : 'text-sm text-muted-foreground';
            message.textContent = t(key);
            listEl.replaceChildren(message);
        }

        function fetchAgents(type) {
            if (sending) return;
            const version = ++agentFetchVersion;
            selectedAgents.clear();
            syncButtons();
            resultsEl.hidden = true;
            resultsEl.replaceChildren();
            listMessage('agent_loading');
            btnLocal?.setAttribute('aria-pressed', String(type === 'local'));
            btnExternal?.setAttribute('aria-pressed', String(type === 'external'));

            fetch(`/ajax/agents?type=${type}`, { headers: { Accept: 'application/json' } })
                .then(r => {
                    if (!r.ok) throw new Error('Agent list unavailable');
                    return r.json();
                })
                .then(data => {
                    if (version !== agentFetchVersion) return;
                    listEl.replaceChildren();
                    data.forEach(a => {
                        const item = document.createElement('label');
                        item.className = 'p-2 border rounded flex items-center justify-between cursor-pointer';
                        const details = document.createElement('div');
                        const name = document.createElement('div');
                        name.className = 'font-bold';
                        name.textContent = a.name;
                        const contact = document.createElement('div');
                        contact.className = 'text-xs';
                        contact.textContent = [a.email, a.phone].filter(Boolean).join(' - ');
                        details.append(name, contact);
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.name = 'agent_ids[]';
                        checkbox.value = a.id;
                        checkbox.addEventListener('change', () => {
                            if (checkbox.checked) selectedAgents.set(Number(a.id), a);
                            else selectedAgents.delete(Number(a.id));
                            syncButtons();
                        });
                        item.append(details, checkbox);
                        listEl.appendChild(item);
                    });

                    if (!data.length) listMessage('agent_no_agents_for_type');
                }).catch(() => {
                    if (version === agentFetchVersion) listMessage('agent_error_fetch_agents', true);
                });
        }

        function openModal() {
            if (sending) return;
            if (!currentBookingId) { showToast(t('agent_no_booking'), 'error'); return; }
                const version = ++modalVersion;
                messageEl.value = '';
                modal.classList.remove('hidden');
                // enforce display flex and top z-index in case page styles override
                modal.style.display = 'flex';
                modal.style.zIndex = '99999';

                // ensure buttons are visible even if Tailwind/CSS attempted to hide them
                if (btnWhats) {
                    btnWhats.style.display = 'inline-flex';
                    btnWhats.style.visibility = 'visible';
                }

                if (btnEmail) {
                    btnEmail.style.display = 'inline-flex';
                    btnEmail.style.visibility = 'visible';
                }

                if (btnCancel) {
                    btnCancel.style.display = 'inline-flex';
                    btnCancel.style.visibility = 'visible';
                }
                if (btnWhats) { btnWhats.disabled = true; btnWhats.classList.add('opacity-60','pointer-events-none'); }
                if (btnEmail) { btnEmail.disabled = true; btnEmail.classList.add('opacity-60','pointer-events-none'); }
                // Move modal to body to avoid ancestor overflow/z-index clipping
                try {
                    if (modal.parentNode !== document.body) {
                        modal._originalParent = modal.parentNode;
                        modal._originalNext = modal.nextSibling;
                        document.body.appendChild(modal);
                    }
                    // force fixed full-screen overlay so it sits above other content
                    modal.style.position = 'fixed';
                    modal.style.inset = '0';
                    modal.style.zIndex = '99999';
                    modal.style.overflow = 'auto';
                } catch (e) { console.warn('move modal failed', e); }

                // show fallback toolbar as well (in case modal buttons are hidden)
                const toolbar = document.getElementById('agent-forward-toolbar');
                if (toolbar) toolbar.style.display = 'block';
                // ensure modal buttons are visible (remove accidental inline hiding)
                if (btnWhats) { btnWhats.style.display = ''; btnWhats.style.visibility = ''; }
                if (btnEmail) { btnEmail.style.display = ''; btnEmail.style.visibility = ''; }
                // prefill message with booking details to send (language-aware)
                fetch(`/orders/${currentBookingId}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                }).then(r => r.json()).then(b => {
                    if (version !== modalVersion || sending || messageEl.value) return;
                    const lang = (document.documentElement.lang || 'en').toLowerCase();
                    const isAr = lang.startsWith('ar');
                    const template = [];
                    if (isAr) {
                        template.push(`طلب تسعير جديد - رقم: ${b.id}`);
                        template.push(`المسار: ${b.route.pol} → ${b.route.pod}`);
                        let cargo = formatBookingCargoLabel(b.cargo);
                        template.push(`نوع البضاعة: ${cargo}`);
                        template.push(`الوزن: ${b.cargo.weightKg || '-'} كجم`);
                        template.push(`الحجم: ${b.cargo.volumeCbm || '-'} CBM`);
                        template.push(`DG: ${b.cargo.dangerous ? 'نعم' : 'لا'}`);
                        template.push(`الوصف: ${b.cargo.goodsDescription || '-'}`);
                        if (b.cargo.notes) template.push(`ملاحظات: ${b.cargo.notes}`);
                    } else {
                        template.push(`New pricing request - Booking #: ${b.id}`);
                        template.push(`Route: ${b.route.pol} → ${b.route.pod}`);
                        let cargo = formatBookingCargoLabel(b.cargo);
                        template.push(`Cargo: ${cargo}`);
                        template.push(`Weight: ${b.cargo.weightKg || '-'} kg`);
                        template.push(`Volume: ${b.cargo.volumeCbm || '-'} CBM`);
                        template.push(`DG: ${b.cargo.dangerous ? 'Yes' : 'No'}`);
                        template.push(`Description: ${b.cargo.goodsDescription || '-'}`);
                        if (b.cargo.notes) template.push(`Notes: ${b.cargo.notes}`);
                    }
                    messageEl.value = template.join('\n');
                }).catch(() => {});
                fetchAgents('local');
        }

        function closeModal() {
            if (sending) return;
            modalVersion++;
            agentFetchVersion++;
            modal.classList.add('hidden');
            selectedAgents.clear();
            syncButtons();
            listEl.innerHTML = '';
            messageEl.value = '';
            const toolbar = document.getElementById('agent-forward-toolbar');
            if (toolbar) toolbar.style.display = 'none';
            // attempt to restore modal to original parent to keep DOM tidy
            try {
                if (modal._originalParent) {
                    if (modal._originalNext) modal._originalParent.insertBefore(modal, modal._originalNext);
                    else modal._originalParent.appendChild(modal);
                    delete modal._originalParent;
                    delete modal._originalNext;
                }
                // clear forced styles
                modal.style.position = '';
                modal.style.display = 'none';
                modal.style.inset = '';
                modal.style.zIndex = '';
                modal.style.overflow = '';
            } catch (e) { console.warn('restore modal failed', e); }
        }

        function send(method) {
            if (sending) return;
            if (selectedAgents.size === 0) {
                showToast(t('agent_select_multiple_hint'), 'error');
                return;
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!csrf) {
                showToast(t('agent_session_error'), 'error');
                return;
            }

            const agents = [...selectedAgents.values()];
            const payload = {
                booking_id: currentBookingId,
                agent_ids: agents.map(agent => Number(agent.id)),
                method: method,
                message: messageEl.value || ''
            };

            sending = true;
            syncButtons();
            resultsEl.hidden = true;
            resultsEl.replaceChildren();

            // Reserve every window during this click, before any asynchronous work.
            const waWindows = method === 'whatsapp' ? agents.map(() => {
                try {
                    const popup = window.open('about:blank', '_blank');
                    if (popup) popup.opener = null;
                    return popup;
                } catch (e) {
                    return null;
                }
            }) : [];
            const closeWindow = popup => { try { if (popup && !popup.closed) popup.close(); } catch {} };
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 30000);

            fetch(`/ajax/bookings/send-to-agent`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify(payload),
                signal: controller.signal,
            })
            .then(async r => {
                const text = await r.text();

                let resp;
                try {
                    resp = JSON.parse(text);
                } catch (e) {
                    throw new Error(t('agent_send_failed'));
                }

                if (!r.ok || resp.status !== 'ok') {
                    throw new Error(resp.message || t('agent_send_failed'));
                }

                return resp;
            })
            .then(resp => {
                let count = 0;
                let failed = 0;
                const blockedLinks = [];
                if (method === 'whatsapp') {
                    const urls = resp.whatsapp_urls || (resp.whatsapp_url ? [resp.whatsapp_url] : []);
                    const missing = new Set((resp.failed_agents || []).map(agent => Number(agent.id)));
                    let urlIndex = 0;
                    agents.forEach((agent, index) => {
                        const popup = waWindows[index];
                        if (missing.has(Number(agent.id))) {
                            closeWindow(popup);
                            failed++;
                            return;
                        }
                        const url = urls[urlIndex++];
                        if (!url || !/^https:\/\/wa\.me\/[0-9]+\?text=/.test(url)) {
                            closeWindow(popup);
                            failed++;
                            return;
                        }
                        try {
                            if (!popup || popup.closed) throw new Error('Popup blocked');
                            popup.location.href = url;
                            count++;
                        } catch {
                            closeWindow(popup);
                            failed++;
                            blockedLinks.push({ name: agent.name, url });
                        }
                    });
                } else {
                    if (!resp.mailto_url?.startsWith('mailto:')) throw new Error(t('agent_send_failed'));
                    window.location.href = resp.mailto_url;
                    count = resp.sent_count ?? 1;
                    failed = (resp.failed_agents || []).length;
                }

                sending = false;
                const key = method === 'whatsapp'
                    ? (failed ? 'agent_whatsapp_partial' : 'agent_whatsapp_opened')
                    : (failed ? 'agent_email_partial' : 'agent_email_prepared');
                const message = t(key, { count, failed });
                if (failed) {
                    const summary = document.createElement('p');
                    summary.textContent = message;
                    resultsEl.append(summary);
                    if (blockedLinks.length) {
                        const hint = document.createElement('p');
                        hint.textContent = t('agent_popup_links_hint');
                        resultsEl.append(hint);
                        blockedLinks.forEach(({ name, url }) => {
                            const link = document.createElement('a');
                            link.href = url;
                            link.target = '_blank';
                            link.rel = 'noopener noreferrer';
                            link.className = 'block text-accent underline';
                            link.textContent = name;
                            resultsEl.append(link);
                        });
                    }
                    resultsEl.hidden = false;
                } else {
                    closeModal();
                }
                showToast(message, count > 0 ? 'success' : 'error');
            })
            .catch(error => {
                waWindows.forEach(closeWindow);
                showToast(error.name === 'AbortError' ? t('agent_send_failed') : (error.message || t('agent_send_failed')), 'error');
            })
            .finally(() => {
                clearTimeout(timeout);
                sending = false;
                syncButtons();
            });
        }

        btnLocal?.addEventListener('click', () => fetchAgents('local'));
        btnExternal?.addEventListener('click', () => fetchAgents('external'));
        btnCancel?.addEventListener('click', () => {
            // simply close the modal; do not remove the booking from pricing queue
            closeModal();
        });
        btnWhats?.addEventListener('click', () => send('whatsapp'));
        btnEmail?.addEventListener('click', () => send('email'));

        // toolbar fallback wiring
        const tWhats = document.getElementById('toolbar-agent-whatsapp');
        const tEmail = document.getElementById('toolbar-agent-email');
        const tCancel = document.getElementById('toolbar-agent-cancel');
        tWhats?.addEventListener('click', () => send('whatsapp'));
        tEmail?.addEventListener('click', () => send('email'));
        tCancel?.addEventListener('click', closeModal);

        // expose a method for the detail modal to set current booking id and status
        window.__bookingDetailSet = function(id, status) {
            currentBookingId = id;
            if (!openBtn) return;

            const isSalesPage = window.location.pathname === '/sales' || window.location.pathname.startsWith('/sales/');
            if (isSalesPage) {
                openBtn.classList.add('hidden');
                return;
            }

            // show choose-agent button only when in pricing stage on non-sales pages
            if (status === 'pricing' || status === 'new') {
                openBtn.classList.remove('hidden');
            } else {
                openBtn.classList.add('hidden');
            }
        };

        // global opener to allow opening agent modal from other pages (e.g., pricing cards)
        window.openAgentForwardModal = function(id) {
            if (sending) return;
            currentBookingId = id;
            openModal();
        };

        syncButtons();
        openBtn?.addEventListener('click', openModal);
    })();
</script>
