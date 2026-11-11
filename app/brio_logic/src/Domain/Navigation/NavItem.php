<?php
declare(strict_types=1);

namespace Brio\Domain\Navigation;

use Brio\Domain\Exceptions\ValidationException;
use Brio\Domain\Link\Link;

final class NavItem
{
    public const LOCATIONS = ['header', 'footer'];

    public function __construct(
        public ?int $id,
        public string $location,
        public string $label,
        public Link $link,
        public bool $newTab,
        public bool $isActive,
        public int $sortOrder = 0,
    ) {}

    public static function fromInput(string $location, array $in, int $position): self
    {
        $n = $position + 1;
        $label = trim((string)($in['label'] ?? ''));
        $e = [];
        if ($label === '' || mb_strlen($label) > 60) $e['items'][] = "Item $n: enter a label (max 60 characters).";
        try {
            $link = Link::fromInput($in['linkType'] ?? 'url', $in['linkTarget'] ?? '', 'items');
            if ($link->isNone()) $e['items'][] = "Item $n: choose where it links to.";
        } catch (ValidationException $x) {
            $e['items'][] = "Item $n: " . $x->errors['items'][0];
            $link = Link::none();
        }
        if ($e) throw new ValidationException($e, $e['items'][0]);

        return new self(null, $location, $label, $link, !empty($in['newTab']), !isset($in['isActive']) || (bool)$in['isActive'], $position);
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'location' => $this->location, 'label' => $this->label, 'linkType' => $this->link->type,
                'linkTarget' => $this->link->target, 'newTab' => $this->newTab, 'isActive' => $this->isActive, 'sortOrder' => $this->sortOrder];
    }
}
