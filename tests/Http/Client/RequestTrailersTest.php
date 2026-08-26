<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Client;

use PHPUnit\Framework\TestCase;
use Smithy\Http\Client\RequestTrailers;

final class RequestTrailersTest extends TestCase
{
    public function testNormalizesStaticTrailerFields(): void
    {
        $trailers = RequestTrailers::fromArray([
            'X-Checksum' => ['first', 'second']
        ]);

        $this->assertSame(
            ['x-checksum' => ['first', 'second']],
            $trailers->resolve()
        );
        $this->assertSame(37, $trailers->fieldSectionLength());
    }

    public function testFuzzesTrailerFieldNormalization(): void
    {
        \mt_srand(0x5eed);

        for ($i = 0; $i < 250; ++$i) {
            $fields = $this->randomTrailerFields();
            $expected = $this->expectedNormalizedTrailerFields($fields);

            if ($expected === null) {
                try {
                    RequestTrailers::fromArray($fields);
                    $this->fail('Expected invalid trailer fields to fail.');
                } catch (\InvalidArgumentException) {
                }

                continue;
            }

            $trailers = RequestTrailers::fromArray($fields);

            $this->assertSame($expected, $trailers->resolve());
            $this->assertSame(
                $this->expectedFieldSectionLength($expected),
                $trailers->fieldSectionLength()
            );
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private function randomTrailerFields(): array
    {
        $fields = [];
        $fieldCount = \mt_rand(0, 6);

        for ($i = 0; $i < $fieldCount; ++$i) {
            $fields[$this->randomFieldName()] = $this->randomFieldValue();
        }

        return $fields;
    }

    private function randomFieldName(): mixed
    {
        $names = [
            'X-Checksum',
            'x-amz-meta-test',
            'Traceparent',
            'bad name',
            "bad\rname",
            '',
            'Content-Length',
            123
        ];

        return $names[\mt_rand(0, \count($names) - 1)];
    }

    private function randomFieldValue(): mixed
    {
        $values = [
            'value',
            "value\twith\tspace",
            "bad\nvalue",
            '',
            ['one', 'two'],
            [],
            ['ok', "bad\x7F"],
            ['ok', 10],
            10
        ];

        return $values[\mt_rand(0, \count($values) - 1)];
    }

    /**
     * @param array<array-key, mixed> $fields
     *
     * @return array<string, list<string>>|null
     */
    private function expectedNormalizedTrailerFields(array $fields): ?array
    {
        $expected = [];
        $disallowed = [
            'authorization' => true,
            'content-encoding' => true,
            'content-length' => true,
            'content-range' => true,
            'content-type' => true,
            'cookie' => true,
            'expect' => true,
            'host' => true,
            'pragma' => true,
            'proxy-authenticate' => true,
            'proxy-authorization' => true,
            'range' => true,
            'te' => true,
            'trailer' => true,
            'transfer-encoding' => true,
            'www-authenticate' => true
        ];

        foreach ($fields as $name => $value) {
            if (!\is_string($name)
                || \preg_match(
                    "/^[!#$%&'*+\\-.^_`|~0-9A-Za-z]+$/D",
                    $name
                ) !== 1
            ) {
                return null;
            }

            $lowerName = \strtolower($name);
            if (isset($disallowed[$lowerName])) {
                return null;
            }

            $values = \is_array($value) ? $value : [$value];
            if ($values === []) {
                return null;
            }

            foreach ($values as $fieldValue) {
                if (!\is_string($fieldValue)
                    || \preg_match(
                        '/[\x00-\x08\x0A-\x1F\x7F]/',
                        $fieldValue
                    ) === 1
                ) {
                    return null;
                }

                $expected[$lowerName][] = $fieldValue;
            }
        }

        return $expected;
    }

    /**
     * @param array<string, list<string>> $fields
     */
    private function expectedFieldSectionLength(array $fields): int
    {
        $length = 0;

        foreach ($fields as $name => $values) {
            foreach ($values as $value) {
                $length += \strlen($name) + \strlen($value) + 3;
            }
        }

        return $length;
    }
}
