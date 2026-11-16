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
            'about' => ['title' => 'About us', 'slug' => 'about-us', 'description' => 'Who we are and what we make', 'blocks' => self::aboutBlocks()],
        ];
    }

    /** Starter copy for the About us page. It is ordinary page-builder content: edit it in Admin → Pages. */
    private static function aboutBlocks(): array
    {
        $shopSkin = ['type' => 'category', 'target' => 'skin-care'];
        $shopBody = ['type' => 'category', 'target' => 'bath-body'];
        $item = static fn(string $id, string $src, string $alt, string $title, string $text, string $side, array $link, string $cta): array => [
            'id' => $id, 'type' => 'image_text', 'src' => $src, 'alt' => $alt, 'title' => $title, 'text' => $text, 'side' => $side, 'ctaLabel' => $cta, 'link' => $link,
        ];
        return [
            ['id' => 'abt00001', 'type' => 'heading', 'text' => 'About Asiva Organics', 'level' => 'h2', 'align' => 'center'],
            ['id' => 'abt00002', 'type' => 'text', 'align' => 'center', 'text' => "Asiva Organics is about simple, everyday care for your face, hands and body.\n\nOur range brings together ingredients like cucumber, aloe vera, papaya and vitamins C & E in products designed to be easy to use, every day."],
            $item('abt00003', 'assets/img/products/face_cream.jpeg', 'Cucumber and aloe vera face cream', 'Face Cream', 'Our Cucumber & Aloe Vera Face Cream is a hydrating, brightening and anti-aging face cream with SPF 30. Made with cucumber and aloe vera, it is light enough to wear every day.', 'left', $shopSkin, 'Shop skin care'),
            $item('abt00004', 'assets/img/products/serum.jpeg', 'Vitamin C and E serum', 'Serum', 'Our Vitamin C & E Serum brightens skin tone, hydrates and nourishes, and helps fight free radicals. A simple step to add to your skincare routine.', 'right', $shopSkin, 'Shop skin care'),
            $item('abt00005', 'assets/img/products/showergel.jpeg', 'Cucumber and aloe vera shower gel', 'Shower Gel', 'Our Organic Shower Gel with cucumber and aloe vera cleanses, hydrates and refreshes. Gentle cleansing and deep hydration for all skin types.', 'left', $shopBody, 'Shop bath & body'),
            $item('abt00006', 'assets/img/products/body_lotion.jpeg', 'Papaya extract body and hands lotion', 'Body & Hand Lotion', 'Our Papaya Extract Body & Hands Lotion is a brightening, moisturizing lotion with vitamin E, for smooth, soft and radiant skin.', 'right', $shopBody, 'Shop bath & body'),
            ['id' => 'abt00007', 'type' => 'heading', 'text' => 'Shopping with us', 'level' => 'h2', 'align' => 'center'],
            ['id' => 'abt00008', 'type' => 'text', 'align' => 'left', 'text' => "- Order online in minutes, with or without an account\n- Finish your order on WhatsApp, or leave your details and we will contact you\n- Create an account to track your orders"],
            ['id' => 'abt00009', 'type' => 'button', 'label' => 'Shop all products', 'link' => ['type' => 'shop', 'target' => null], 'align' => 'center', 'style' => 'primary'],
        ];
    }
}
