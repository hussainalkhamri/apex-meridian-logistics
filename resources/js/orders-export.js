const ORDERS_EXPORT_COLUMN_KEYS = [
  'رقم الطلب',
  'الشركة',
  'الشخص المسؤول',
  'الهاتف',
  'البريد',
  'نوع الشحن',
  'نوع الحاوية',
  'عدد الحاويات',
  'وصف البضاعة',
  'POL',
  'POD',
  'الوزن كجم',
  'الحجم CBM',
  'بضاعة خطرة',
  'الملاحظات',
  'المستندات',
  'الحالة',
  'حالة الطلب',
  'الشحن البحري',
  'رسوم المنشأ',
  'أجور EXW',
  'DTHC',
  'مخاطر الحرب',
  'رسوم أخرى',
  'رسوم تغيير ميناء الوجهة',
  'غرامات أخرى',
  'رسوم التخليص',
  'الإجمالي',
  'تاريخ الإنشاء',
  'ملاحظات التسعير',
  'الشاحن',
  'المستلم',
  'الطرف المبلّغ',
  'نوع بوليصة الشحن',
  'نوع الحركة',
  'رقم الوظيفة',
  'الوكيل الخارجي',
  'مكان التسليم',
  'عدد الطرود',
  'القياس CBM',
  'رقم ختم الحاوية',
  'ملاحظات الشحن',
  'السفينة',
  'الرحلة',
  'مرجع حجز الناقل',
  'ETD',
  'ETA',
  'تم الحجز بواسطة',
  'تاريخ حجز الناقل',
  'رقم الطلب الرئيسي',
  'تفاصيل المسار JSON',
  'تفاصيل الحمولة JSON',
  'المرفقات JSON',
  'ملاحظات المبيعات',
  'قرار العميل',
  'ملاحظة قرار العميل',
  'تاريخ قرار العميل',
  'تاريخ القرار',
  'أيام العبور',
  'صلاحية السعر',
  'تم التسعير بواسطة',
  'تاريخ التسعير',
  'الوكيل',
  'حالة الفاتورة',
  'قيمة الفاتورة',
  'تاريخ إصدار الفاتورة',
  'تاريخ استحقاق الفاتورة',
  'حالة التتبع',
  'نسبة التتبع',
  'تسعير النقل البري JSON',
  'كمية التسعير البري',
  'معرف الفاتورة',
  'معرف التتبع',
];

const ORDERS_EXPORT_STORAGE_KEY = 'shipmate.orders.selectedExportColumns';

function readPersistedColumns() {
  try {
    const saved = localStorage.getItem(ORDERS_EXPORT_STORAGE_KEY);
    if (!saved) {
      return ORDERS_EXPORT_COLUMN_KEYS.slice();
    }

    const parsed = JSON.parse(saved);
    if (!Array.isArray(parsed)) {
      return ORDERS_EXPORT_COLUMN_KEYS.slice();
    }

    return parsed.filter((column) => ORDERS_EXPORT_COLUMN_KEYS.includes(column));
  } catch (error) {
    return ORDERS_EXPORT_COLUMN_KEYS.slice();
  }
}

function persistSelectedColumns(columns) {
  try {
    localStorage.setItem(ORDERS_EXPORT_STORAGE_KEY, JSON.stringify(columns));
  } catch (error) {
    // Ignore storage restrictions.
  }
}

function buildExportUrl(format, selectedColumns) {
  const url = new URL(window.location.origin + (window.location.pathname || '/orders'));
  const params = new URLSearchParams(window.location.search);

  params.forEach((value, key) => {
    if (key === 'export' || key === 'columns') {
      params.delete(key);
    }
  });

  const filterForm = document.getElementById('orders-filter-form');
  if (filterForm) {
    new FormData(filterForm).forEach((value, key) => {
      const normalizedValue = String(value || '').trim();
      if (normalizedValue !== '') {
        params.set(key, normalizedValue);
      } else {
        params.delete(key);
      }
    });
  }

  params.set('export', format);
  if (selectedColumns.length > 0) {
    params.set('columns', selectedColumns.join(','));
  }

  return `${url.origin}${url.pathname}?${params.toString()}`;
}

