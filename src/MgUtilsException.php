<?php

declare(strict_types=1);

namespace Madagascar\MgUtils;

/**
 * Thrown by mg-utils when input is rejected. Check `errorCode`: it is the same in every language.
 *
 * Example:
 *
 *     try {
 *         NumberWords::fromInt(-1);
 *     } catch (MgUtilsException $exception) {
 *         $exception->errorCode === ErrorCode::OutOfRange; // true
 *     }
 */
final class MgUtilsException extends \InvalidArgumentException
{
    public function __construct(
        public readonly ErrorCode $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
