<?php

require_once 'vendor/autoload.php';

use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;

use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\NodeVisitor;
use PhpParser\Node\Scalar;

function dumpType($node)
{
    return $node->getType();
}

class YoVisitor implements NodeVisitor
{
    /**
     *
     * @param XMLWriter
     */
    private $writer;

    public function __construct()
    {
        $this->writer = new XMLWriter();
        $this->writer->openMemory();
        $this->writer->setIndent(1);
        $this->writer->setIndentString(' ');
        $this->writer->startDocument('1.0');
        $this->writer->startElement('root');
    }

    private function attr(string $name, string $value)
    {
        $this->writer->startAttribute($name);
        $this->writer->text($value);
        $this->writer->endAttribute();
    }
    
    public function enterNode(Node $node)
    {
        $type = $node->getType();
        $this->writer->startElement($type);
        $this->addSpecialAttributes($node);
        $this->attr('start', $node->getStartFilePos());
        $this->attr('end', $node->getEndFilePos());
        
        // echo "enter\n";
        // echo dumpType($node);
        // echo "\n---\n";
        // var_dump($node->getType());
        // var_dump(substr($code, $node->getStartFilePos(), $node->getEndFilePos() +1 - $node->getStartFilePos()));
    }

    public function leaveNode(Node $node)
    {
        $this->writer->endElement();
    }
    
    public function beforeTraverse(array $nodes)
    {
        // echo "before[]\n";
        // var_dump(array_map('dumpType', $nodes));
        // echo "---\n";
        return null;
    }

    public function afterTraverse(array $nodes)
    {
        // echo "after[]\n";
        // var_dump(array_map('dumpType', $nodes));
        // echo "---\n";
        return null;
    }

    public function finish()
    {
        $this->writer->endElement();
        $this->writer->endDocument();
        return $this->writer->outputMemory();
    }

    private function addSpecialAttributes(Node $node)
    {
        $name = $node->name ?? '';
        if($name !== '')
        {
            $this->attr('name', $name);
        }
        if($node instanceof Scalar)
        {
            $this->attr('value', $node->value);
        }
        
    }
}

function yo($code, $ast)
{
    $yo = new YoVisitor();

    $traverser = new NodeTraverser();
    $traverser->addVisitor($yo);
    $ast = $traverser->traverse($ast);
    
    return $yo->finish();
}

function init()
{
    $lexer = new PhpParser\Lexer(array(
        'usedAttributes' => array(
            'comments',
            'startLine',
            'endLine',
            'startFilePos',
            'endFilePos'
        )
    ));

    $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer);

    return $parser;
}

// $stdin = fopen('php://stdin', 'r');
// echo (int)$argv[1] . "\n";
$code = stream_get_contents(STDIN);// fread($stdin, (int)$argv[1]);
//$code = file_get_contents('test.php');
$parser = init();
try
{
    $ast = $parser->parse($code);
}
catch (Error $error)
{
    echo "Parse error: {$error->getMessage()}\n";
    return;
}

// $dumper = new NodeDumper();
// echo $dumper->dump($ast) . "\n";

echo yo($code, $ast);
