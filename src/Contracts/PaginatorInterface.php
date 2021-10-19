<?php
/**
 * @author Aime Nzolo
 * Created by IntelliJ IDEA.
 * Date: 7/9/21
 * Time: 12:06 AM
 */
namespace Amare53\HelperBundle\Contracts;

use Doctrine\ORM\Query;
use Knp\Component\Pager\Pagination\PaginationInterface;

interface PaginatorInterface
{
    public function allowSort(string ...$fields): self;

    public function allowFilter(string ...$fields): self;

    public function paginate(Query $query): PaginationInterface;
}
