<?php

namespace App\DomainObject;

abstract class QueryList extends Query
{
    /**
     * @abstract
     * @param array $params
     * @return QueryBuilder
     */
    abstract public function initByListParams(array $params = array());

    /**
     * @param QueryBuilder $qb
     * @param $offset
     * @param $length
     * @return \App\DomainObject\Model[]|array|mixed
     */
    public function findDataForList(QueryBuilder $qb, $offset, $length)
    {
        $qb->offset($offset)->limit($length);
        return $this->getMultiFound($qb);
    }

    /**
     * @param QueryBuilder $qb
     * @return int|mixed
     */
    public function findCountForList(QueryBuilder $qb)
    {
        return $this->getCount($qb);
    }
}