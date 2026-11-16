<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tools\PackageManifests;

/**
 * Compares the package composer.json files under src with the root
 * composer.json.
 *
 * @internal
 */
final class PackageManifests
{
    /**
     * Returns one line for each difference. An empty list means that the
     * package manifests agree with the root.
     *
     * @param array<string, mixed> $root The decoded root composer.json.
     * @param array<string, array<string, mixed>> $packages The decoded
     *     package composer.json files, keyed by path.
     * @return list<string>
     */
    public static function compare(array $root, array $packages): array
    {
        $replaced = $root['replace'] ?? [];
        $php = $root['require']['php'] ?? null;
        $differences = [];
        $names = [];
        $required = [];

        foreach ($packages as $path => $package) {
            $name = $package['name'] ?? null;
            if (!\is_string($name) || $name === '') {
                $differences[] = "{$path}: has no name";
            } else {
                if (isset($names[$name])) {
                    $differences[] = "{$path}: {$name} is also the name of"
                        . " {$names[$name]}";
                } else {
                    $names[$name] = $path;
                }

                if (($replaced[$name] ?? null) !== 'self.version') {
                    $differences[] = "{$path}: {$name} is not in root replace"
                        . ' as self.version';
                }
            }

            if ($php !== null && !isset($package['require']['php'])) {
                $differences[] = "{$path}: require php is missing,"
                    . " root has {$php}";
            }

            foreach ($package['require'] ?? [] as $dependency => $constraint) {
                $required[$dependency] = true;

                if (isset($replaced[$dependency])) {
                    if ($constraint !== 'self.version') {
                        $differences[] = "{$path}: require {$dependency}"
                            . " is {$constraint}, not self.version";
                    }

                    continue;
                }

                $rootConstraint = $root['require'][$dependency] ?? null;
                $extension = \str_starts_with($dependency, 'ext-');

                if ($rootConstraint === null && $extension) {
                    if (!isset($root['suggest'][$dependency])) {
                        $differences[] = "{$path}: require {$dependency}"
                            . ' is in neither root require nor root suggest';
                    }
                } elseif ($rootConstraint === null) {
                    $differences[] = "{$path}: require {$dependency}"
                        . ' is not in root require';
                } elseif ($rootConstraint !== $constraint) {
                    $differences[] = "{$path}: require {$dependency}"
                        . " is {$constraint}, root has {$rootConstraint}";
                }
            }
        }

        foreach (\array_keys($replaced) as $name) {
            if (!isset($names[$name])) {
                $differences[] = "composer.json: replace {$name}"
                    . ' has no package composer.json';
            }
        }

        foreach (\array_keys($root['require'] ?? []) as $dependency) {
            if (!isset($required[$dependency])) {
                $differences[] = "composer.json: require {$dependency}"
                    . ' is in no package composer.json';
            }
        }

        return $differences;
    }
}
