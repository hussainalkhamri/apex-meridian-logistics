<?php

return [
    'name' => [
        'ar' => 'نظام إدارة الشحن والخدمات اللوجستية',
        'en' => 'Freight & Logistics Management System',
    ],
    'short_name' => [
        'ar' => 'إدارة الشحن واللوجستيات',
        'en' => 'Freight & Logistics Portal',
    ],
    'address' => [
        'ar' => 'مركز التجارة البحرية، حي المرفأ، جناح 400، البوابة الدولية',
        'en' => 'Maritime Trade Center, Port District, Suite 400, International Gateway',
    ],
    'phones' => [
        ['display' => '+1 (555) 019-2834', 'tel' => '+15550192834'],
        ['display' => '+44 20 7946 0912', 'tel' => '+442079460912'],
    ],
    'landline' => [
        'display' => '+1 (555) 019-2800',
        'tel' => '+15550192800',
    ],
    'whatsapp' => '15550192834',
    'email' => 'contact@example.com',
    'emails' => [
        'contact@example.com',
        'operations@example.com',
        'support@example.com',
    ],
    'website' => 'https://demo.logistics-freight.test',
    'tax_number' => env('COMPANY_TAX_NUMBER', 'US-88291047-X'),
    'logo' => 'images/brand/system-logo.svg',
    'letterhead_logo' => 'images/brand/system-letterhead.svg',
    'icon' => 'images/brand/system-icon.svg',
    'partners' => [
        ['name' => 'Global Maritime Alliance', 'logo' => 'images/partners/global-maritime.svg'],
        ['name' => 'International Freight Network', 'logo' => 'images/partners/freight-network.svg'],
        ['name' => 'Pacific Ocean Carrier Council', 'logo' => 'images/partners/carrier-council.svg'],
    ],
];
