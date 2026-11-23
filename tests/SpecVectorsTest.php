<?php

declare(strict_types=1);

namespace Madagascar\MgUtils\Tests;

use Madagascar\MgUtils\MgUtilsException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Runs every vector in spec/<module>/tests.yaml against the PHP API.
 * A module is "done" in PHP when all of its vectors pass here.
 */
final class SpecVectorsTest extends TestCase
{
    private const string SPEC_DIR = __DIR__ . '/../../spec';

    /**
     * @return array<string, array<string, \Closure>>
     */
    private static function functions(): array
    {
        /** @var array<string, array<string, \Closure>> */
        return require __DIR__ . '/spec-functions.php';
    }

    /**
     * @return iterable<string, array{\Closure, array<string, mixed>}>
     */
    public static function vectors(): iterable
    {
        foreach (self::functions() as $module => $moduleFunctions) {
            /** @var list<array<string, mixed>> $vectors */
            $vectors = Yaml::parseFile(self::SPEC_DIR . "/{$module}/tests.yaml");
            foreach ($vectors as $index => $vector) {
                $name = \is_string($vector['fn'] ?? null) ? $vector['fn'] : array_key_first($moduleFunctions);
                yield "{$module} #{$index} {$name}" => [$moduleFunctions[$name], $vector];
            }
        }
    }

    /**
     * @param array<string, mixed> $vector
     */
    #[DataProvider('vectors')]
    public function testVector(\Closure $function, array $vector): void
    {
        $arguments = [$vector['input'], ...(\is_array($vector['args'] ?? null) ? $vector['args'] : [])];

        if (!\array_key_exists('error', $vector)) {
            self::assertSame($vector['expected'], $function(...$arguments));

            return;
        }
        self::assertSame($vector['error'], self::errorCodeOf(static fn(): mixed => $function(...$arguments)));
    }

    private static function errorCodeOf(\Closure $call): string
    {
        try {
            $call();
        } catch (MgUtilsException $exception) {
            return $exception->errorCode->value;
        } catch (\TypeError|\ValueError) {
            return 'INVALID_TYPE';
        }

        return 'no error thrown';
    }
}
