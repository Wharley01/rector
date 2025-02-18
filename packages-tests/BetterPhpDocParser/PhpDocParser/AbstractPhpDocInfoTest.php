<?php

declare(strict_types=1);

namespace Rector\Tests\BetterPhpDocParser\PhpDocParser;

use Iterator;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\BetterPhpDocParser\Printer\PhpDocInfoPrinter;
use Rector\Core\Exception\ShouldNotHappenException;
use Rector\Core\HttpKernel\RectorKernel;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
use Rector\FileSystemRector\Parser\FileInfoParser;
use Symplify\EasyTesting\DataProvider\StaticFixtureFinder;
use Symplify\PackageBuilder\Testing\AbstractKernelTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

abstract class AbstractPhpDocInfoTest extends AbstractKernelTestCase
{
    /**
     * @var FileInfoParser
     */
    private $fileInfoParser;

    /**
     * @var BetterNodeFinder
     */
    private $betterNodeFinder;

    /**
     * @var PhpDocInfoPrinter
     */
    private $phpDocInfoPrinter;

    /**
     * @var PhpDocInfoFactory
     */
    private $phpDocInfoFactory;

    protected function setUp(): void
    {
        $this->bootKernel(RectorKernel::class);

        $this->fileInfoParser = $this->getService(FileInfoParser::class);

        $this->betterNodeFinder = $this->getService(BetterNodeFinder::class);
        $this->phpDocInfoPrinter = $this->getService(PhpDocInfoPrinter::class);
        $this->phpDocInfoFactory = $this->getService(PhpDocInfoFactory::class);
    }

    /**
     * @param class-string<\PHPStan\PhpDocParser\Ast\Node> $tagValueNodeType
     */
    protected function doTestPrintedPhpDocInfo(
        SmartFileInfo $smartFileInfo,
        string $tagValueNodeType,
        string $nodeClass
    ): void {
        $nodeWithPhpDocInfo = $this->parseFileAndGetFirstNodeOfType($smartFileInfo, $nodeClass);

        $docComment = $nodeWithPhpDocInfo->getDocComment();
        if (! $docComment instanceof Doc) {
            throw new ShouldNotHappenException(sprintf('Doc comments for "%s" file cannot not be empty', $fileInfo));
        }

        $originalDocCommentText = $docComment->getText();
        $printedPhpDocInfo = $this->printNodePhpDocInfoToString($nodeWithPhpDocInfo);

        $this->assertSame($originalDocCommentText, $printedPhpDocInfo);

        $this->doTestContainsTagValueNodeType($nodeWithPhpDocInfo, $tagValueNodeType, $smartFileInfo);
    }

    protected function yieldFilesFromDirectory(string $directory, string $suffix = '*.php'): Iterator
    {
        return StaticFixtureFinder::yieldDirectory($directory, $suffix);
    }

    protected function findFilesFromDirectory(string $directory, string $suffix = '*.php'): Iterator
    {
        return StaticFixtureFinder::yieldDirectory($directory, $suffix);
    }

    /**
     * @template T as Node
     * @param class-string<T> $nodeType
     * @return T
     */
    private function parseFileAndGetFirstNodeOfType(SmartFileInfo $fileInfo, string $nodeType): Node
    {
        $nodes = $this->fileInfoParser->parseFileInfoToNodesAndDecorate($fileInfo);

        $namespace = $this->betterNodeFinder->findFirstInstanceOf($nodes, $nodeType);
        if (! $namespace instanceof Node) {
            throw new ShouldNotHappenException();
        }

        return $namespace;
    }

    private function printNodePhpDocInfoToString(Node $node): string
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        return $this->phpDocInfoPrinter->printFormatPreserving($phpDocInfo);
    }

    /**
     * @param class-string<\PHPStan\PhpDocParser\Ast\Node> $tagValueNodeType
     */
    private function doTestContainsTagValueNodeType(Node $node, string $tagValueNodeType, SmartFileInfo $fileInfo): void
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);

        $hasByAnnotationClass = $phpDocInfo->hasByAnnotationClass($tagValueNodeType);
        $this->assertTrue($hasByAnnotationClass, $fileInfo->getRelativeFilePathFromCwd());
    }
}
