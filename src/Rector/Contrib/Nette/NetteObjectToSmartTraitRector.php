<?php declare(strict_types=1);

namespace Rector\Rector\Contrib\Nette;

use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use Rector\Builder\StatementGlue;
use Rector\Deprecation\SetNames;
use Rector\NodeFactory\NodeFactory;
use Rector\Rector\AbstractRector;

/**
 * Covers https://doc.nette.org/en/2.4/migration-2-4#toc-nette-smartobject.
 */
final class NetteObjectToSmartTraitRector extends AbstractRector
{
    /**
     * @var string
     */
    private const PARENT_CLASS = 'Nette\Object';

    /**
     * @var string
     */
    private const TRAIT_NAME = 'Nette\SmartObject';

    /**
     * @var StatementGlue
     */
    private $statementGlue;

    /**
     * @var NodeFactory
     */
    private $nodeFactory;

    public function __construct(StatementGlue $statementGlue, NodeFactory $nodeFactory)
    {
        $this->statementGlue = $statementGlue;
        $this->nodeFactory = $nodeFactory;
    }

    public function getSetName(): string
    {
        return SetNames::NETTE;
    }

    public function sinceVersion(): float
    {
        return 2.2;
    }

    public function isCandidate(Node $node): bool
    {
        if (! $node instanceof Class_ || $node->extends === null) {
            return false;
        }

        /** @var FullyQualified $fqnName */
        $fqnName = $node->extends->getAttribute('resolvedName');

        return $fqnName->toString() === self::PARENT_CLASS;
    }

    /**
     * @param Class_ $classNode
     */
    public function refactor(Node $classNode): ?Node
    {
        $traitUseNode = $this->nodeFactory->createTraitUse(self::TRAIT_NAME);
        $this->statementGlue->addAsFirstTrait($classNode, $traitUseNode);

        $this->removeParentClass($classNode);

        return $classNode;
    }

    private function removeParentClass(Class_ $classNode): void
    {
        $classNode->extends = null;
    }
}
