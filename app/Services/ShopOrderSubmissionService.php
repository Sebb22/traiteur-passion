<?php
declare (strict_types = 1);

namespace App\Services;

final class ShopOrderSubmissionService
{
    private const FULFILLMENT_PICKUP   = 'pickup';
    private const FULFILLMENT_DELIVERY = 'delivery';

    /**
     * @return array{success:bool,status:int,error?:string,customerData?:array<string,mixed>,selections?:list<array{item_id:int,quantity:int,option_id:int|null,option_label:string|null,option_units:int|null}>}
     */
    public function parse(array $post, string $method): array
    {
        if (strtoupper($method) !== 'POST') {
            return [
                'success' => false,
                'status'  => 405,
                'error'   => 'Method not allowed',
            ];
        }

        $name  = trim((string) ($post['name'] ?? ''));
        $email = trim((string) ($post['email'] ?? ''));

        if ($name === '' || $email === '') {
            return [
                'success' => false,
                'status'  => 400,
                'error'   => 'Les champs nom et email sont requis.',
            ];
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'status'  => 400,
                'error'   => 'Email invalide',
            ];
        }

        $fulfillmentMethod = trim((string) ($post['fulfillment_method'] ?? self::FULFILLMENT_PICKUP));
        if (! in_array($fulfillmentMethod, [self::FULFILLMENT_PICKUP, self::FULFILLMENT_DELIVERY], true)) {
            return [
                'success' => false,
                'status'  => 400,
                'error'   => 'Mode de réception invalide.',
            ];
        }

        $pickupDate = trim((string) ($post['pickup_date'] ?? ''));
        if (! $this->isValidDate($pickupDate)) {
            return [
                'success' => false,
                'status'  => 400,
                'error'   => 'Date de retrait invalide.',
            ];
        }

        $today = date('Y-m-d');
        if ($pickupDate < $today) {
            return [
                'success' => false,
                'status'  => 400,
                'error'   => 'La date de retrait doit être aujourd’hui ou plus tard.',
            ];
        }

        $deliveryAddress    = $this->nullableTrim($post['delivery_address'] ?? null);
        $deliveryPostalCode = $this->nullableTrim($post['delivery_postal_code'] ?? null);
        $deliveryCity       = $this->nullableTrim($post['delivery_city'] ?? null);

        if ($fulfillmentMethod === self::FULFILLMENT_DELIVERY) {
            if ($deliveryAddress === null || $deliveryPostalCode === null || $deliveryCity === null) {
                return [
                    'success' => false,
                    'status'  => 400,
                    'error'   => 'Renseignez une adresse complète pour demander la livraison.',
                ];
            }

            if (! preg_match('/^\d{5}$/', $deliveryPostalCode)) {
                return [
                    'success' => false,
                    'status'  => 400,
                    'error'   => 'Code postal invalide pour la livraison.',
                ];
            }
        }

        $selections      = [];
        $raw             = is_array($post['shop_quantity'] ?? null) ? $post['shop_quantity'] : [];
        $rawItems        = is_array($post['shop_item'] ?? null) ? $post['shop_item'] : [];
        $rawOptions      = is_array($post['shop_option'] ?? null) ? $post['shop_option'] : [];
        $rawOptionLabels = is_array($post['shop_option_label'] ?? null) ? $post['shop_option_label'] : [];
        $rawOptionUnits  = is_array($post['shop_option_units'] ?? null) ? $post['shop_option_units'] : [];
        foreach ($raw as $lineKey => $quantity) {
            $itemId   = (int) ($rawItems[$lineKey] ?? 0);
            $quantity = (int) $quantity;
            if ($itemId > 0 && $quantity > 0) {
                $optionId     = (int) ($rawOptions[$lineKey] ?? 0);
                $optionLabel  = trim((string) ($rawOptionLabels[$lineKey] ?? ''));
                $optionUnits  = (int) ($rawOptionUnits[$lineKey] ?? 0);
                $selections[] = [
                    'item_id'      => $itemId,
                    'quantity'     => min(999, $quantity),
                    'option_id'    => $optionId > 0 ? $optionId : null,
                    'option_label' => $optionLabel !== '' ? $optionLabel : null,
                    'option_units' => $optionUnits > 0 ? $optionUnits : null,
                ];
            }
        }

        if ($selections === []) {
            return [
                'success' => false,
                'status'  => 400,
                'error'   => 'Ajoutez au moins un produit à votre commande.',
            ];
        }

        return [
            'success'      => true,
            'status'       => 200,
            'customerData' => [
                'name'                 => $name,
                'email'                => $email,
                'phone'                => $this->nullableTrim($post['phone'] ?? null),
                'fulfillment_method'   => $fulfillmentMethod,
                'pickup_date'          => $pickupDate,
                'pickup_slot'          => $this->nullableTrim($post['pickup_slot'] ?? null),
                'delivery_address'     => $deliveryAddress,
                'delivery_postal_code' => $deliveryPostalCode,
                'delivery_city'        => $deliveryCity,
                'message'              => $this->nullableTrim($post['message'] ?? null),
                'promo_code'           => $this->nullableTrim($post['promo_code'] ?? null),
            ],
            'selections'   => $selections,
        ];
    }

    private function nullableTrim($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private function isValidDate(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $value);
        return $dt !== false && $dt->format('Y-m-d') === $value;
    }
}
