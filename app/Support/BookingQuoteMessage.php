<?php

namespace App\Support;

use App\Models\Booking;
use Illuminate\Support\Carbon;

class BookingQuoteMessage
{
    public static function forBooking(Booking $booking, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale() ?: 'en';
        $isArabic = str_starts_with(strtolower($locale), 'ar');

        $customerName = trim((string) $booking->customer_company);
        $orderId = trim((string) ($booking->id ?? ''));
        $pol = trim((string) ($booking->pol ?? ''));
        $pod = trim((string) ($booking->pod ?? ''));
        $goodsDescription = self::goodsDescription($booking);
        $shippingLine = trim((string) (
            data_get($booking->cargo_details, 'shipping_line')
            ?: data_get($booking->route_details, 'shipping_line')
            ?: $booking->carrier_vessel
        ));
        $freeTime = trim((string) (
            data_get($booking->cargo_details, 'free_time')
            ?: data_get($booking->route_details, 'free_time')
        ));
        $tos = trim((string) (
            data_get($booking->cargo_details, 'tos')
            ?: data_get($booking->route_details, 'tos')
            ?: $booking->bill_type
        ));
        $transitDays = (int) (
            ($booking->transit_days ?? 0)
            ?: data_get($booking->route_details, 'transit_days', 0)
            ?: data_get($booking->cargo_details, 'transit_days', 0)
        );
        $validityDate = $booking->validity_date
            ? Carbon::parse($booking->validity_date)->format('d/m/Y')
            : null;
        $etd = $booking->carrier_etd
            ? Carbon::parse($booking->carrier_etd)->format('d/m/Y')
            : trim((string) (
                data_get($booking->cargo_details, 'etd')
                ?: data_get($booking->route_details, 'etd')
            ));
        $details = [];
        $addDetail = static function (string $label, string $value) use (&$details): void {
            if (trim($value) !== '') {
                $details[] = ['label' => $label, 'value' => $value];
            }
        };

        $addDetail($isArabic ? 'ميناء التحميل' : 'Port of Loading', $pol);
        $addDetail($isArabic ? 'مكان التفريغ' : 'Place of Discharge', $pod);

        $rate = (float) $booking->pricingTotal();
        if ($rate > 0) {
            $addDetail(
                $isArabic ? 'السعر' : 'Rate',
                $booking->pricingCurrencySymbol() . ' ' . number_format($rate, 2)
            );
        }

        if ($transitDays > 0) {
            $addDetail(
                $isArabic ? 'مدة النقل' : 'Transit Time',
                $transitDays . ($isArabic ? ' يوم' : ' days')
            );
        }

        $addDetail($isArabic ? 'الفترة المجانية' : 'Free Time', $freeTime);
        $addDetail($isArabic ? 'شركة الشحن' : 'Shipping Line', $shippingLine);
        $addDetail('ETD', $etd);
        $addDetail('TOS', $tos);
        $addDetail($isArabic ? 'اسم العميل' : 'Customer Name', $customerName);
        $addDetail($isArabic ? 'وصف البضاعة' : 'Goods Description', $goodsDescription);

        if (trim((string) $booking->shipment_type) !== '' || $booking->isDoorToDoorShipment() || $booking->isLandShipment()) {
            $addDetail($isArabic ? 'نوع الشحن' : 'Shipment Type', self::shipmentType($booking, $isArabic));
        }

        $details = [...$details, ...self::cargoQuantityDetails($booking, $isArabic)];

        $volumeCbm = self::volumeCbm($booking);
        if ($volumeCbm > 0 && ! self::quantityAlreadyUsesCbm($booking)) {
            $details[] = [
                'label' => $isArabic ? 'الحجم' : 'Volume',
                'value' => self::formatNumber($volumeCbm, 4) . ' CBM',
            ];
        }

        $weightKg = self::weightKg($booking);
        if ($weightKg > 0) {
            $details[] = [
                'label' => $isArabic ? 'الوزن الإجمالي' : 'Gross Weight',
                'value' => self::formatNumber($weightKg) . ($isArabic ? ' كجم' : ' KG'),
            ];
        }

        if ($validityDate !== null) {
            $details[] = [
                'label' => $isArabic ? 'صلاحية العرض' : 'Validity Date',
                'value' => $validityDate,
            ];
        }

        $numberedDetails = [];
        foreach ($details as $index => $detail) {
            $numberedDetails[] = ($index + 1) . '- ' . $detail['label'] . ': ' . $detail['value'];
        }

        $companyName = config('company.name.' . ($isArabic ? 'ar' : 'en'));
        $header = $isArabic
            ? "الطلب: {$orderId}\n{$companyName}\nعرض سعر شحن"
            : "Order: {$orderId}\n{$companyName}\nFreight Quotation";
        $notes = $isArabic
            ? "ملاحظة:\n• العرض خاضع لتوافر المساحة والحاويات في وقت الحجز.\n• السعر المقتبس يشمل رسوم مخاطر الحرب الحالية، وأي رسوم إضافية تفرضها شركة الشحن تكون على حساب العميل."
            : "Note:\n• Offer is subject to availability of space and containers at the time of booking.\n• Price quoted includes current War Risk fees; however, any additional War Risk Surcharges imposed by the shipping line shall be for the account of the customer.";

        return $header . "\n\n" . implode("\n", $numberedDetails) . "\n\n" . $notes;
    }

