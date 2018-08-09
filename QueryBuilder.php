<?php

namespace {
    class Doctrine_Query_Builder extends \Doctrine_Query
    {
        /**
         * @var array $_params  The parameters of this query.
         */
        protected $_params = array(
            'exec'         => array(),
            'join'         => array(),
            'where'        => array(),
            'set'          => array(),
            'having'       => array(),
            'placeholders' => array()
        );

        /**
         * @param array $params
         * @return DomainObjectQueryBuilder
         */
        public function setParameters(array &$params = array())
        {
            $_params                 = $this->getParams();
            $_params['placeholders'] = array_merge($_params['placeholders'], $params);
            $this->setParams($_params);

            return $this;
        }

        /**
         * @return array
         */
        public function getParameters()
        {
            return $this->getFlattenedParams();
        }

        /**
         * Get flattened array of parameters for query.
         * Used internally and used to pass flat array of params to the database.
         *
         * @param array $params
         * @return array
         */
        public function getFlattenedParams($params = array())
        {
            return array_merge(
                (array)$params,
                (array)$this->_params['exec'],
                (array)$this->_params['placeholders'],
                $this->_params['join'], $this->_params['set'],
                $this->_params['where'], $this->_params['having']
            );
        }
    }
}

namespace App\DomainObject {

    class QueryBuilder
    {
        /**
         * @var \Doctrine_Query $q
         */
        protected $q = null;

        public function __construct(\Doctrine_Connection $connection)
        {
            $this->q = new \Doctrine_Query_Builder($connection);
        }

        public function __clone() {
            $this->q = clone $this->q;
        }

        /**
         * @var array $_params  The parameters of this query.
         */

        /**
         * Specifies an item that is to be returned in the query result.
         * Replaces any previously specified selections, if any.
         *
         * <code>
         *     $qb = $em->createQueryBuilder()
         *         ->select('u', 'p')
         *         ->from('User', 'u')
         *         ->leftJoin('u.Phonenumbers', 'p');
         * </code>
         *
         * @param mixed $select The selection expressions.
         * @return QueryBuilder This DomainObjectQueryBuilder instance.
         */
        public function select($select)
        {
            if (empty($select)) {
                throw new \Exception("Select can't be empty");
            } else {
                $selects = is_array($select) ? $select : func_get_args();
                foreach ($selects as $k => $v) {
                    if (strpos($v, '.') === false && strpos(mb_strtolower($v), ' as ') === false) {
                        $selects[$k] = "{$v}.*";
                    }
                }

                $this->q->select(implode(', ', $selects));
                return $this;
            }
        }

        /**
         * Turns the query being built into a bulk delete query that ranges over
         * a certain entity type.
         *
         * <code>
         *     $qb = $em->createQueryBuilder()
         *         ->delete('User', 'u')
         *         ->where('u.id = :user_id');
         *         ->setParameter(':user_id', 1);
         * </code>
         *
         * @param string $delete The class/type whose instances are subject to the deletion.
         * @param string $alias  The class/type alias used in the constructed query.
         * @return QueryBuilder This DomainObjectQueryBuilder instance.
         */
        public function delete($delete = null, $alias = null)
        {
            if ($delete && $alias) {
                $delete .= " {$alias}";
            }

            $this->q->delete($delete);
            return $this;
        }

        /**
         * Turns the query being built into a bulk update query that ranges over
         * a certain entity type.
         *
         * <code>
         *     $qb = $em->createQueryBuilder()
         *         ->update('User', 'u')
         *         ->set('u.password', md5('password'))
         *         ->where('u.id = ?');
         * </code>
         *
         * @param string $update The class/type whose instances are subject to the update.
         * @param string $alias  The class/type alias used in the constructed query.
         * @return QueryBuilder This DomainObjectQueryBuilder instance.
         */
        public function update($update = null, $alias = null)
        {
            if ($update && $alias) {
                $update .= " {$alias}";
            }

            $this->q->update($update);
            return $this;
        }

        /**
         * Create and add a query root corresponding to the entity identified by the given alias,
         * forming a cartesian product with any existing query roots.
         *
         * <code>
         *     $qb = $em->createQueryBuilder()
         *         ->select('u')
         *         ->from('User', 'u')
         * </code>
         *
         * @param string $from   The class name.
         * @param string $alias  The alias of the class.
         * @return QueryBuilder This DomainObjectQueryBuilder instance.
         */
        public function from($from, $alias)
        {
            $this->q->from("{$from} {$alias}");
            return $this;
        }

