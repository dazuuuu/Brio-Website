<?php
// Wiring: which concrete class satisfies each dependency.
use Brio\Application\Services\AuthService;
use Brio\Application\Services\HeroService;
use Brio\Application\Services\LinkResolver;
use Brio\Application\Services\NavigationService;
use Brio\Application\Services\OrderService;
use Brio\Application\Services\PageService;
use Brio\Application\Services\ResetService;
use Brio\Application\Services\SettingsService;
use Brio\Application\Services\SystemPagesService;
use Brio\Application\Services\ProductService;
use Brio\Application\Services\SetupService;
use Brio\Infrastructure\Config\Config;
use Brio\Infrastructure\Database\Connection;
use Brio\Infrastructure\Database\Migrator;
use Brio\Infrastructure\Files\FileHandler;
use Brio\Infrastructure\Persistence\PdoAdminRepository;
use Brio\Infrastructure\Persistence\PdoHeroSlideRepository;
use Brio\Infrastructure\Persistence\PdoNavRepository;
use Brio\Infrastructure\Persistence\PdoOrderRepository;
use Brio\Infrastructure\Persistence\PdoPageRepository;
use Brio\Infrastructure\Persistence\PdoSettingsRepository;
use Brio\Infrastructure\Persistence\PdoProductRepository;

return [
    // Opening the connection also applies any new migrations (so upgrades need no manual step).
    'db'       => function () {
        $pdo = Connection::fromConfig(Config::get('database'));
        (new Migrator(BRIO_ROOT . '/database/migrations', BRIO_ROOT . '/storage/cache/migrated.sig'))->runIfPending($pdo);
        (new SystemPagesService(new PdoPageRepository($pdo), new PdoSettingsRepository($pdo), BRIO_ROOT . '/storage/cache/system-pages.seeded'))->seedOnce();
        return $pdo;
    },
    'products' => fn() => new ProductService(new PdoProductRepository(brio('db')), brio('files'), (int)Config::get('app.low_stock_threshold')),
    'auth'     => fn() => new AuthService(new PdoAdminRepository(brio('db'))),
    'files'    => fn() => new FileHandler(BRIO_PUBLIC, Config::get('app.uploads')),
    'setup'    => fn() => new SetupService(BRIO_ROOT . '/config', new Migrator(BRIO_ROOT . '/database/migrations', BRIO_ROOT . '/storage/cache/migrated.sig'), BRIO_ROOT . '/database/seeds'),
    'links'    => fn() => new LinkResolver(new PdoProductRepository(brio('db')), new PdoPageRepository(brio('db'))),
    'hero'     => fn() => new HeroService(new PdoHeroSlideRepository(brio('db')), brio('files'), brio('links')),
    'pages'    => fn() => new PageService(new PdoPageRepository(brio('db')), brio('products'), brio('links')),
    'navigation' => fn() => new NavigationService(new PdoNavRepository(brio('db')), brio('links')),
    'systempages' => fn() => new SystemPagesService(new PdoPageRepository(brio('db')), new PdoSettingsRepository(brio('db'))),
    'reset'    => fn() => new ResetService(new PdoProductRepository(brio('db')), new PdoOrderRepository(brio('db')), brio('files'), brio('auth')),
    'settings' => fn() => new SettingsService(new PdoSettingsRepository(brio('db'))),
    'orders'   => fn() => new OrderService(new PdoOrderRepository(brio('db')), new PdoProductRepository(brio('db')), brio('settings'), brio_public_url()),
];