    private static function cargoQuantityDetails(Booking $booking, bool $isArabic): array
    {
        $details = [];
        $containerSummary = $booking->containerSummary();
        $containerQuantity = $booking->containerTotalQuantity();

        if ($booking->isFclShipment() || $containerSummary !== null) {
            if ($containerSummary !== null) {
                $details[] = [
                    'label' => $isArabic ? 'تفاصيل الحاويات' : 'Container Details',
                    'value' => $containerSummary,
                ];
            }
            if ($containerQuantity !== null && $containerQuantity > 0) {
                $details[] = [
                    'label' => $isArabic ? 'عدد الحاويات' : 'Container Quantity',
                    'value' => self::formatNumber($containerQuantity, 0) . ($isArabic ? ' حاوية' : ($containerQuantity === 1 ? ' container' : ' containers')),
                ];
            }

            return $details;
        }

        $packageSummary = self::packageSummary($booking);
        if ($packageSummary !== null) {
            $details[] = [
                'label' => $isArabic ? 'الطرود' : 'Packages',
                'value' => $packageSummary,
            ];
        }

        $basis = self::quantityBasis($booking);
        $quantity = self::quantity($booking, $basis);
        if ($quantity > 0) {
            $details[] = [
                'label' => $isArabic ? 'الكمية' : 'Quantity',
                'value' => self::formatNumber($quantity, 4) . ' ' . self::unitLabel($basis, $quantity, $isArabic),
            ];
        }

        return $details;
    }

    private static function goodsDescription(Booking $booking): string
    {
        $description = trim((string) (
            $booking->goods_description
            ?: data_get($booking->cargo_details, 'cargo.goods_description')
            ?: data_get($booking->cargo_details, 'cargo_name')
            ?: data_get($booking->cargo_details, 'vehicle_type')
        ));

        if ($description !== '') {
            return $description;
        }

        return collect($booking->cargoItems())
            ->pluck('name')
            ->filter()
            ->unique()
            ->implode('، ');
    }

    private static function shipmentType(Booking $booking, bool $isArabic): string
    {
        if ($booking->isDoorToDoorShipment()) {
            return $isArabic ? 'شحن من الباب إلى الباب' : 'Door-to-Door';
        }

        if ($booking->isLandShipment()) {
            return $isArabic ? 'شحن بري' : 'Land Freight';
        }

        $type = strtoupper(trim((string) $booking->shipment_type));

        return match ($type) {
            'FCL' => $isArabic ? 'حاوية كاملة (FCL)' : 'Full Container Load (FCL)',
            'LCL' => $isArabic ? 'شحن مجمع (LCL)' : 'Less than Container Load (LCL)',
            'AIR' => $isArabic ? 'شحن جوي' : 'Air Freight',
            'BULK' => $isArabic ? 'بضائع سائبة (Bulk)' : 'Bulk Cargo',
            'RORO', 'RO-RO' => $isArabic ? 'شحن مركبات (RoRo)' : 'RoRo Vehicle Shipping',
            default => trim((string) $booking->shipment_type) ?: ($isArabic ? 'غير محدد' : 'Not specified'),
        };
    }