        /**
         * Creates and adds a join over an entity association to the query.
         *
         * The entities in the joined association will be fetched as part of the query
         * result if the alias used for the joined association is placed in the select
         * expressions.
         *
         * <code>
         *     $qb = $em->createQueryBuilder()
         *         ->select('u')
         *         ->from('User', 'u')
         *         ->join('u.Phonenumbers', 'p', Expr\Join::WITH, 'p.is_primary = 1');
         * </code>
         *
         * @param string $join          The relationship to join
         * @param string $alias         The alias of the join
         * @param string $conditionType The condition type constant. Either ON or WITH.
         * @param string $condition     The condition for the join
         * @param string $indexBy       The index for the join
         * @return QueryBuilder This DomainObjectQueryBuilder instance.
         */
        public function join($join, $alias, $conditionType = null, $condition = null, $indexBy = null)
        {
            $this->q->innerJoin($join, $alias, $conditionType, $condition, $indexBy);
            return $this;
        }

        /**
         * Creates and adds a join over an entity association to the query.
         *
         * The entities in the joined association will be fetched as part of the query
         * result if the alias used for the joined association is placed in the select
         * expressions.
         *
         *     [php]
         *     $qb = $em->createQueryBuilder()
         *         ->select('u')
         *         ->from('User', 'u')
         *         ->innerJoin('u.Phonenumbers', 'p', 'WITH', 'p.is_primary = 1');
         *
         * @param string $join          The relationship to join
         * @param string $alias         The alias of the join
         * @param string $conditionType The condition type constant. Either ON or WITH.
         * @param string $condition     The condition for the join
         * @param string $indexBy       The index for the join
         * @return QueryBuilder This DomainObjectQueryBuilder instance.
         */
        public function innerJoin($join, $alias, $conditionType = null, $condition = null, $indexBy = null)
        {
            $this->q->innerJoin("{$join} {$alias} {$conditionType} {$condition}");
            return $this;
        }

        /**
         * Creates and adds a join over an entity association to the query.
         *
         * The entities in the joined association will be fetched as part of the query
         * result if the alias used for the joined association is placed in the select
         * expressions.
         *
         *     [php]
         *     $qb = $em->createQueryBuilder()
         *         ->select('u')
         *         ->from('User', 'u')
         *         ->leftJoin('u.Phonenumbers', 'p', 'WITH', 'p.is_primary = 1');
         *
         * @param string $join          The relationship to join
         * @param string $alias         The alias of the join
         * @param string $conditionType The condition type constant. Either ON or WITH.
         * @param string $condition     The condition for the join
         * @param string $indexBy       The index for the join
         * @return QueryBuilder This DomainObjectQueryBuilder instance.
         */
        public function leftJoin($join, $alias, $conditionType = null, $condition = null, $indexBy = null)
        {
            $this->q->leftJoin("{$join} {$alias} {$conditionType} {$condition}");
            return $this;
        }

        /**
         * @param string $field
         * @param array  $params
         * @return QueryBuilder
         */
        public function whereIn($field, array &$params = array())
        {
            $this->q->andWhere($this->whereInExpression($field, $params));
            return $this;
        }

        /**
         * @param string $field
         * @param array  $params
         * @return QueryBuilder
         */
        public function andWhereIn($field, array &$params = array())
        {
            $this->q->andWhere($this->whereInExpression($field, $params));
            return $this;
        }

        /**
         * @param string $field
         * @param array  $params
         * @return QueryBuilder
         */
        public function whereNotIn($field, array &$params = array())
        {
            $this->q->andWhere($this->whereInExpression($field, $params, true));
            return $this;
        }

        /**
         * @param       $field
         * @param array $params
         * @return QueryBuilder
         */
        public function andWhereNotIn($field, array &$params = array())
        {
            $this->q->andWhere($this->whereInExpression($field, $params, true));
            return $this;
        }

        /**
         * @param       $field
         * @param array $params
         * @return QueryBuilder
         */
        public function orWhereIn($field, array &$params = array())
        {
            $this->q->orWhere($this->whereInExpression($field, $params));
            return $this;
        }

