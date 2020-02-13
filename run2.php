<?php

require_once __DIR__ . '/vendor/autoload.php';

ini_set( 'display_errors', 'stderr' );

use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;

use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\NodeVisitor;
use PhpParser\Node\Scalar;
use PhpParser\Node\Name;

class A implements NodeVisitor
{
    /**
     * @var PDO
     */
    private $db;
    
    /**
     * @var string
     */
    private $filename;

    /**
     * @var null
     */
    private $namespace;

    /**
     * @var null
     */
    private $construct;
    
    public function __construct($db, $filename)
    {
        $this->db = $db;
        $this->filename = $filename;
        $this->namespace = '\\';
    }
    
    public function enterNode(Node $node)
    {
        $method = $node->getType();
        $this->$method($node);
    }
    
    public function leaveNode(Node $node)
    {
        
    }

    private function Stmt_Namespace(Node $node)
    {
        $this->namespace = $node->name . '';
    }
    
    private function Stmt_Function(Function_ $node)
    {
        $functionId = $this->insert('function', [
            'name' => $node->name->name,
            'comment' => $this->getDocBloc($node),
            'scope' => $this->insertscope($node),
            'return' => $this->typeToString($node->getReturnType())
        ]);

        $this->insertParameters('function-parameter', $node, 'function', $functionId);
        
        $node->stmts = [];// no need to traverse the rest
    }

    private function Stmt_Class(Class_ $node)
    {
        $classId = $this->insert('class', [
            'name' => $node->name->name,
            'scope' => $this->insertScope($node),
            'comment' => $this->getDocBloc($node),
            'abstract' => $node->isAbstract() ? 1 : 0,
            'final' => $node->isFinal() ? 1 : 0
        ]);

        $this->insertMethods('class-method', $node, 'class', $classId, 'class-method-parameter', []);

        $node->stmts = [];// no need to traverse the rest
    }

    private function Stmt_Interface(Node $node)
    {
        $interfaceId = $this->insert('interface', [
            'name' => $node->name->name,
            'scope' => $this->insertScope($node),
            'comment' => $this->getDocBloc($node)
        ]);

        $this->insertMethods('interface-method', $node, 'interface', $interfaceId, 'interface-method-parameter', ['static', 'abstract', 'final', 'visibility']);
    }

    private function Stmt_Trait(Node $node)
    {
        
    }

    private function insertMethods(string $table, Node $classLike, string $parentName, int $parentId, string $paramTable, $removeKeys)
    {
        foreach($classLike->getMethods() as $method)
        {
            $values = [
                'name' => $method->name->name,
                $parentName => $parentId,
                'comment' => $this->getDocBloc($method),
                'start' => $method->getStartFilePos(),
                'end' => $method->getEndFilePos(),
                'abstract' => $method->isAbstract() ? 1 : 0,
                'final' => $method->isFinal() ? 1 : 0,
                'static' => $method->isStatic() ? 1 : 0,
                'visibility' => $method->isPublic() ? 'public' : $method->isProtected() ? 'protected' : 'private'
            ];

            foreach($removeKeys as $removeKey)// this is because the interface-methods table does not have visibility etc...
            {
                unset ($values[$removeKey]);
            }

            $methodId = $this->insert($table, $values);
            $this->insertParameters($paramTable, $method, 'method', $methodId);
        }
    }

    private function insertParameters(string $table, $functionLike, string $parentName, int $parentId)
    {
        foreach($functionLike->params as $param)
        {
            $this->insert($table, [
                'name' => $param->var->name . '',
                'type' => $this->typeToString($param->type),
                $parentName => $parentId,
                'default' => $param->default ? $param->default->getType() : null,// @todo
                'variadic' => $param->variadic ? 1 : 0
            ]);
        }
    }

    private function insertScope(Node $node)
    {
        return $this->insert('scope', [
            'filename' => $this->filename,
            'namespace' => $this->namespace,
            'start' => $node->getStartFilePos(),
            'end' => $node->getEndFilePos()
        ]);
    }

    private function insert(string $table, array $values)
    {
        $keys = array_keys($values);
        $colonKeys = array_map(function(string $key){
            return ':' . $key;
        }, $keys);
        $this->db->prepare(sprintf('insert into `%s` (`%s`) values (%s)', $table, join('`, `', $keys), join(', ', $colonKeys)))->execute(array_combine($colonKeys, array_values($values)));

        return $this->db->lastInsertId();
    }

    private function getDocBloc(Node $node)
    {
        return $node->getDocComment() ? $node->getDocComment()->getText() : null;
    }

    private function typeToString($type)
    {
        if($type === null)
        {
            return null;
        }
        if(in_array($type->getType(), ['Identifier', 'Name']))
        {
            return $type . '';
        }

        return $type->getType(); // @todo handle the other cases
    }

    public function __call($a, $b) { /* ignore */ }
    public function beforeTraverse(array $nodes) {}
    public function afterTraverse(array $nodes) {}
}

class B
{
    /**
     * @var PDO
     */
    private $db;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, new PhpParser\Lexer(array(
            'usedAttributes' => array(
                'comments',
                'startLine',
                'endLine',
                'startFilePos',
                'endFilePos'
            )
        )));
        
        $this->parser = $parser; 
    }

    public function run(string $filename)
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new A($this->db, $filename));
        $ast = $this->parser->parse(file_get_contents($filename));
        $ast = $traverser->traverse($ast);
    }
}


(new B(new PDO('mysql:host=127.0.0.1;dbname=emacs-php-server', 'root', 'root')))->run('./test.php');
