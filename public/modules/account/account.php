<?php
// Demo customer + order history (ported from the original UI's mock data).

function demo_customer(): array
{
    return [
        'firstName' => 'Amani', 'lastName' => 'Kariuki', 'email' => 'amani.kariuki@example.com',
        'phone' => '+254 700 123 456', 'memberSince' => '2024-03-14T09:00:00Z',
        'addresses' => [
            ['label' => 'Home', 'recipient' => 'Amani Kariuki', 'line1' => '14 Mwanzi Road', 'line2' => 'Apt 3B',
             'city' => 'Nairobi', 'region' => 'Nairobi County', 'postalCode' => '00100', 'country' => 'Kenya', 'isDefault' => true],
            ['label' => 'Office', 'recipient' => 'Amani Kariuki', 'line1' => 'Westlands Business Park', 'line2' => 'Floor 5',
             'city' => 'Nairobi', 'region' => 'Nairobi County', 'postalCode' => '00800', 'country' => 'Kenya', 'isDefault' => false],
        ],
    ];
}

function demo_orders(): array
{
    return [
        ['orderNumber' => 'BRIO-5003', 'createdAt' => '2026-10-28T10:12:00Z', 'status' => 'processing', 'total' => 4850,
         'items' => [['name' => 'Vitamin C & E Serum', 'quantity' => 1], ['name' => 'Cucumber & Aloe Vera Face Cream SPF 30', 'quantity' => 1]]],
        ['orderNumber' => 'BRIO-4921', 'createdAt' => '2026-09-17T15:40:00Z', 'status' => 'completed', 'total' => 3750,
         'items' => [['name' => 'Organic Shower Gel, Cucumber & Aloe Vera', 'quantity' => 2], ['name' => 'Papaya Extract Body & Hands Lotion', 'quantity' => 1]]],
        ['orderNumber' => 'BRIO-4807', 'createdAt' => '2026-08-02T08:05:00Z', 'status' => 'cancelled', 'total' => 2200,
         'items' => [['name' => 'Cucumber & Aloe Vera Face Cream SPF 30', 'quantity' => 1]]],
        ['orderNumber' => 'BRIO-4650', 'createdAt' => '2026-06-21T12:30:00Z', 'status' => 'completed', 'total' => 5550,
         'items' => [['name' => 'Vitamin C & E Serum', 'quantity' => 1], ['name' => 'Papaya Extract Body & Hands Lotion', 'quantity' => 2]]],
    ];
}
