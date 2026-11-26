<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils;

/**
 * Outcome of a `safeParse()` call: either the parsed value in `data`, or the rejection in
 * `error` with its `errorCode`. Never throws. Same shape as `SafeParseResult` in mdg-utils-js.
 *
 * Example:
 *
 *     $result = PhoneNumber::safeParse('035 12 345 67');
 *     $result->success;           // false
 *     $result->error?->errorCode; // ErrorCode::UnknownOperator
 *
 * @template-covariant T of object
 */
final readonly class SafeParseResult
{
    /**
     * @param T|null $data
     */
    private function __construct(
        public bool $success,
        public ?object $data,
        public ?MdgUtilsException $error,
    ) {}

    /**
     * @internal built by the `safeParse()` methods
     *
     * @template V of object
     *
     * @param V $data
     *
     * @return self<V>
     */
    public static function success(object $data): self
    {
        return new self(true, $data, null);
    }

    /**
     * @internal built by the `safeParse()` methods
     *
     * @return self<never>
     */
    public static function failure(MdgUtilsException $error): self
    {
        // No value to infer T from: a failure fits any SafeParseResult<T> (T is covariant).
        /** @var self<never> $failure */
        $failure = new self(false, null, $error);

        return $failure;
    }
}
