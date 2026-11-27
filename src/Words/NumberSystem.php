<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Words;

/**
 * How large numbers are named (spec words rule 12).
 *
 * Example: `NumberWords::spell(30_000_000, NumberSystem::Traditional)` returns
 * `'telo safatsiroa'`; with `NumberSystem::Standard` (the default) it is `'telopolo tapitrisa'`.
 */
enum NumberSystem: string
{
    /** Everyday writing: counts before `tapitrisa` and `lavitrisa` (`telopolo tapitrisa`). */
    case Standard = 'standard';

    /** One name per power of ten, each taking a single digit (`telo safatsiroa`). */
    case Traditional = 'traditional';
}