        /**
         * @param       $field
         * @param array $params
         * @return QueryBuilder
         */
        public function orWhereNotIn($field, array &$params = array())
        {
            $this->q->orWhere($this->whereInExpression($field, $params, true));
            return $this;
        }

        /**
         * @param $where
         * @param array $params
         * @return QueryBuilder
         */
        public function andWhere($where, $params = array())
        {
            $this->q->andWhere($where, $params);
            return $this;
        }

        /**
         * @param $where
         * @param array $params
         * @return QueryBuilder
         */
        public function orWhere($where, $params = array())
        {
            $this->q->orWhere($where, $params);
            return $this;
        }

        /**
         * @param $key
         * @param null $value
         * @param null $params
         * @return QueryBuilder
         */
        public function set($key, $value = null, $params = null)
        {
            $this->q->set($key, $value, $params);
            return $this;
        }

        /**
         * Specifies an ordering for the query results.
         * Replaces any previously specified orderings, if any.
         *
         * @param string $sort  The ordering expression.
         * @param string $order The ordering direction.
         * @return QueryBuilder This DomainObjectQueryBuilder instance.
         */
        public function orderBy($sort, $order = null)
        {
            if ($order !== null) {
                $orderby = $sort . ' ' . $order;
            } else {
                $orderby = $sort;
            }

            $this->q->orderBy($orderby);
            return $this;
        }

        /**
         * Adds an ordering to the query results.
         *
         * @param string $sort The ordering expression.
         * @param string $order The ordering direction.
         * @return QueryBuilder This DomainObjectQueryBuilder instance.
         */
        public function addOrderBy($sort, $order = null)
        {
            if ($order !== null) {
                $orderby = $sort . ' ' . $order;
            } else {
                $orderby = $sort;
            }

            $this->q->addOrderBy($orderby);
            return $this;
        }

        /**
         * Specifies grouping for the query results.
         * Replaces any previously specified grouping, if any.
         *
         * @param string $group  The grouping expression.
         * @return DomainObjectQueryBuilder This DomainObjectQueryBuilder instance.
         */
        public function groupBy($group)
        {
            $this->q->groupBy($group);
            return $this;
        }
        
        /**
         * @return string
         */
        public function getSql()
        {
            return $this->q->getSqlQuery();
        }

        /**
         * @return string
         */
        public function getDQL()
        {
            return $this->q->getDql();
        }

        /**
         * @param string $field
         * @param array  $params
         * @param bool   $not
         * @return string
         */
        protected function whereInExpression($field, array &$params = array(), $not = false)
        {
            return $field . ' ' . ($not ? 'NOT IN' : 'IN') . ' (' . implode(', ', array_fill(0, count($params), '?')) . ')';
        }

        /**
         * @param $where
         * @param mixed $params        an array of parameters or a simple scalar
         * @return \Doctrine_Query
         */
        public function where($where, $params = array())
        {
            $this->q->where($where, $params);
            return $this;
        }

        /**
         * @param array|string $params Query parameters
         * @param int $hydrationMode    Hydration mode: see Doctrine_Core::HYDRATE_* constants
         * @return mixed                Array or \Doctrine_Collection, depending on hydration mode. False if no result.
         */
        public function fetchOne($params = array(), $hydrationMode = null)
        {
            return $this->q->fetchOne($params, $hydrationMode);
        }

        /**
         * @param array $params
         * @param null $hydrationMode
         * @return \Doctrine_Collection            the root collection
         */
        public function execute($params = array(), $hydrationMode = null)
        {
            return $this->q->execute($params, $hydrationMode);
        }

        /**
         * @param array $params        an array of prepared statement parameters
         * @return integer             the count of this query
         */
        public function count($params = array())
        {
            return $this->q->count($params);
        }

        /**
         * @param integer $limit        limit to be used for limiting the query results
         * @return \Doctrine_Query
         */
        public function limit($limit)
        {
            $this->q->limit($limit);
            return $this;
        }

        /**
         * @param integer $offset       offset to be used for paginating the query
         * @return \Doctrine_Query
         */
        public function offset($offset)
        {
            $this->q->offset($offset);
            return $this;
        }

        /**
         * @return array
         */
        public function getParameters()
        {
            return $this->q->getFlattenedParams();
        }

        /**
         * @param array $params
         * @return QueryBuilder
         */
        public function setParameters(array &$params = array())
        {
            $this->q->setParameters($params);
            return $this;
        }
    }
}