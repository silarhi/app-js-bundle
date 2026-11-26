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

namespace Silarhi\AppJsBundle\Tests\Writer;

use PHPUnit\Framework\TestCase;
use Silarhi\AppJsBundle\Writer\LineDiff;

final class LineDiffTest extends TestCase
{
    public function testWritesTheChangesWithTheirContextAsDiffDoes(): void
    {
        self::assertSame(<<<'DIFF'
            --- a/A.ts
            +++ b/A.ts
            @@ -1,5 +1,5 @@
             a
            -b
            +B
             c
             d
             e
            @@ -8,3 +8,4 @@
             h
             i
             j
            +k

            DIFF, LineDiff::getDiff("a\nb\nc\nd\ne\nf\ng\nh\ni\nj\n", "a\nB\nc\nd\ne\nf\ng\nh\ni\nj\nk\n", 'A.ts'));
    }

    public function testWritesANewAndARemovedFile(): void
    {
        self::assertSame("--- a/New.ts\n+++ b/New.ts\n@@ -0,0 +1,2 @@\n+x\n+y\n", LineDiff::getDiff('', "x\ny\n", 'New.ts'));
        self::assertSame("--- a/Old.ts\n+++ b/Old.ts\n@@ -1,2 +0,0 @@\n-x\n-y\n", LineDiff::getDiff("x\ny\n", '', 'Old.ts'));
    }

    public function testIsEmptyForEqualTexts(): void
    {
        self::assertSame('', LineDiff::getDiff("x\n", "x\n", 'Same.ts'));
    }
}
