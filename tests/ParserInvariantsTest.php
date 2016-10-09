<?php
// TODO autoload classes
require_once(__DIR__ . "/../lexer.php");
require_once(__DIR__ . "/../parser.php");
require_once(__DIR__ . "/../Token.php");
require_once(__DIR__ . "/LexerInvariantsTest.php");

use PHPUnit\Framework\TestCase;
use PhpParser\TokenKind;
use PhpParser\Node;

class ParserInvariantsTest extends LexerInvariantsTest {
    // TODO test w/ multiple files
    const FILENAMES = array (
        __dir__ . "/cases/parserPocFile.php",
        __dir__ . "/cases/parserPocFile2.php"
    );

    private $parser;

    public function setUp() {
        $this->parser = new \PhpParser\Parser();

        foreach (self::FILENAMES as $filename) {
            $sourceFileNode = $this->parser->parseSourceFile($filename);
            $tokensArray = array();
            foreach ($sourceFileNode->getAllChildren() as $child) {
                if ($child instanceof \PhpParser\Token) {
                    array_push($tokensArray, $child);
                }
            }
            $this->fileToTokensArrayMap[$filename] = $tokensArray;
        }
    }

    public function testSourceFileNodeLengthEqualsDocumentLength() {
        foreach (self::FILENAMES as $filename) {
            $sourceFileNode = $this->parser->parseSourceFile($filename);
            $this->assertEquals(
                filesize($filename), $sourceFileNode->getLength(),
                "Invariant: The tree length exactly matches the file length.");
        }
    }

    public function testNodesAllHaveAtLeastOneChild() {
        foreach (self::FILENAMES as $filename) {
            $sourceFileNode = $this->parser->parseSourceFile($filename);

            foreach ($sourceFileNode->getAllChildren() as $child) {
                if ($child instanceof Node) {
                    $this->assertGreaterThanOrEqual(
                        1, count($child->children),
                        "Invariant: All Nodes have at least one child."
                    );
                }
            }
        }
    }

    public function testEveryNodeSpanIsSumOfChildSpans() {
        foreach (self::FILENAMES as $filename) {
            $sourceFileNode = $this->parser->parseSourceFile($filename);

            $treeElements = $sourceFileNode->getAllChildren();
            array_push($treeElements, $sourceFileNode);

            foreach ($treeElements as $element) {
                if ($element instanceof Node) {
                    $expectedLength = 0;
                    foreach ($element->children as $child) {
                        if ($child instanceof Node) {
                            $expectedLength += $child->getLength();
                        } else if ($child instanceof \PhpParser\Token) {
                            $expectedLength += $child->length;
                        }
                    }
                    $this->assertEquals(
                        $expectedLength, $element->getLength(),
                        "Invariant: Span of any Node is span of child nodes and tokens."
                    );
                }
            }
        }
    }

    public function testParentOfNodeHasSameChildNode() {
        foreach (self::FILENAMES as $filename) {
            $sourceFileNode = $this->parser->parseSourceFile($filename);
            foreach ($sourceFileNode->getAllChildren() as $child) {
                if ($child instanceof Node) {
                    $this->assertContains(
                        $child, $child->parent->children,
                        "Invariant: Parent of Node contains same child node."
                    );
                }
            }
        }
    }

    public function testEachChildHasExactlyOneParent() {
        foreach (self::FILENAMES as $filename) {
            $sourceFileNode = $this->parser->parseSourceFile($filename);

            $treeElements = $sourceFileNode->getAllChildren();
            array_push($treeElements, $sourceFileNode);

            foreach ($sourceFileNode->getAllChildren() as $child) {
                $count = 0;
                foreach ($treeElements as $element) {
                    if ($element instanceof Node) {
                        if (in_array($child, $element->children, true)) {
                            $count++;
                        }
                    }
                }
                $this->assertEquals(
                    1, $count,
                    "Invariant: each child has exactly one parent.");
            }
        }
    }

    public function testRootNodeHasNoParent() {
        foreach (self::FILENAMES as $filename) {
            $sourceFileNode = $this->parser->parseSourceFile($filename);
            $this->assertEquals(
                null, $sourceFileNode->parent,
                "Invariant: Root node of tree has no parent.");
        }
    }

    public function testRootNodeIsNeverAChild() {
        foreach (self::FILENAMES as $filename) {
            $sourceFileNode = $this->parser->parseSourceFile($filename);

            $treeElements = $sourceFileNode->getAllChildren();
            array_push($treeElements, $sourceFileNode);

            foreach($treeElements as $element) {
                if ($element instanceof Node) {
                    $this->assertNotContains(
                        $sourceFileNode, $element->children,
                        "Invariant: root node of tree is never a child.");
                }
            }
        }
    }

    public function testEveryNodeHasAKind() {
        foreach (self::FILENAMES as $filename) {
            $sourceFileNode = $this->parser->parseSourceFile($filename);

            $treeElements = $sourceFileNode->getAllChildren();
            array_push($treeElements, $sourceFileNode);

            foreach($treeElements as $element) {
                if ($element instanceof Node) {
                    $this->assertNotNull(
                        $element->kind,
                        "Invariant: Every Node has a Kind");
                }
            }
        }
    }
}