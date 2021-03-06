<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/data-structure project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Tests\DataStructure\Fixture;

use Daikon\DataStructure\Map;

final class PlainMap extends Map
{
    public function __construct(iterable $values = [])
    {
        $this->init($values);
    }
}
