<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Date\Internal;

use Mdgascar\MdgUtils\Date\MalagasyTime;

/**
 * Keeps every time of the day by its words once built, since `MalagasyTime` is a readonly
 * class and cannot hold a static cache itself.
 *
 * @internal not part of the public API; use `MalagasyTime::parse()`
 */
final class TimeTable
{
    /** @var array<string, MalagasyTime>|null */
    private static ?array $times = null;

    /**
     * @param \Closure(): array<string, MalagasyTime> $build
     *
     * @return array<string, MalagasyTime>
     */
    public static function byWords(\Closure $build): array
    {
        return self::$times ??= $build();
    }
}
