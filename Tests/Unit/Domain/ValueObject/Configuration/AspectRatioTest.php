<?php
declare(strict_types=1);

namespace Neos\Media\Tests\Unit\Domain\ValueObject\Configuration;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\ValueObject\Configuration\AspectRatio;

class AspectRatioTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function aspectRatioCanBeConvertedToString(): void
    {
        $aspectRatio = new AspectRatio(16, 9);
        self::assertSame('16:9', (string)$aspectRatio);
    }

    /**
     * @test
     * @return void
     */
    public function aspectRatioCanBeCreatedFromString(): void
    {
        $aspectRatio = AspectRatio::fromString('16:9');

        self::assertSame(16, $aspectRatio->getWidth());
        self::assertSame(9, $aspectRatio->getHeight());
    }

    /**
     * @return array
     */
    public function validStrings(): array
    {
        return [
            ['16:9'],
            ['1:1'],
            ['24:98'],
            ['500:600']
        ];
    }

    /**
     * @test
     * @dataProvider validStrings
     * @param string $validString
     * @return void
     */
    public function validStringIsAccepted(string $validString): void
    {
        $aspectRatio = AspectRatio::fromString($validString);
        self::assertSame($validString, (string)$aspectRatio);
    }

    /**
     * @return array
     */
    public function invalidStrings(): array
    {
        return [
            ['invalid'],
            ['16 9'],
            ['something:else'],
            ['something:8'],
            ['1:-8'],
            ['1:foo'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidStrings
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1552641724
     * @param string $invalidString
     * @return void
     */
    public function invalidStringIsRejected(string $invalidString): void
    {
        AspectRatio::fromString($invalidString);
    }
}
