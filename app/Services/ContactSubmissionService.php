<?php
declare (strict_types = 1);

namespace App\Services;

final class ContactSubmissionService
{
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

        $requiredFields = ['name', 'email', 'message'];
        foreach ($requiredFields as $field) {
            $value = trim((string) ($post[$field] ?? ''));
            if ($value === '') {
                return [
                    'success' => false,
                    'status'  => 400,
                    'error'   => "Le champ {$field} est requis",
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

        $contactData = [
            'name'     => trim((string) ($post['name'] ?? '')),
            'email'    => $email,
            'phone'    => $this->nullableTrim($post['phone'] ?? null),
            'people'   => $this->nullablePositiveInt($post['people'] ?? null),
            'date'     => $this->nullableDate($post['date'] ?? null),
            'location' => $this->nullableTrim($post['location'] ?? null),
            'type'     => $this->nullableTrim($post['type'] ?? null),
            'message'  => trim((string) ($post['message'] ?? '')),
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
}
