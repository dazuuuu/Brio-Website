<?php
declare(strict_types=1);

namespace Brio\Domain\Order;

use Brio\Domain\Exceptions\ValidationException;
use Brio\Domain\Product\Product;

/** A customer order. Prices come from the catalogue, never from the browser. */
final class Order
{
    public const STATUSES = ['new', 'contacted', 'completed', 'cancelled'];
    public const CHANNELS = ['form', 'whatsapp'];

    /** @param array<int,array{productId:int,name:string,price:float,quantity:int,image?:string,description?:string}> $items */
    public function __construct(
        public ?int $id,
        public ?string $orderNumber,
        public string $name,
        public string $phone,
        public string $whatsapp,
        public ?string $address,
        public ?string $notes,
        public string $channel,
        public array $items,
        public float $total,
        public string $status,
        public string $ip,
        public string $createdAt,
    ) {}

    /**
     * @param array<int,Product> $products catalogue rows keyed by id (only the ones the cart mentions)
     * @throws ValidationException
     */
    public static function place(array $in, array $products, string $ip): self
    {
        $e = [];
        $channel = ($in['channel'] ?? 'form') === 'whatsapp' ? 'whatsapp' : 'form';
        $field = static fn(string $k, int $max): string => mb_substr(trim((string)($in[$k] ?? '')), 0, $max);
        $name = $field('name', 120);
        $phone = $field('phone', 30);
        $whatsapp = $field('whatsapp', 30);
        $address = $field('address', 300);
        $notes = $field('notes', 500);

        // The contact form needs name, phone and WhatsApp; going straight to WhatsApp needs nothing.
        if ($channel === 'form') {
            if (mb_strlen($name) < 2) $e['name'][] = 'Enter your full name.';
            if (!self::phoneOk($phone)) $e['phone'][] = 'Enter a valid phone number.';
            if (!self::phoneOk($whatsapp)) $e['whatsapp'][] = 'Enter a valid WhatsApp number.';
        } else {
            if ($phone !== '' && !self::phoneOk($phone)) $e['phone'][] = 'Enter a valid phone number.';
            if ($whatsapp !== '' && !self::phoneOk($whatsapp)) $e['whatsapp'][] = 'Enter a valid WhatsApp number.';
        }

        // Merge duplicate lines, then check each against the catalogue.
        $qty = [];
        foreach (is_array($in['items'] ?? null) ? $in['items'] : [] as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id > 0) $qty[$id] = ($qty[$id] ?? 0) + (int)($row['quantity'] ?? 0);
        }
        if (!$qty) $e['items'][] = 'Your cart is empty.';

        $items = [];
        $total = 0.0;
        foreach ($qty as $id => $q) {
            $p = $products[$id] ?? null;
            if ($p === null) { $e['items'][] = 'An item in your cart is no longer available. Please remove it and try again.'; continue; }
            if ($q < 1 || $q > 99) { $e['items'][] = "Choose a quantity between 1 and 99 for “{$p->name}”."; continue; }
            if ($p->stock <= 0) { $e['items'][] = "“{$p->name}” is out of stock."; continue; }
            if ($q > $p->stock) { $e['items'][] = "Only {$p->stock} of “{$p->name}” left in stock."; continue; }
            $items[] = ['productId' => $id, 'name' => $p->name, 'price' => $p->price, 'quantity' => $q,
                        'image' => $p->image, 'description' => mb_substr(trim((string)preg_replace('/\\s+/', ' ', strip_tags($p->description))), 0, 200)];
            $total += $p->price * $q;
        }

        if ($e) throw new ValidationException($e, ($e['items'][0] ?? null) ?: 'Please check the highlighted fields.');

        return new self(null, null, $name, $phone, $whatsapp, $address !== '' ? $address : null, $notes !== '' ? $notes : null,
            $channel, $items, round($total, 2), 'new', $ip, date('Y-m-d H:i:s'));
    }

    public static function phoneOk(string $v): bool
    {
        if (!preg_match('/^\+?[0-9][0-9 ()\-]{5,24}$/', $v)) return false;
        $digits = strlen(preg_replace('/\D/', '', $v) ?? '');
        return $digits >= 7 && $digits <= 15;
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'orderNumber' => $this->orderNumber, 'name' => $this->name, 'phone' => $this->phone,
                'whatsapp' => $this->whatsapp, 'address' => $this->address, 'notes' => $this->notes, 'channel' => $this->channel,
                'items' => $this->items, 'total' => $this->total, 'status' => $this->status, 'createdAt' => $this->createdAt];
    }
}
