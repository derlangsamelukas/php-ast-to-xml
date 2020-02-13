<?php

require_once __DIR__ . '/vendor/autoload.php';

ini_set( 'display_errors', 'stderr' );

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
use PhpParser\Node\Name;

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
        $this->attr('comment', $node->getDocComment() ?? '');
        
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

    /**
     *
     * @param Node $node
     */
    private function addSpecialAttributes(Node $node)
    {
        $name = $node->name ?? '';
        if($name !== '')
        {
            while(\is_object($name) && property_exists($name, 'name'))
            {
                $name = $name->name;
            }
            if(\is_string($name) || $name->getType() !== 'Expr_Closure')
            {
                $this->attr('name', $name);
            }
        }
        if($node instanceof Scalar && property_exists($node, 'value'))
        {
            $this->attr('value', $node->value);
        }
        if($node instanceof Name)
        {
            $this->attr('parts', join('\\', $node->parts));
        }
    }
}

function yo($ast)
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

// $code = stream_get_contents(STDIN);// fread($stdin, (int)$argv[1]);
$parser = init();

$nextLine = false;
while(false !== ($nextLine = fgets(STDIN)))
{
    $length = (int)trim($nextLine);
    // printf("reading now: %d\n", $length);
    $code = '';
    do
    {
        $x = fread(STDIN, $length - strlen($code));
        if($x === false)
        {
            throw new \Exception('fread returned false');
        }
        $code .= $x;
        // printf('waiting for %d bytes' . "\n", $length - strlen($code));
        // $length -= strlen($code);
    } while(strlen($code) < $length);
    if(strlen($code) > $length)
    {
        throw new \Exception('fread read more than the requested bytes');
    }
    // $code = stream_get_contents(STDIN, $length);
    // printf("read %d bytes\n", strlen($code));
    try
    {
        $result = yo($parser->parse($code));
        printf("%d ok\n", strlen($result));
        echo $result;
    }
    catch(Error $error)
    {
        printf("%d error\n", strlen($error->getMessage()));
        echo $error->getMessage();
    }
}

// echo yo($ast);
