<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agent;
use App\Models\Booking;
use Illuminate\Support\Facades\Validator;

class BookingAgentController extends Controller
{
    // Return agents filtered by type (local|external)
    public function agents(Request $request)
    {
        $type = $request->get('type');
        $query = Agent::query();
        if (in_array($type, ['local', 'external'], true)) {
            $query->where('type', $type);
        }

        $agents = $query->get(['id', 'name', 'email', 'phone', 'region', 'country']);
        return response()->json($agents);
    }

    // Send booking to agent via whatsapp link or email
    public function sendToAgent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|string',
            'agent_id' => 'required_without:agent_ids|integer|exists:agents,id',
            'agent_ids' => 'sometimes|required|array|min:1',
            'agent_ids.*' => 'required|integer|distinct|exists:agents,id',
            'method' => 'required|string|in:whatsapp,email',
            'message' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }
        $data = $validator->validated();

        $agentIds = $data['agent_ids'] ?? [$data['agent_id']];
        $agentsById = Agent::whereIn('id', $agentIds)->get()->keyBy('id');
        // SQL whereIn does not preserve the user's selection order.
        $agents = collect($agentIds)->map(fn ($id) => $agentsById->get($id));
        $booking = Booking::where('id', $data['booking_id'])->firstOrFail();

        $userMessage = trim($data['message'] ?? '');

        // decide language by app locale (Arabic if starts with 'ar')
        $locale = app()->getLocale() ?? 'en';
        $isArabic = strpos($locale, 'ar') === 0;

        // build parts for both languages
        $partsAr = [];
        $partsAr[] = "رقم الطلب: {$booking->id}";
        $partsAr[] = "العميل: " . ($booking->customer_company ?? ($booking->customer->company ?? '')) . (
            isset($booking->customer_contact_name) ? " — " . $booking->customer_contact_name : "");
        $partsAr[] = "هاتف: " . ($booking->customer_phone ?? ($booking->customer->phone ?? '-'));
        $partsAr[] = "بريد: " . ($booking->customer_email ?? ($booking->customer->email ?? '-'));
        $partsAr[] = "المسار: {$booking->pol} → {$booking->pod}";
        $cargoLine = $booking->shipmentDisplay();
        $partsAr[] = "نوع البضاعة: {$cargoLine}";
        $partsAr[] = "الوزن: " . ($booking->weight_kg ?? '-') . " كجم";
        $partsAr[] = "الحجم: " . ($booking->volume_cbm ?? '-') . " CBM";
        $partsAr[] = "DG: " . (!empty($booking->dangerous) ? 'نعم' : 'لا');
        $partsAr[] = "الوصف: " . ($booking->goods_description ?? '-');
        if (!empty($booking->notes)) $partsAr[] = "ملاحظات: " . $booking->notes;
        if (!empty($booking->documents)) $partsAr[] = "مستندات (وصف): " . $booking->documents;

        $partsEn = [];
        $partsEn[] = "Order number: {$booking->id}";
        $partsEn[] = "Client: " . ($booking->customer_company ?? ($booking->customer->company ?? '')) . (
            isset($booking->customer_contact_name) ? " — " . $booking->customer_contact_name : "");
        $partsEn[] = "Phone: " . ($booking->customer_phone ?? ($booking->customer->phone ?? '-'));
        $partsEn[] = "Email: " . ($booking->customer_email ?? ($booking->customer->email ?? '-'));
        $partsEn[] = "Route: {$booking->pol} → {$booking->pod}";
        $partsEn[] = "Cargo: {$cargoLine}";
        $partsEn[] = "Weight: " . ($booking->weight_kg ?? '-') . " kg";
        $partsEn[] = "Volume: " . ($booking->volume_cbm ?? '-') . " CBM";
        $partsEn[] = "DG: " . (!empty($booking->dangerous) ? 'Yes' : 'No');
        $partsEn[] = "Description: " . ($booking->goods_description ?? '-');
        if (!empty($booking->notes)) $partsEn[] = "Notes: " . $booking->notes;
        if (!empty($booking->documents)) $partsEn[] = "Documents (desc): " . $booking->documents;

        // attachments
        if (!empty($booking->attachments)) {
            try {
                $atts = is_array($booking->attachments) ? $booking->attachments : json_decode($booking->attachments, true);
            } catch (\Throwable $e) { $atts = null; }
            if (is_array($atts) && count($atts)) {
                $partsAr[] = 'مرفقات:';
                foreach ($atts as $a) $partsAr[] = ' - ' . basename($a);
                $partsEn[] = 'Attachments:';
                foreach ($atts as $a) $partsEn[] = ' - ' . basename($a);
            } elseif (is_string($booking->attachments) && $booking->attachments !== '') {
                $partsAr[] = 'مرفقات: ' . $booking->attachments;
                $partsEn[] = 'Attachments: ' . $booking->attachments;
            }
        }

        // route details
        if (!empty($booking->route_details) && is_array($booking->route_details)) {
            $rd = $booking->route_details;
            if (!empty($rd['mode'])) { $partsAr[] = 'شرط الشحن: ' . $rd['mode']; $partsEn[] = 'Incoterm: ' . $rd['mode']; }
            if (!empty($rd['origin']['country'])) { $partsAr[] = 'بلد الانطلاق: ' . $rd['origin']['country']; $partsEn[] = 'Origin country: ' . $rd['origin']['country']; }
            if (!empty($rd['origin']['city'])) { $partsAr[] = 'مدينة الانطلاق: ' . $rd['origin']['city']; $partsEn[] = 'Origin city: ' . $rd['origin']['city']; }
            if (!empty($rd['destination']['country'])) { $partsAr[] = 'بلد الوصول: ' . $rd['destination']['country']; $partsEn[] = 'Destination country: ' . $rd['destination']['country']; }
            if (!empty($rd['destination']['city'])) { $partsAr[] = 'مدينة الوصول: ' . $rd['destination']['city']; $partsEn[] = 'Destination city: ' . $rd['destination']['city']; }
        }

        if (!empty($booking->transit_days)) { $partsAr[] = 'مدة العبور (تقريبي): ' . $booking->transit_days . ' يوم'; $partsEn[] = 'Transit days (est): ' . $booking->transit_days . ' days'; }
        if (!empty($booking->validity_date)) { $partsAr[] = 'صلاحية العرض حتى: ' . $booking->validity_date->toDateString(); $partsEn[] = 'Validity until: ' . $booking->validity_date->toDateString(); }

        if ($booking->ocean_freight || $booking->origin_charges || $booking->shouldShowExwCharges() || $booking->dthc || $booking->war_risk_surcharge || $booking->other_charges || $booking->destination_port_change_fee || $booking->other_penalties) {
            $partsAr[] = 'تفاصيل التسعير:';
            $partsAr[] = ' - Ocean Freight: ' . ($booking->ocean_freight ?? 0);
            $partsAr[] = ' - Origin Charges: ' . ($booking->origin_charges ?? 0);
            if ($booking->shouldShowExwCharges()) {
                $partsAr[] = ' - أجور EXW: ' . ($booking->exw_charges ?? 0);
            }
            $partsAr[] = ' - DTHC: ' . ($booking->dthc ?? 0);
            $partsAr[] = ' - WRS/BAF: ' . ($booking->war_risk_surcharge ?? 0);
            $partsAr[] = ' - Other: ' . ($booking->other_charges ?? 0);
            $partsAr[] = ' - رسوم تغيير ميناء الوجهة: ' . ($booking->destination_port_change_fee ?? 0);
            $partsAr[] = ' - غرامات أخرى: ' . ($booking->other_penalties ?? 0);
            $partsAr[] = ' - Total: ' . $booking->pricingTotal();

            $partsEn[] = 'Pricing details:';
            $partsEn[] = ' - Ocean Freight: ' . ($booking->ocean_freight ?? 0);
            $partsEn[] = ' - Origin Charges: ' . ($booking->origin_charges ?? 0);
            if ($booking->shouldShowExwCharges()) {
                $partsEn[] = ' - EXW Charges: ' . ($booking->exw_charges ?? 0);
            }
            $partsEn[] = ' - DTHC: ' . ($booking->dthc ?? 0);
            $partsEn[] = ' - WRS/BAF: ' . ($booking->war_risk_surcharge ?? 0);
            $partsEn[] = ' - Other: ' . ($booking->other_charges ?? 0);
            $partsEn[] = ' - Destination port change fee: ' . ($booking->destination_port_change_fee ?? 0);
            $partsEn[] = ' - Other penalties: ' . ($booking->other_penalties ?? 0);
            $partsEn[] = ' - Total: ' . $booking->pricingTotal();
        }

        if (!empty($booking->carrier_vessel) || !empty($booking->carrier_voyage) || !empty($booking->carrier_booking_ref)) {
            $partsAr[] = 'معلومات الناقل:';
            $partsEn[] = 'Carrier info:';
            if (!empty($booking->carrier_vessel)) { $partsAr[] = ' - Vessel: ' . $booking->carrier_vessel; $partsEn[] = ' - Vessel: ' . $booking->carrier_vessel; }
            if (!empty($booking->carrier_voyage)) { $partsAr[] = ' - Voyage: ' . $booking->carrier_voyage; $partsEn[] = ' - Voyage: ' . $booking->carrier_voyage; }
            if (!empty($booking->carrier_booking_ref)) { $partsAr[] = ' - Carrier Ref: ' . $booking->carrier_booking_ref; $partsEn[] = ' - Carrier Ref: ' . $booking->carrier_booking_ref; }
            if (!empty($booking->carrier_etd)) { $partsAr[] = ' - ETD: ' . $booking->carrier_etd; $partsEn[] = ' - ETD: ' . $booking->carrier_etd; }
            if (!empty($booking->carrier_eta)) { $partsAr[] = ' - ETA: ' . $booking->carrier_eta; $partsEn[] = ' - ETA: ' . $booking->carrier_eta; }
        }

        $partsAr[] = "رابط الطلب: " . url("/orders/{$booking->id}");
        $partsEn[] = "Order link: " . url("/orders/{$booking->id}");

        // If the user already pasted or the frontend prefilled a full booking template,
        // avoid duplicating details. Detect by presence of booking id or 'رقم الطلب' or the order URL or English markers.
        $looksLikeFullDetails = false;
        if ($userMessage !== '') {
            if (stripos($userMessage, $booking->id) !== false) $looksLikeFullDetails = true;
            if (stripos($userMessage, 'رقم الطلب') !== false) $looksLikeFullDetails = true;
            if (stripos($userMessage, 'Order number') !== false) $looksLikeFullDetails = true;
            if (stripos($userMessage, url("/orders/{$booking->id}")) !== false) $looksLikeFullDetails = true;
        }

        if ($userMessage !== '' && $looksLikeFullDetails) {
            $message = $userMessage;
        } else {
            $prefixAr = $userMessage !== '' ? $userMessage . "\n\n" : "طلب تسعير جديد - Booking #{$booking->id}\n\n";
            $prefixEn = $userMessage !== '' ? $userMessage . "\n\n" : "New pricing request - Booking #{$booking->id}\n\n";
            $message = $isArabic ? $prefixAr . implode("\n", $partsAr) : $prefixEn . implode("\n", $partsEn);
        }

        $message = $this->sanitizeMessage($message);

        if ($data['method'] === 'whatsapp') {
            $urls = [];
            $failedAgents = [];
            $text = rawurlencode($message);
            foreach ($agents as $agent) {
                $phone = preg_replace('/[^0-9]/', '', (string) $agent->phone);
                if (! preg_match('/^[0-9]{7,15}$/', $phone)) {
                    $failedAgents[] = ['id' => $agent->id, 'name' => $agent->name, 'reason' => 'missing_phone'];
                    continue;
                }
                $urls[] = "https://wa.me/{$phone}?text={$text}";
            }

            $response = [
                'status' => $urls ? 'ok' : 'error',
                'whatsapp_urls' => $urls,
                'sent_count' => count($urls),
                'failed_agents' => $failedAgents,
            ];
            if (! $urls) {
                $response['message'] = __('messages.agent_no_valid_phones');
            } elseif (count($agentIds) === 1) {
                $response['whatsapp_url'] = $urls[0];
            }

            // These are conversation links, not messages sent by the server.
            // Forwarding must never move the request out of the pricing queue.
            return response()->json($response, $urls ? 200 : 422);
        }

        $emails = [];
        $failedAgents = [];
        foreach ($agents as $agent) {
            $email = trim((string) $agent->email);
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failedAgents[] = ['id' => $agent->id, 'name' => $agent->name, 'reason' => 'missing_email'];
                continue;
            }
            $emails[] = $email;
        }
        if (! $emails) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.agent_no_valid_emails'),
                'emails' => [],
                'sent_count' => 0,
                'failed_agents' => $failedAgents,
            ], 422);
        }

        $subject = $isArabic ? 'إحالة طلب تسعير' : 'Forwarded Booking Request';
        $mailtoUrl = count($agentIds) === 1
            ? 'mailto:' . rawurlencode($emails[0]) . '?'
            : 'mailto:?bcc=' . implode(',', array_map('rawurlencode', $emails)) . '&';
        $mailtoUrl .= 'subject=' . rawurlencode($subject)
            . '&body=' . rawurlencode($message);

        // Open through the user's mail app, matching the sales send-to-customer flow.
        return response()->json([
            'status' => 'ok',
            'email' => $emails[0],
            'emails' => $emails,
            'mailto_url' => $mailtoUrl,
            'sent_count' => count($emails),
            'failed_agents' => $failedAgents,
        ]);
    }

    private function sanitizeMessage($text)
    {
        if (empty($text)) {
            return $text;
        }

        // Mask emails
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL]', $text);

        // Mask phone numbers (matches 7+ digit sequences with potential country prefix/spaces/parentheses)
        $text = preg_replace('/(\+?[0-9][\s\-\(\)0-9]{6,}[0-9])/', '[PHONE]', $text);

        // Mask name fields
        $patterns = [
            '/(المسؤول:\s*)([^\n\r]+)/u',
            '/(المسؤول\s*—\s*)([^\n\r]+)/u',
            '/(الشخص المسؤول:\s*)([^\n\r]+)/u',
            '/(Contact Name:\s*)([^\n\r]+)/i',
            '/(Responsible:\s*)([^\n\r]+)/i',
            '/(العميل:\s*)([^\n\r]+)/u',
            '/(Client:\s*)([^\n\r]+)/i',
        ];

        foreach ($patterns as $pattern) {
            $text = preg_replace_callback($pattern, function ($matches) {
                return $matches[1] . '[CONFIDENTIAL]';
            }, $text);
        }

        return $text;
    }
}
