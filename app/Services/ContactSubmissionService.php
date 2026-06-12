<?php
declare (strict_types = 1);

namespace App\Services;

final class ContactSubmissionService
{
    private const FIELD_LABELS = [
        'name'                 => 'nom',
        'email'                => 'email',
        'phone'                => 'telephone',
        'people'               => 'nombre de personnes',
        'date'                 => 'date de l\'evenement',
        'location'             => 'lieu',
        'type'                 => 'type d\'evenement',
        'message'              => 'message',
        'customer_address'     => 'adresse de facturation',
        'customer_postal_code' => 'code postal',
        'customer_city'        => 'commune',
    ];

    /**
     * @return array{success:bool,status:int,error?:string,contactData?:array<string,mixed>,menuItems?:array<int,array<string,mixed>>}
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

        $requiredFields = [
            'name',
            'email',
            'phone',
            'people',
            'date',
            'location',
            'type',
            'message',
            'customer_address',
            'customer_postal_code',
            'customer_city',
        ];
        foreach ($requiredFields as $field) {
            $value = trim((string) ($post[$field] ?? ''));
            if ($value === '') {
                return [
                    'success' => false,
                    'status'  => 400,
                    'error'   => sprintf('Le champ %s est requis.', $this->fieldLabel($field)),
                ];
            }
        }

        $email = trim((string) ($post['email'] ?? ''));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'status'  => 400,
                'error'   => 'Email invalide',
            ];
        }

        $people = $this->nullablePositiveInt($post['people'] ?? null);
        if ($people === null) {
            return [
                'success' => false,
                'status'  => 400,
                'error'   => 'Nombre de personnes invalide',
            ];
        }

        $date = $this->nullableDate($post['date'] ?? null);
        if ($date === null) {
            return [
                'success' => false,
                'status'  => 400,
                'error'   => 'Date de l\'événement invalide',
            ];
        }

        $customerPostalCode = trim((string) ($post['customer_postal_code'] ?? ''));
        if (! preg_match('/^\d{5}$/', $customerPostalCode)) {
            return [
                'success' => false,
                'status'  => 400,
                'error'   => 'Code postal invalide',
            ];
        }

        $contactData = [
            'name'                 => trim((string) ($post['name'] ?? '')),
            'email'                => $email,
            'phone'                => trim((string) ($post['phone'] ?? '')),
            'people'               => $people,
            'date'                 => $date,
            'location'             => trim((string) ($post['location'] ?? '')),
            'customer_address'     => trim((string) ($post['customer_address'] ?? '')),
            'customer_postal_code' => $customerPostalCode,
            'customer_city'        => trim((string) ($post['customer_city'] ?? '')),
            'type'                 => trim((string) ($post['type'] ?? '')),
            'message'              => trim((string) ($post['message'] ?? '')),
        ];

        return [
            'success'     => true,
            'status'      => 200,
            'contactData' => $contactData,
            'menuItems'   => $this->parseMenuItems($post),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function parseMenuItems(array $post): array
    {
        if (! isset($post['menu_items']) || ! is_array($post['menu_items'])) {
            return [];
        }

        $menuItems      = [];
        $menuQuantities = is_array($post['menu_quantity'] ?? null) ? $post['menu_quantity'] : [];

        foreach ($post['menu_items'] as $itemData) {
            $parts = explode('|', (string) $itemData, 3);
            if (count($parts) !== 3) {
                continue;
            }

            $menuItems[] = [
                'category' => trim($parts[0]),
                'name'     => trim($parts[1]),
                'price'    => trim($parts[2]),
                'quantity' => $this->quantityForItem($menuQuantities, (string) $itemData),
            ];
        }

        return $menuItems;
    }

    /**
     * @param array<string,mixed> $menuQuantities
     */
    private function quantityForItem(array $menuQuantities, string $itemKey): int
    {
        if (! isset($menuQuantities[$itemKey])) {
            return 1;
        }

        $qty = (int) $menuQuantities[$itemKey];
        return $qty > 0 ? $qty : 1;
    }

    private function nullableTrim($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private function nullablePositiveInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = (int) $value;
        return $number > 0 ? $number : null;
    }

    private function nullableDate($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $value);
        if ($dt === false || $dt->format('Y-m-d') !== $value) {
            return null;
        }

        return $value;
    }

    private function fieldLabel(string $field): string
    {
        return self::FIELD_LABELS[$field] ?? $field;
    }
}