function saveSelectedColumnsFromModal() {
  const modal = document.querySelector('[data-orders-export-modal]');
  const columnContainer = document.querySelector('[data-orders-export-columns]');

  if (!modal || !columnContainer) {
    return;
  }

  const allColumns = ORDERS_EXPORT_COLUMN_KEYS.slice();
  const currentSelection = Array.from(columnContainer.querySelectorAll('input[type="checkbox"]'))
    .filter((checkbox) => checkbox.checked)
    .map((checkbox) => checkbox.value)
    .filter((column) => ORDERS_EXPORT_COLUMN_KEYS.includes(column));

  const finalSelection = currentSelection.length > 0 ? currentSelection : allColumns;
  persistSelectedColumns(finalSelection);
  closeOrdersExportModal();
}

function exportSelectedColumns(format) {
  const selectedColumns = readPersistedColumns();
  const columns = selectedColumns.length > 0 ? selectedColumns : ORDERS_EXPORT_COLUMN_KEYS.slice();
  window.location.href = buildExportUrl(format, columns);
}

function openOrdersExportModal() {
  const modal = document.querySelector('[data-orders-export-modal]');
  const columnContainer = document.querySelector('[data-orders-export-columns]');

  if (!modal || !columnContainer) {
    return;
  }

  const allColumns = ORDERS_EXPORT_COLUMN_KEYS.slice();
  const savedColumns = readPersistedColumns();
  const initialSelection = savedColumns.length > 0 ? savedColumns : allColumns;
  const selectedSet = new Set(initialSelection);

  columnContainer.innerHTML = '';
  allColumns.forEach((column) => {
    const row = document.createElement('label');
    row.className = 'flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground hover:bg-secondary/50';

    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.checked = selectedSet.has(column);
    checkbox.value = column;
    checkbox.className = 'h-4 w-4 accent-accent';

    const text = document.createElement('span');
    text.textContent = column;
    text.className = 'flex-1 text-right';

    const wrapper = document.createElement('div');
    wrapper.className = 'flex w-full items-center justify-between gap-3';
    wrapper.appendChild(text);
    wrapper.appendChild(checkbox);
    row.appendChild(wrapper);
    columnContainer.appendChild(row);
  });

  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function closeOrdersExportModal() {
  const modal = document.querySelector('[data-orders-export-modal]');
  if (modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }
}

function bindOrdersExportButtons() {
  document.addEventListener('click', (event) => {
    const exportButton = event.target.closest('[data-orders-export-button]');
    if (exportButton) {
      event.preventDefault();
      exportSelectedColumns(exportButton.dataset.ordersExportButton);
      return;
    }

    const customizeButton = event.target.closest('[data-orders-customize-button]');
    if (customizeButton) {
      event.preventDefault();
      openOrdersExportModal();
      return;
    }

    const closeButton = event.target.closest('[data-orders-export-close]');
    if (closeButton) {
      event.preventDefault();
      closeOrdersExportModal();
      return;
    }

    const confirmButton = event.target.closest('[data-orders-export-confirm]');
    if (confirmButton) {
      event.preventDefault();
      saveSelectedColumnsFromModal();
      return;
    }

    const modal = document.querySelector('[data-orders-export-modal]');
    if (modal && event.target === modal) {
      closeOrdersExportModal();
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bindOrdersExportButtons, { once: true });
} else {
  bindOrdersExportButtons();
}

window.openOrdersExportModal = openOrdersExportModal;
window.closeOrdersExportModal = closeOrdersExportModal;
window.readPersistedColumns = readPersistedColumns;
window.ORDERS_EXPORT_COLUMN_KEYS = ORDERS_EXPORT_COLUMN_KEYS;
