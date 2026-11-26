<?php

declare(strict_types=1);

/*
 * This file is part of the App JS Bundle package.
 *
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silarhi\AppJsBundle\Writer;

use function count;
use function sprintf;

/**
 * A unified diff of two texts, line by line, for the files --check finds out of date.
 *
 * @internal
 */
final class LineDiff
{
    /** The unchanged lines shown around a change */
    private const CONTEXT = 3;

    /**
     * @return string the diff, empty when the texts are equal
     */
    public static function getDiff(string $old, string $new, string $path): string
    {
        if ($old === $new) {
            return '';
        }

        $operations = self::getOperations(self::splitLines($old), self::splitLines($new));
        $diff = sprintf("--- a/%s\n+++ b/%s\n", $path, $path);
        foreach (self::groupHunks($operations) as $hunk) {
            $diff .= self::renderHunk($hunk);
        }

        return $diff;
    }

    /**
     * @return list<string>
     */
    private static function splitLines(string $text): array
    {
        return '' === $text ? [] : explode("\n", rtrim($text, "\n"));
    }

    /**
     * The longest common subsequence of the lines, as the lines kept, removed and added, with their line numbers.
     *
     * @param list<string> $old
     * @param list<string> $new
     *
     * @return list<array{string, int, int, string}> the operation (' ', '-', '+'), the old and new line numbers, the line
     */
    private static function getOperations(array $old, array $new): array
    {
        $nbOld = count($old);
        $nbNew = count($new);
        $lengths = array_fill(0, $nbOld + 1, array_fill(0, $nbNew + 1, 0));
        for ($i = $nbOld - 1; $i >= 0; --$i) {
            for ($j = $nbNew - 1; $j >= 0; --$j) {
                $lengths[$i][$j] = $old[$i] === $new[$j] ? $lengths[$i + 1][$j + 1] + 1 : max($lengths[$i + 1][$j], $lengths[$i][$j + 1]);
            }
        }

        $operations = [];
        $i = 0;
        $j = 0;
        while ($i < $nbOld || $j < $nbNew) {
            if ($i < $nbOld && $j < $nbNew && $old[$i] === $new[$j]) {
                $operations[] = [' ', ++$i, ++$j, $old[$i - 1]];
            } elseif ($i < $nbOld && ($j === $nbNew || $lengths[$i + 1][$j] >= $lengths[$i][$j + 1])) {
                // Removed before added, as diff -u writes them
                $operations[] = ['-', ++$i, $j, $old[$i - 1]];
            } else {
                $operations[] = ['+', $i, ++$j, $new[$j - 1]];
            }
        }

        return $operations;
    }

    /**
     * The changes with the lines around them, the changes close to each other in the same hunk.
     *
     * @param list<array{string, int, int, string}> $operations
     *
     * @return list<non-empty-list<array{string, int, int, string}>>
     */
    private static function groupHunks(array $operations): array
    {
        $shown = [];
        foreach ($operations as $index => [$operation]) {
            if (' ' === $operation) {
                continue;
            }

            for ($k = max(0, $index - self::CONTEXT); $k <= min(count($operations) - 1, $index + self::CONTEXT); ++$k) {
                $shown[$k] = true;
            }
        }

        $hunks = [];
        $hunk = [];
        foreach ($operations as $index => $operation) {
            if (isset($shown[$index])) {
                $hunk[] = $operation;
            } elseif ([] !== $hunk) {
                $hunks[] = $hunk;
                $hunk = [];
            }
        }

        if ([] !== $hunk) {
            $hunks[] = $hunk;
        }

        return $hunks;
    }

    /**
     * @param non-empty-list<array{string, int, int, string}> $hunk
     */
    private static function renderHunk(array $hunk): string
    {
        $nbOld = count(array_filter($hunk, static fn (array $operation): bool => '+' !== $operation[0]));
        $nbNew = count(array_filter($hunk, static fn (array $operation): bool => '-' !== $operation[0]));
        // The line before the hunk when it starts with a line added: the number of the first line otherwise
        [$operation, $oldLine, $newLine] = $hunk[0];
        $oldStart = '+' === $operation ? $oldLine + 1 : $oldLine;
        $newStart = '-' === $operation ? $newLine + 1 : $newLine;

        $lines = sprintf("@@ -%d,%d +%d,%d @@\n", 0 === $nbOld ? $oldStart - 1 : $oldStart, $nbOld, 0 === $nbNew ? $newStart - 1 : $newStart, $nbNew);
        foreach ($hunk as [$operation, , , $line]) {
            $lines .= $operation . $line . "\n";
        }

        return $lines;
    }
}
