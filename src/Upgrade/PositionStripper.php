<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Removes source positions from nodes parsed out of another file, so the
 * format-preserving printer prints them fresh. Comments are kept.
 *
 * @internal
 */
final class PositionStripper extends NodeVisitorAbstract
{
    #[Override]
    public function enterNode(Node $node): null
    {
        $node->setAttributes(['comments' => $node->getComments()]);

        return null;
    }
}
