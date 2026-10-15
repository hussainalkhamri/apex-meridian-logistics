<?php

namespace App\Support;

use DOMDocument;
use DOMElement;

class RasaqCustomsDeclarationXml
{
    public function build(array $data): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $root = $document->createElement('CustomsDeclaration');
        $root->setAttribute('format', 'rasaq-compatible');
        $root->setAttribute('generated_at', now()->toIso8601String());
        $document->appendChild($root);

        $header = $this->appendElement($document, $root, 'Header');
        foreach ([
            'DeclarationType' => $data['declaration_type'] ?? 'import',
            'FileName' => $data['file_name'] ?? '',
            'SerialNo' => $data['serial_no'] ?? '',
            'ShipmentNo' => $data['shipment_no'] ?? '',
            'ImporterCode' => $data['importer_code'] ?? '',
            'ImporterName' => $data['customer'] ?? '',
            'Customer' => $data['customer'] ?? '',
            'DeclarantCode' => $data['declarant_code'] ?? '',
            'DeclarantName' => $data['declarant_name'] ?? '',
            'CustomsOfficeCode' => $data['customs_office_code'] ?? '0309',
            'DeclarationNo' => $data['declaration_no'] ?? '',
            'BayanNo' => $data['bayan_no'] ?? '',
            'CustomsCenter' => $data['customs_center'] ?? '',
            'Country' => $data['country'] ?? 'اليمن',
            'Destination' => $data['destination'] ?? '',
            'GoodsType' => $data['goods_type'] ?? '',
            'Driver' => $data['driver'] ?? '',
            'Truck' => $data['truck'] ?? '',
            'ArrivalDate' => $data['arrival_date'] ?? '',
            'Note' => $data['note'] ?? '',
        ] as $key => $value) {
            $this->appendElement($document, $header, $key, (string) $value);
        }

        $items = $this->appendElement($document, $root, 'Items');
        $totals = [
            'ItemsCount' => 0,
            'Quantity' => 0.0,
            'Packages' => 0.0,
            'NetWeightKg' => 0.0,
            'ValueUsd' => 0.0,
        ];

        foreach (($data['items'] ?? []) as $index => $itemData) {
            $item = $this->appendElement($document, $items, 'Item');
            $item->setAttribute('number', (string) ($index + 1));

            foreach ([
                'Name' => $itemData['name'] ?? '',
                'Brand' => $itemData['brand'] ?? '',
                'Origin' => $itemData['origin'] ?? '',
                'Quantity' => $itemData['count'] ?? 0,
                'Type' => $itemData['type'] ?? '',
                'Packages' => $itemData['packages'] ?? 0,
                'PriceUnit' => $itemData['price_unit'] ?? '',
                'PackageWeightKg' => $itemData['package_weight_kg'] ?? 0,
                'NetWeightKg' => $itemData['net_weight_kg'] ?? 0,
                'ValueUsd' => $itemData['value_usd'] ?? 0,
                'Price' => $itemData['price'] ?? 0,
                'Reference' => $itemData['reference'] ?? '',
                'HsCode' => $itemData['hs'] ?? '',
            ] as $key => $value) {
                $this->appendElement($document, $item, $key, (string) $value);
            }

            $totals['ItemsCount']++;
            $totals['Quantity'] += (float) ($itemData['count'] ?? 0);
            $totals['Packages'] += (float) ($itemData['packages'] ?? 0);
            $totals['NetWeightKg'] += (float) ($itemData['net_weight_kg'] ?? 0);
            $totals['ValueUsd'] += (float) ($itemData['value_usd'] ?? 0);
        }

        $totalsNode = $this->appendElement($document, $root, 'Totals');
        foreach ($totals as $key => $value) {
            $this->appendElement($document, $totalsNode, $key, (string) round($value, 3));
        }

        return (string) $document->saveXML();
    }

    private function appendElement(DOMDocument $document, DOMElement $parent, string $name, ?string $value = null): DOMElement
    {
        $element = $document->createElement($name);

        if ($value !== null) {
            $element->appendChild($document->createTextNode($value));
        }

        $parent->appendChild($element);

        return $element;
    }
}
