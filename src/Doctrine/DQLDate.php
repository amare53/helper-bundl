<?php
/**
 * @author Aime Nzolo
 * Created by IntelliJ IDEA.
 * Date: 6/9/21
 * Time: 12:06 AM
 */
namespace Amare53\HelperBundle\Doctrine;

use \Doctrine\ORM\Query\AST\Functions\FunctionNode,
    \Doctrine\ORM\Query\SqlWalker,
    \Doctrine\ORM\Query\Parser,
    \Doctrine\ORM\Query\Lexer;


class DQLDate extends FunctionNode
{
    protected $date;

    public function getSql(SqlWalker $sqlWalker)
    {
        return 'DATE(' . $sqlWalker->walkArithmeticPrimary($this->date) .')';
    }

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->date = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
