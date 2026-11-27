<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils;

/**
 * Thrown by mdg-utils when input is rejected. Check `errorCode`: it is the same in every language.
 *
 * Example:
 *
 *     try {
 *         NumberWords::spell(-1);
 *     } catch (MdgUtilsException $exception) {
 *         $exception->errorCode === ErrorCode::OutOfRange; // true
 *     }
 */
final class MdgUtilsException extends \InvalidArgumentException
{
    public function __construct(
        public readonly ErrorCode $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
