<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\PhpDoc;

use PHPStan\PhpDocParser\Ast\NodeAttributes;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;

final class EmptyPhpDocNode implements PhpDocTagValueNode
{
    use NodeAttributes;

    public function __toString(): string
    {
        return '';
    }
}
