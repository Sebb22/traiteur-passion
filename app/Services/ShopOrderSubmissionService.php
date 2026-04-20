<?php
declare (strict_types = 1);

namespace App\Services;

final class ShopOrderSubmissionService
{
    /**
     * @return array{success:bool,status:int,error?:string,customerData?:array<string,mixed>,quantities?:array<int,int>}
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

        $quantities = [];
        $raw        = is_array($post['shop_quantity'] ?? null) ? $post['shop_quantity'] : [];
        foreach ($raw as $itemId => $quantity) {
            $itemId   = (int) $itemId;
            $quantity = (int) $quantity;
            if ($itemId > 0 && $quantity > 0) {
                $quantities[$itemId] = min(999, $quantity);
            }
        }

        if ($quantities === []) {
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
                'name'        => $name,
                'email'       => $email,
                'phone'       => $this->nullableTrim($post['phone'] ?? null),
                'pickup_date' => $pickupDate,
                'pickup_slot' => $this->nullableTrim($post['pickup_slot'] ?? null),
                'message'     => $this->nullableTrim($post['message'] ?? null),
            ],
            'quantities'   => $quantities,
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