    private static function quantityBasis(Booking $booking): string
    {
        $shipmentType = strtoupper(trim((string) $booking->shipment_type));
        $volumeCbm = self::volumeCbm($booking);

        if ($booking->itemPricingLines() !== []) {
            return $booking->pricingBasis();
        }

        if ($booking->isLandShipment()) {
            return 'ton';
        }

        if ($booking->isDoorToDoorShipment()) {
            return (string) $booking->doorToDoorPricing()['basis'];
        }

        if (in_array($shipmentType, ['LCL', 'AIR'], true) && $volumeCbm > 0) {
            return 'cbm';
        }

        if ($shipmentType === 'BULK' && self::weightKg($booking) > 0) {
            return 'ton';
        }

        $packageCount = self::packageCount($booking);
        if ($packageCount > 0) {
            return 'piece';
        }

        return $booking->pricingBasis();
    }

    private static function quantity(Booking $booking, string $basis): float
    {
        if ($basis === 'cbm') {
            return self::volumeCbm($booking);
        }

        if ($basis === 'ton' && strtoupper((string) $booking->shipment_type) === 'BULK') {
            return self::weightKg($booking) / 1000;
        }

        $packageCount = self::packageCount($booking);
        if ($basis === 'piece' && $packageCount > 0) {
            return $packageCount;
        }

        return $booking->pricingQuantity();
    }

    private static function quantityAlreadyUsesCbm(Booking $booking): bool
    {
        if ($booking->isFclShipment() || $booking->containerSummary() !== null) {
            return false;
        }

        return self::quantityBasis($booking) === 'cbm';
    }

    private static function packageSummary(Booking $booking): ?string
    {
        $packages = data_get($booking->cargo_details, 'packages', []);
        if (! is_array($packages)) {
            return null;
        }

        $summary = collect($packages)
            ->map(function ($package) {
                $quantity = (int) data_get($package, 'quantity', 0);
                $type = trim((string) data_get($package, 'package_type', ''));

                return $quantity > 0 ? $quantity . 'x ' . ($type !== '' ? $type : 'package') : null;
            })
            ->filter()
            ->implode(' + ');

        return $summary !== '' ? $summary : null;
    }

    private static function packageCount(Booking $booking): float
    {
        $packages = data_get($booking->cargo_details, 'packages', []);
        if (is_array($packages) && $packages !== []) {
            $count = collect($packages)->sum(fn ($package) => (float) data_get($package, 'quantity', 0));
            if ($count > 0) {
                return $count;
            }
        }

        return (float) (
            data_get($booking->cargo_details, 'cargo.pieces_count')
            ?: data_get($booking->cargo_details, 'pieces_count')
            ?: data_get($booking->cargo_details, 'packages_count')
            ?: data_get($booking->cargo_details, 'pieces')
            ?: (is_numeric($booking->quantity_packages) ? $booking->quantity_packages : 0)
        );
    }

    private static function volumeCbm(Booking $booking): float
    {
        return max(0, (float) (
            $booking->volume_cbm
            ?: data_get($booking->cargo_details, 'cargo.volume_cbm')
            ?: data_get($booking->cargo_details, 'volume_cbm')
            ?: $booking->measurement_cbm
            ?: 0
        ));
    }

    private static function weightKg(Booking $booking): float
    {
        return max(0, (float) (
            $booking->weight_kg
            ?: data_get($booking->cargo_details, 'cargo.gross_weight_kg')
            ?: data_get($booking->cargo_details, 'gross_weight_kg')
            ?: 0
        ));
    }

    private static function unitLabel(string $unit, float $quantity, bool $isArabic): string
    {
        if ($isArabic) {
            return match ($unit) {
                'vehicle' => 'مركبة',
                'cbm' => 'CBM',
                'kg' => 'كجم',
                'ton' => 'طن',
                'container' => 'حاوية',
                'service' => 'خدمة',
                'mixed' => 'وحدة متنوعة',
                default => 'قطعة',
            };
        }

        return match ($unit) {
            'vehicle' => $quantity === 1.0 ? 'vehicle' : 'vehicles',
            'cbm' => 'CBM',
            'kg' => 'KG',
            'ton' => $quantity === 1.0 ? 'ton' : 'tons',
            'container' => $quantity === 1.0 ? 'container' : 'containers',
            'service' => $quantity === 1.0 ? 'service' : 'services',
            'mixed' => 'mixed units',
            default => $quantity === 1.0 ? 'piece' : 'pieces',
        };
    }

    private static function formatNumber(float|int $value, int $decimals = 2): string
    {
        $formatted = number_format((float) $value, $decimals, '.', ',');

        return $decimals > 0 ? rtrim(rtrim($formatted, '0'), '.') : $formatted;
    }
}
