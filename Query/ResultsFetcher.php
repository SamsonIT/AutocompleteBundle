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
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param array $searchFields
     * @return \Doctrine\ORM\Query
     */
    public function getResults(Request $request, QueryBuilder $qb, array $searchFields)
    {
        $search = preg_split('/\s+/', trim($request->request->get(Autocomplete::KEY_SEARCH)));
        $this->appendQuery($qb, $search, $searchFields);
        $query = $qb->getQuery();

        return $query;
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
        $query = $this->getResults($request, $qb, $searchFields);
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
                $expressions[] = $qb->expr()->like($field, ':query'.$key);
            }
            $qb->andWhere("(".call_user_func_array(array($qb->expr(), 'orx'), $expressions).")");
            $qb->setParameter('query'.$key, '%'.$searchWord.'%');
        }
    }
}
