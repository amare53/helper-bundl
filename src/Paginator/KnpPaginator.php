<?php
/**
 * @author Aime Nzolo
 * Created by IntelliJ IDEA.
 * Date: 6/9/21
 * Time: 11:43 AM
 */

namespace Amare53\HelperBundle\Paginator;

use Amare53\HelperBundle\Contracts\PaginatorInterface;
use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\RequestStack;
use \Knp\Component\Pager\Pagination\PaginationInterface;

class KnpPaginator implements PaginatorInterface
{
    private \Knp\Component\Pager\PaginatorInterface $paginator;

    private RequestStack $requestStack;

    private array $sortableFields = [];
    private array $filterFields = [];

    public function __construct(\Knp\Component\Pager\PaginatorInterface $paginator, RequestStack $requestStack)
    {
        $this->paginator = $paginator;
        $this->requestStack = $requestStack;
    }

    final public function paginate(Query $query): PaginationInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        $page = $request ? $request->query->getInt('page', 1) : 1;

        if ($page <= 0) {
            throw new PageOutOfBoundException();
        }

        return $this->paginator->paginate($query, $page, $query->getMaxResults() ?: 15, [
            'sortFieldAllowList' => $this->sortableFields,
            'filterFieldAllowList' => $this->filterFields,
        ]);
    }

    final public function allowSort(string ...$fields): self
    {
        $this->sortableFields = array_merge($this->sortableFields, $fields);

        return $this;
    }

    final public function allowFilter(string ...$fields): self
    {
        $this->filterFields = array_merge($this->filterFields, $fields);
        return $this;
    }
}
