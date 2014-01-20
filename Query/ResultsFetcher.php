<?php

namespace Samson\Bundle\AutocompleteBundle\Query;

use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
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
        $search = $request->get(Autocomplete::KEY_SEARCH, '');
        if (strlen($search)) {
            $search = preg_split('/\s+/', trim($search));
        } else {
            $search = array();
        }
        return $this->getResultsByArray($search, $request->get(Autocomplete::KEY_PAGE, 1), $qb, $searchFields);
    }

    public function getResultsByArray(array $search, $page, QueryBuilder $qb, array $searchFields)
    {
        $this->appendQuery($qb, $search, $searchFields);
        $qb->setMaxResults(10);
        $qb->setFirstResult(($page - 1) * 10);
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
        $pageSize = $request->get(Autocomplete::KEY_LIMIT, 10);
        $page = $request->get(Autocomplete::KEY_PAGE, 1);

        $query->setFirstResult(($page - 1) * $pageSize);
        $query->setMaxResults($pageSize);

//        return new Paginator($query);
        return $query->getQuery()->execute();
    }

    private function appendQuery(QueryBuilder $qb, array $searchWords, array $searchFields)
    {
        if (count($searchWords)) {
            $qb->andWhere()->like()->field($searchFields[0])->literal('%'.$searchWords[0].'%');
        }

        return;

        foreach ($searchWords as $key => $searchWord) {
            $expressions = array();

            foreach ($searchFields as $key2 => $field) {
                $expressions[] = $qb->expr()->like($field, ':query' . $key . $key2);
                $qb->setParameter('query' . $key . $key2, '%' . $searchWord . '%');
            }
            $qb->andWhere("(" . call_user_func_array(array($qb->expr(), 'orx'), $expressions) . ")");
        }
    }
}
