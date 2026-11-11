<?php
declare(strict_types=1);

namespace Brio\Domain\Settings;

interface SettingsRepository
{
    /** @return array<string,string> */
    public function all(): array;

    /** @param array<string,string> $values */
    public function set(array $values): void;
}
