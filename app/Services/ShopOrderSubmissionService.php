<?php
declare (strict_types = 1);

namespace App\Services;

final class ShopOrderSubmissionService
{
    private const FULFILLMENT_PICKUP   = 'pickup';
    private const FULFILLMENT_DELIVERY = 'delivery';
    private const PICKUP_SLOT_INTERVAL_MINUTES = 30;
    private const PICKUP_LEAD_TIME_MINUTES     = 120;
    private const PICKUP_WEEKDAY_START_MINUTES = 8 * 60 + 30;
    private const PICKUP_WEEKDAY_END_MINUTES   = 19 * 60;
    private const PICKUP_SATURDAY_END_MINUTES  = 15 * 60 + 30;

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

        $fulfillmentMethod = trim((string) ($post['fulfillment_method'] ?? ''));
        if (! in_array($fulfillmentMethod, [self::FULFILLMENT_PICKUP, self::FULFILLMENT_DELIVERY], true)) {
            return [
                'success' => false,
                'status'  => 400,
                'error'   => 'Choisissez Retrait ou Livraison avant de continuer.',
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

        $now   = new \DateTimeImmutable('now');
        $today = $now->format('Y-m-d');
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
        $pickupSlot         = $this->nullableTrim($post['pickup_slot'] ?? null);

        if ($fulfillmentMethod === self::FULFILLMENT_PICKUP) {
            if ($this->isPickupClosedDay($pickupDate)) {
                return [
                    'success' => false,
                    'status'  => 400,
                    'error'   => 'Le retrait boutique est disponible du mardi au vendredi de 8h30 à 19h et le samedi de 8h30 à 15h30. Aucun retrait n’est proposé le dimanche et le lundi.',
                ];
            }

            if ($pickupSlot === null) {
                return [
                    'success' => false,
                    'status'  => 400,
                    'error'   => 'Choisissez un créneau de retrait.',
                ];
            }

            $allowedSlots = $this->allowedPickupSlotsForDate($pickupDate, $now);
            if ($allowedSlots === []) {
                return [
                    'success' => false,
                    'status'  => 400,
                    'error'   => 'Aucun créneau de retrait n’est encore disponible pour cette date. Le retrait boutique est possible à partir de 2h après validation de la commande.',
                ];
            }

            if (! in_array($pickupSlot, $allowedSlots, true)) {
                return [
                    'success' => false,
                    'status'  => 400,
                    'error'   => 'Choisissez un créneau de retrait proposé pour cette date. Le retrait boutique est possible à partir de 2h après validation de la commande.',
                ];
            }
        }

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
                'pickup_slot'          => $pickupSlot,
                'delivery_address'     => $deliveryAddress,
                'delivery_postal_code' => $deliveryPostalCode,
                'delivery_city'        => $deliveryCity,
                'message'              => $this->nullableTrim($post['message'] ?? null),
                'promo_code'           => $this->nullableTrim($post['promo_code'] ?? null),
            ],
            'selections'   => $selections,
        ];
    }

    public function firstAvailablePickupDate(): string
    {
        $reference = new \DateTimeImmutable('now');

        for ($offset = 0; $offset < 14; $offset++) {
            $date = $reference->modify(sprintf('+%d day', $offset))->format('Y-m-d');
            if ($this->allowedPickupSlotsForDate($date, $reference) !== []) {
                return $date;
            }
        }

        return $reference->format('Y-m-d');
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

    private function isPickupClosedDay(string $date): bool
    {
        $dayOfWeek = (int) date('N', strtotime($date));
        return $dayOfWeek === 1 || $dayOfWeek === 7;
    }

    /**
     * @return list<string>
     */
    private function allowedPickupSlotsForDate(string $date, ?\DateTimeImmutable $reference = null): array
    {
        if ($this->isPickupClosedDay($date)) {
            return [];
        }

        [$startMinutes, $endMinutes] = $this->pickupWindowForDate($date);

        $reference ??= new \DateTimeImmutable('now');
        if ($date === $reference->format('Y-m-d')) {
            $minimumStart = $this->roundUpMinutes(
                $this->minutesFromDateTime($reference) + self::PICKUP_LEAD_TIME_MINUTES,
                self::PICKUP_SLOT_INTERVAL_MINUTES,
            );
            $startMinutes = max($startMinutes, $minimumStart);
        }

        return $this->buildPickupSlots($startMinutes, $endMinutes);
    }

    /**
     * @return array{0:int,1:int}
     */
    private function pickupWindowForDate(string $date): array
    {
        $dayOfWeek = (int) date('N', strtotime($date));
        if ($dayOfWeek === 6) {
            return [self::PICKUP_WEEKDAY_START_MINUTES, self::PICKUP_SATURDAY_END_MINUTES];
        }

        return [self::PICKUP_WEEKDAY_START_MINUTES, self::PICKUP_WEEKDAY_END_MINUTES];
    }

    /**
     * @return list<string>
     */
    private function buildPickupSlots(int $startMinutes, int $endMinutes): array
    {
        $slots = [];

        for ($current = $startMinutes; $current + self::PICKUP_SLOT_INTERVAL_MINUTES <= $endMinutes; $current += self::PICKUP_SLOT_INTERVAL_MINUTES) {
            $slots[] = sprintf('%s - %s', $this->formatMinutes($current), $this->formatMinutes($current + 30));
        }

        return $slots;
    }

    private function minutesFromDateTime(\DateTimeImmutable $dateTime): int
    {
        return ((int) $dateTime->format('G') * 60) + (int) $dateTime->format('i');
    }

    private function roundUpMinutes(int $minutes, int $step): int
    {
        if ($step <= 0) {
            return $minutes;
        }

        return (int) (ceil($minutes / $step) * $step);
    }

    private function formatMinutes(int $minutes): string
    {
        $hours = (int) floor($minutes / 60);
        $mins  = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }
}
