<?php

namespace Samson\Bundle\AutocompleteBundle\Query;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Samson\Bundle\AutocompleteBundle\Autocomplete\Autocomplete;
use Symfony\Component\HttpFoundation\Request;

class ResultsFetcher
{

    /**
     *
     * @deprecated( 'Use getResultsByRequest' )
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param array $searchFields
     * @return \Doctrine\ORM\Query
     */
    public function getResults(Request $request, QueryBuilder $qb, array $searchFields)
    {
        return $this->getResultsByRequest($request, $qb, $searchFields)->getQuery();
    }

    public function getResultsByRequest(Request $request, QueryBuilder $qb, array $searchFields)
    {
        $search = preg_split('/\s+/', trim($request->request->get(Autocomplete::KEY_SEARCH)));
        return $this->getResultsByArray($search, $qb, $searchFields);
    }

    public function getResultsByArray(array $search, QueryBuilder $qb, array $searchFields)
    {
        $this->appendQuery($qb, $search, $searchFields);
        return $qb;
    }

    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param array $searchFields
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getPaginatedResults(Request $request, QueryBuilder $qb, array $searchFields)
    {
        $query = $this->getResultsByRequest($request, $qb, $searchFields);
        $pageSize = $request->request->get(Autocomplete::KEY_LIMIT, 10);
        $page = $request->request->get(Autocomplete::KEY_PAGE, 1);

        $query->setFirstResult(($page - 1) * $pageSize);
        $query->setMaxResults($pageSize);

        return new Paginator($query);
    }

    private function appendQuery(QueryBuilder $qb, array $searchWords, array $searchFields)
    {
        foreach ($searchWords as $key => $searchWord) {
            $expressions = array();

            foreach ($searchFields as $field) {
                $expressions[] = $qb->expr()->like($field, ':query' . $key);
            }
            $qb->andWhere("(" . call_user_func_array(array($qb->expr(), 'orx'), $expressions) . ")");
            $qb->setParameter('query' . $key, '%' . $searchWord . '%');
        }
    }
}
