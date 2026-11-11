<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Order\OrderRepository;
use Brio\Domain\Product\ProductRepository;
use Brio\Infrastructure\Files\FileHandler;
use Brio\Infrastructure\Logging\Logger;

/**
 * Clears store data (products and orders) after re-confirming the admin's password.
 * Never touches: the admin account, pages, hero slides, menus, settings.
 */
final class ResetService
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly OrderRepository $orders,
        private readonly FileHandler $files,
        private readonly AuthService $auth,
    ) {}

    /** What a reset would remove right now. */
    public function preview(): array
    {
        return ['products' => $this->products->count(), 'orders' => $this->orders->count(null)];
    }

    /** @return array{products:int,orders:int,images:int} */
    public function run(string $password): array
    {
        $this->auth->confirmPassword($password);

        $removed = $this->products->deleteAll();
        $orders = $this->orders->deleteAll();
        $images = 0;
        foreach ($removed['images'] as $path) {
            if ($this->files->delete($path)) $images++; // only uploaded files are removed; bundled starter images stay
        }

        Logger::warning('Store reset', ['admin' => $this->auth->user()?->email, 'products' => $removed['count'], 'orders' => $orders]);
        return ['products' => $removed['count'], 'orders' => $orders, 'images' => $images];
    }
}
