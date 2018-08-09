<?php

namespace App\DomainObject;

abstract class Query
{
    /**
     * @param mixed $id
     * @return Model|null
     */
    abstract public function findById($id);

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return new QueryBuilder($this->getDomainObjectManager()->getConnection());
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return \Doctrine::getTable(str_replace('Query', 'Model', get_class($this)))->getTableName();
    }

    /**
     * @return void
     */
    public function truncateTable()
    {
        //todo: удаление таблицы чере пдо
        /** @var $dbo DxDBO_PDO */
        $dbo = DxApp::getComponent(DxConstant_Project::ALIAS_DOMAIN_OBJECT_DBO);
        $dbo->query("TRUNCATE TABLE {$this->getTableName()}");
    }

    /**
     * @param QueryBuilder $qb
     * @param array                    $params
     * @return Model|null
     */
    protected function getSingleFound(QueryBuilder $qb, $params = array())
    {
        if (is_object($m = $qb->fetchOne($params)) && $m instanceof Model) {
            /** @var Model $m */
            return $m;
        }

        return null;
    }

    /**
     * @param QueryBuilder $qb
     * @param array                    $params
     * @return Model[]|array
     */
    protected function &getMultiFound(QueryBuilder $qb, $params = array())
    {
        if (count($result = $qb->execute($params)) && $result instanceof Collection) {
            /** @var Collection $result */
            return $result->getModels();
        } else {
            $result = array();
        }

        return $result;
    }

    /**
     * @param QueryBuilder $qb
     * @param array                    $params
     * @return array
     */
    protected function &getArrayResult(QueryBuilder $qb, $params = array())
    {
        $result = $qb->execute($params, \Doctrine_Core::HYDRATE_ARRAY);
        return $result;
    }

    /**
     * @param QueryBuilder $qb
     * @param array                    $params
     * @return array
     */
    protected function &getScalarResult(QueryBuilder $qb, $params = array())
    {
        $result = $qb->execute($params, \Doctrine_Core::HYDRATE_SCALAR);
        return $result;
    }

    /**
     * @param QueryBuilder $qb
     * @return int
     */
    protected function getCount(QueryBuilder $qb)
    {
        return $qb->count($qb->getParameters());
    }

    /**
     * @return Manager
     */
    protected function getDomainObjectManager()
    {
        return Manager::getManager('generic');
    }
}