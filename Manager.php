<?php

namespace App\DomainObject;

use App\Project\Core;
use App\Project\Component;

class Manager implements Component
{
    /** @var \Doctrine_Manager */
    private $dm;

    /** @var \Doctrine_Connection */
    private $conn;

    /** @var array */
    private $cfg;

    /** @var Manager[] */
    protected static $managers = array();

    const CFG_DOCTRINE = 'doctrine';

    /**
     * @param array $params
     * @return Manager
     * @throws \Exception
     */
    public static function getComponent(array $params = array())
    {
        try {
            $default = array(
                'connection' => array(
                    'protocol' => 'mysql',
                    'hostname' => 'localhost',
                    'port'     => 3306,
                    'database' => null,
                    'username' => 'root',
                    'password' => null,
                    'prefix'   => null,
                    'charset'  => 'utf8'
                ),

                'entity' => array(
                    'dir' => null,
                ),

                'cache' => array(
                    'implrmentation' => null,
                    'query_cache'    => 0,
                    'result_cache'   => 0
                ),

                'generated' => array(
                    'output_path'      => null,
                    'models_path'      => null,
                    'controllers_path' => null,
                    'queries_path'     => null
                ),
            );

            $params = Core::getConfig(self::CFG_DOCTRINE);

            foreach ($default as $k1 => $v1) {
                if (!isset($params[$k1])) {
                    $params[$k1] = array();
                }

                foreach ($v1 as $k2 => $v2) {
                    if (!isset($params[$k1][$k2])) {
                        $params[$k1][$k2] = $v2;
                    }
                }
            }

            spl_autoload_register(array('\Doctrine_Core', 'autoload'));

            $manager = \Doctrine_Manager::getInstance();

            $dsn  = "{$params['connection']['protocol']}:host={$params['connection']['hostname']}; dbname={$params['connection']['database']}; port={$params['connection']['port']}";
            $conn = $manager->openConnection(array($dsn, $params['connection']['username'], $params['connection']['password']), 'main');
            $conn->setOption('username', $params['connection']['username']);
            $conn->setOption('password', $params['connection']['password']);
            $conn->setCharset($params['connection']['charset']);

            $manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);
            $manager->setAttribute(\Doctrine_Core::ATTR_QUOTE_IDENTIFIER, true);
            $manager->setAttribute(\Doctrine_Core::ATTR_HYDRATE_OVERWRITE, false);
            $manager->setAttribute(\Doctrine_Core::ATTR_MODEL_LOADING, \Doctrine_Core::MODEL_LOADING_CONSERVATIVE);
            $manager->setAttribute(\Doctrine_Core::ATTR_AUTO_FREE_QUERY_OBJECTS, true);
            $manager->setAttribute(\Doctrine_Core::ATTR_COLLECTION_CLASS, '\App\DomainObject\Collection');

            if (!empty($params['cache']['implementation'])) {
                $cache = new $params['cache']['implementation']($params['cache']['params']);

                if (!empty($params['cache']['query_cache'])) {
                    $manager->setAttribute(\Doctrine::ATTR_QUERY_CACHE, $cache);
                    $manager->setAttribute(\Doctrine::ATTR_QUERY_CACHE_LIFESPAN, 3600);
                }

                if (!empty($params['cache']['result_cache'])) {
                    $manager->setAttribute(\Doctrine::ATTR_RESULT_CACHE, $cache);
                    $manager->setAttribute(\Doctrine::ATTR_RESULT_CACHE_LIFESPAN, 3600);
                }
            }

            //\Doctrine_Core::loadModels($params['entity']['dir'], null, 'Model_');
            \Doctrine_Core::loadModels($params['entity']['dir']);

            self::setManager('generic', new Manager($manager, $conn, $params));
            return self::getManager('generic');
        } catch (\Exception $e) {
            throw new \Exception('Error occured while init DomainObjectManager component', 0, $e);
        }
    }

    /**
     * @static
     * @param null|string $alias
     * @return Manager
     */
    public static function getManager($alias = 'generic')
    {
        return array_key_exists($alias, self::$managers) ? self::$managers[$alias] : null;
    }

    /**
     * @param string $alias
     * @param Manager $o
     */
    public static function setManager($alias = 'generic', Manager $o)
    {
        self::$managers[$alias] = $o;
    }

    public function __construct(\Doctrine_Manager $dm, \Doctrine_Connection $conn, array $cfg)
    {
        $this->dm   = $dm;
        $this->conn = $conn;
        $this->cfg = $cfg;
    }

    /**
     * Get the current connection instance
     *
     * @throws \Doctrine_Connection_Exception       if there are no open connections
     * @return \Doctrine_Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * beginTransaction
     * Start a transaction or set a savepoint.
     *
     * if trying to set a savepoint and there is no active transaction
     * a new transaction is being started
     *
     * Listeners: onPreTransactionBegin, onTransactionBegin
     *
     * @throws \Doctrine_Transaction_Exception   if the transaction fails at database level
     * @return integer                          current transaction nesting level
     */
    public function beginTransaction()
    {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * commit
     * Commit the database changes done during a transaction that is in
     * progress or release a savepoint. This function may only be called when
     * auto-committing is disabled, otherwise it will fail.
     *
     * Listeners: onPreTransactionCommit, onTransactionCommit
     *
     * @throws \Doctrine_Transaction_Exception   if the transaction fails at PDO level
     * @throws \Doctrine_Validator_Exception     if the transaction fails due to record validations
     * @return boolean                          false if commit couldn't be performed, true otherwise
     */
    public function commit()
    {
        if (!$this->getConnection()->getTransactionLevel()) {
            return false;
        }

        return $this->getConnection()->commit();
    }

    /**
     * rollback
     * Cancel any database changes done during a transaction or since a specific
     * savepoint that is in progress. This function may only be called when
     * auto-committing is disabled, otherwise it will fail. Therefore, a new
     * transaction is implicitly started after canceling the pending changes.
     *
     * this method can be listened with onPreTransactionRollback and onTransactionRollback
     * eventlistener methods
     *
     * @throws \Doctrine_Transaction_Exception   if the rollback operation fails at database level
     * @return boolean                          false if rollback couldn't be performed, true otherwise
     */
    public function rollback()
    {
        if (!$this->getConnection()->getTransactionLevel()) {
            return false;
        }

        return $this->getConnection()->rollback();
    }

    /**
     * flush
     * saves all the records from all tables
     * this operation is isolated using a transaction
     *
     * @throws \PDOException         if something went wrong at database level
     * @return void
     */
    public function flush()
    {
        $this->getConnection()->flush();
    }

    /**
     * clear
     * clears all repositories
     *
     * @param null $entityName
     * @return void
     */
    public function clear($entityName = null)
    {
        $this->getConnection()->clear();
    }

    /**
     * close
     * closes the connection
     *
     * @return void
     */
    public function close()
    {
        $this->getConnection()->close();
    }

    /**
     * @param Model $entity
     * @return void
     */
    public function persist(Model $entity)
    {
        return;
    }

    /**
     * @param Model $entity
     * @return void
     */
    public function remove(Model $entity)
    {
        $entity->remove();
    }

    /**
     * refresh
     * refresh internal data from the database
     *
     * @param Model $entity
     *
     * @throws \Doctrine_Record_Exception        When the refresh operation fails (when the database row
     *                                          this record represents does not exist anymore)
     * @return boolean
     */
    public function refresh(Model $entity)
    {
        $entity->refresh(true);
    }

    /**
     * @param Model $entity
     * @return mixed
     */
    public function detach(Model $entity)
    {
        $entity->getTable()->getRepository()->evictAll();
        $entity->getTable()->clear();
    }

    /**
     * @param Model $entity
     * @return bool
     */
    public function contains(Model $entity)
    {
        return $entity->getTable()->getRepository()->contains($entity->getOid());
    }

    /**
     * Check wherther the connection to the database has been made yet
     *
     * @return boolean
     */
    public function isOpen()
    {
        return $this->conn->isConnected() ? true : false;
    }

    /**
     * @return \Doctrine_Manager
     */
    public function getOriginalManager()
    {
        return $this->dm;
    }

    /**
     * @return \PDO
     */
    public function getWrappedConnection()
    {
        return $this->getConnection()->getDbh();
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->cfg;
    }
}
