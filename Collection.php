<?php

namespace App\DomainObject;

class Collection extends \Doctrine_Collection
{
    /**
     * @param string $model
     */
    public function __construct($model)
    {
        parent::__construct($model);
    }

    /**
     * @return array|Model[]
     */
    public function &getModels()
    {
        //todo: упростить метод
        $models = array();

        if ($this->count()) {
            /** @var Model $record */
            foreach ($this->data as &$record) {
                $models[] = $record;
            }
        }

        return $models;
    }

    public function __destruct()
    {
        $this->clear();
    }
}