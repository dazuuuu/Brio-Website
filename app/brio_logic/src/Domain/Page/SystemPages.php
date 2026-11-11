<?php
declare(strict_types=1);

namespace Brio\Domain\Page;

/**
 * The store's built-in pages. They're stored like any other page (so they can be edited and deleted in the
 * page builder) but they start life from these defaults, and a deleted one can be restored from them.
 */
final class SystemPages
{
    /** One page per product is generated from the catalogue, so it isn't a page you edit here. */
    public const PRODUCT_PAGES = ['title' => 'Product pages', 'path' => '/products/…', 'description' => 'Created automatically from each product. Edit or delete products in Products.'];

    /** @return array<string,array{title:string,slug:string,description:string,blocks:array}> keyed by system key */
    public static function defaults(): array
    {
        $section = static fn(string $id, string $name, array $extra = []): array => ['id' => $id, 'type' => 'section', 'name' => $name] + $extra;
        return [
            'home' => ['title' => 'Home', 'slug' => '', 'description' => 'Your front page', 'blocks' => [
                $section('hero0001', 'hero'),
                $section('deals001', 'deals', ['title' => 'Daily Deals', 'subtitle' => 'Special limited-time offers on our signature organic essentials']),
                ['id' => 'feat0001', 'type' => 'products', 'title' => 'Featured Products', 'subtitle' => 'Explore all fresh botanical formulations',
                 'source' => 'featured', 'category' => '', 'ids' => [], 'limit' => 24],
                ['id' => 'btn00001', 'type' => 'button', 'label' => 'View all products →', 'link' => ['type' => 'shop', 'target' => null], 'align' => 'center', 'style' => 'outline'],
            ]],
            'shop' => ['title' => 'Shop', 'slug' => 'products', 'description' => 'All products with search and filters', 'blocks' => [$section('shop0001', 'catalog')]],
            'checkout' => ['title' => 'Checkout', 'slug' => 'checkout', 'description' => 'Order form and WhatsApp ordering', 'blocks' => [$section('chk00001', 'checkout')]],
            'login' => ['title' => 'Sign in', 'slug' => 'login', 'description' => 'Customer sign in', 'blocks' => [$section('login001', 'login')]],
            'register' => ['title' => 'Register', 'slug' => 'register', 'description' => 'Customer sign up', 'blocks' => [$section('reg00001', 'register')]],
            'account' => ['title' => 'My account', 'slug' => 'account', 'description' => 'Customer profile and orders', 'blocks' => [$section('acct0001', 'account')]],
        ];
    }
}
