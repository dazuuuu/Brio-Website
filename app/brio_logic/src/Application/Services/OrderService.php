<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Exceptions\NotFoundException;
use Brio\Domain\Exceptions\RateLimitException;
use Brio\Domain\Exceptions\ValidationException;
use Brio\Domain\Order\Order;
use Brio\Domain\Order\OrderRepository;
use Brio\Domain\Product\ProductRepository;
use Brio\Infrastructure\Config\Config;
use Brio\Infrastructure\Logging\Logger;

final class OrderService
{
    private const MAX_PER_WINDOW = 5;
    private const WINDOW_SECONDS = 600;

    public function __construct(
        private readonly OrderRepository $orders,
        private readonly ProductRepository $products,
        private readonly SettingsService $settings,
        private readonly string $siteUrl = '',
    ) {}

    /** Whether the checkout page should offer the WhatsApp button. */
    public function whatsappEnabled(): bool
    {
        return $this->settings->get()['whatsapp_number'] !== '';
    }

    /** @return array order summary + whatsappUrl (null when no WhatsApp number is configured) */
    public function place(array $input, string $ip): array
    {
        // Honeypot: real people never fill the hidden "website" field. Pretend success, save nothing.
        if (trim((string)($input['website'] ?? '')) !== '') {
            return ['orderNumber' => 'BRIO-0000', 'total' => 0, 'channel' => 'form', 'whatsappUrl' => null];
        }
        if ($this->orders->countRecent($ip, self::WINDOW_SECONDS) >= self::MAX_PER_WINDOW) {
            throw new RateLimitException('Too many orders from your connection. Please wait a few minutes or contact us on WhatsApp.');
        }

        $ids = array_map(static fn($r) => (int)($r['id'] ?? 0), is_array($input['items'] ?? null) ? $input['items'] : []);
        $order = Order::place($input, $this->products->findMany($ids), $ip);
        $this->orders->save($order);
        Logger::info('Order placed', ['order' => $order->orderNumber, 'channel' => $order->channel, 'total' => $order->total]);

        return $order->toArray() + ['whatsappUrl' => $this->whatsappUrl($order)];
    }

    public function paginate(?string $status, int $page, int $perPage): array
    {
        $status = in_array($status, Order::STATUSES, true) ? $status : null;
        $total = $this->orders->count($status);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min($pages, max(1, $page));
        $rows = array_map(fn(Order $o) => $o->toArray(), $this->orders->search($status, $perPage, ($page - 1) * $perPage));
        return ['data' => $rows, 'meta' => ['page' => $page, 'perPage' => $perPage, 'total' => $total, 'pages' => $pages], 'counts' => $this->counts()];
    }

    public function counts(): array
    {
        $by = $this->orders->countsByStatus();
        $counts = ['all' => array_sum($by)];
        foreach (Order::STATUSES as $s) $counts[$s] = $by[$s] ?? 0;
        return $counts;
    }

    public function setStatus(int $id, mixed $status): array
    {
        $order = $this->orders->find($id) ?? throw new NotFoundException('Order not found.');
        if (!in_array($status, Order::STATUSES, true)) throw new ValidationException(['status' => ['Choose a valid status.']]);
        $this->orders->updateStatus($id, $status);
        $order->status = $status;
        Logger::info('Order status changed', ['order' => $order->orderNumber, 'status' => $status]);
        return $order->toArray();
    }

    public function delete(int $id): void
    {
        $order = $this->orders->find($id) ?? throw new NotFoundException('Order not found.');
        $this->orders->delete($id);
        Logger::info('Order deleted', ['order' => $order->orderNumber]);
    }

    /** wa.me link with the order pre-written, addressed to the store's WhatsApp number. */
    private function whatsappUrl(Order $o): ?string
    {
        $s = $this->settings->get();
        if ($s['whatsapp_number'] === '') return null;
        $cur = (string)Config::get('app.currency', 'KSh');

        $lines = [$s['whatsapp_greeting'], '', 'Order: ' . $o->orderNumber, ''];
        foreach ($o->items as $i) {
            $lines[] = sprintf('• %d × %s — %s %s', $i['quantity'], $i['name'], $cur, number_format($i['price'] * $i['quantity']));
            $about = trim((string)($i['description'] ?? ''));
            if ($about !== '') $lines[] = '   ' . (mb_strlen($about) > 100 ? mb_substr($about, 0, 100) . '…' : $about);
            if (!empty($i['image']) && $this->siteUrl !== '') $lines[] = '   Photo: ' . $this->siteUrl . '/' . ltrim($i['image'], '/');
        }
        $lines[] = '';
        $lines[] = 'Total: ' . $cur . ' ' . number_format($o->total);
        $details = ['Name' => $o->name, 'Phone' => $o->phone, 'WhatsApp' => $o->whatsapp, 'Address' => $o->address, 'Notes' => $o->notes];
        $details = array_filter($details, static fn($v) => $v !== null && $v !== '');
        if ($details) {
            $lines[] = '';
            foreach ($details as $label => $value) $lines[] = "$label: $value";
        }
        return 'https://wa.me/' . $s['whatsapp_number'] . '?text=' . rawurlencode(implode("\n", $lines));
    }
}
