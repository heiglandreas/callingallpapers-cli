<?php
/**
 * Copyright (c) Andreas Heigl<andreas@heigl.org>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Andreas Heigl<andreas@heigl.org>
 * @copyright Andreas Heigl
 * @license   http://www.opensource.org/licenses/mit-license.php MIT-License
 * @since     04.09.2017
 * @link      http://github.com/heiglandreas/callingallpapers
 */

namespace CallingallpapersTest\CfpFIlter;

use Callingallpapers\CfpFilter\StripParamsFromUri;
use Callingallpapers\Entity\Cfp;
use PHPUnit\Framework\TestCase;

class StripParamsFromUriTest extends TestCase
{
    /** @dataProvider strippingUriProvider */
    public function testThatStrippingParamsWorks($uri, $expectedUri)
    {
        $filter = new StripParamsFromUri(['conferenceUri']);

        $cfp = new Cfp();
        $cfp->conferenceUri = $uri;

        $this->assertEquals($uri, $cfp->conferenceUri);
        $this->assertSame($cfp, $filter->filter($cfp));
        $this->assertEquals($expectedUri, $cfp->conferenceUri);
    }

    public function strippingUriProvider()
    {
        return [
            ['http://example.com', 'http://example.com'],
            ['http://example.com/foo', 'http://example.com/foo'],
            ['http://example.com/foo?bar=test', 'http://example.com/foo'],
        ];
    }
}