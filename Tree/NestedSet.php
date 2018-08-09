<?php

namespace App\DomainObject\Tree;

use App\DomainObject\Tree;
use App\Project\Exception\LogicException;

class NestedSet implements Tree
{
    /** @var string */
    protected $dom_class;

    /** @var \App\DomainObject\Manager */
    protected $dom;

    /** @var \Doctrine_Tree_NestedSet */
    protected $nsm;

    /** @var \DoctrineObjectNode_NestedSet[] */
    protected $wrappers = array();

    /**
     * @param $dom_class
     * @param \App\DomainObject\Manager $dom
     * @param array $cfg
     */
    public function __construct($dom_class, \App\DomainObject\Manager $dom, $cfg = array())
    {
        /** @var $tree \Doctrine_Tree_NestedSet  */
        $tree = \Doctrine::getTable($dom_class)->getTree();

        $this->setDomainObjectClass($dom_class);
        $this->setTreeManager($tree);
        $this->setDomainObjectManager($dom);
    }

    /**
     * Fetches a/the root node.
     *
     * @param int|null $root_id
     * @return null|\App\DomainObject\Node\NestedSet
     */
    public function fetchRoot($root_id = null)
    {
        $root = null;
        if ($root_id === null) {
            $root = \Doctrine::getTable($this->getDomainObjectClass())->findOneByLft(1);
        }

        if (empty($root)) {
            return null;
        } else {
            return $this->wrapNode($root);
        }
    }

    /**
     * Fetches the complete tree, returning the root node of the tree
     *
     * @param mixed $root_id the root id of the tree (or null if model doesn't
     *                       support multiple trees
     * @param int   $depth   the depth to retrieve or null for unlimited
     * @return \App\DomainObject\Node\NestedSet|bool $root
     */
    public function fetchTree($root_id = null, $depth = null)
    {
        return ($res = $this->fetchTreeAsArray($root_id, $depth)) === false ? false : $res[0];
    }

    /**
     * Fetches the complete tree, returning a flat array of node wrappers with
     * parent, children, ancestors and descendants pre-populated.
     *
     * @param mixed $root_id the root id of the tree (or null if model doesn't
     *                       support multiple trees
     * @param int   $depth   the depth to retrieve or null for unlimited
     *
     * @return \App\DomainObject\Node\NestedSet[]|bool
     */
    public function fetchTreeAsArray($root_id = null, $depth = null)
    {
        $options = array(
            'root_id' => $root_id,
            'depth'   => $depth
        );

        /** @var $tree \Doctrine_Collection|bool */
        if (($tree = $this->getTreeManager()->fetchTree($options)) === false) {
            return false;
        }

        /** @var $result array */
        $result = array();

        /** @var $record \App\DomainObject\Model */
        foreach ($tree as $record) {
            $result[] = $this->wrapNode($record);
        }

        return $result;
    }

    /**
     * Fetches a branch of a tree, returning the starting node of the branch.
     * All children and descendants are pre-populated.
     *
     * @param mixed $pk    the primary key used to locate the node to traverse
     *                     the tree from
     * @param int   $depth the depth to retrieve or null for unlimited
     *
     * @return \App\DomainObject\Node\NestedSet $root_branch
     */
    public function fetchBranch($pk, $depth = null)
    {
        return ($res = $this->fetchBranchAsArray($pk, $depth)) === false ? false : $res[0];
    }

    /**
     * Fetches a branch of a tree, returning a flat array of node wrappers with
     * parent, children, ancestors and descendants pre-populated.
     *
     * @param mixed $pk    the primary key used to locate the node to traverse
     *                     the tree from
     * @param int   $depth the depth to retrieve or null for unlimited
     *
     * @return \App\DomainObject\Node\NestedSet[]|bool
     */
    public function fetchBranchAsArray($pk, $depth = null)
    {
        $options = array(
            'depth'   => $depth
        );

        /** @var $tree \Doctrine_Collection|bool */
        if (($tree = $this->getTreeManager()->fetchBranch($pk, $options)) === false) {
            return false;
        }

        /** @var $result array */
        $result = array();

        /** @var $record \App\DomainObject\Model */
        foreach ($tree as $record) {
            $result[] = $this->wrapNode($record);
        }

        return $result;
    }

    /**
     * Creates a new root node
     *
     * @param \App\DomainObject\Model $node
     * @return \App\DomainObject\Node\NestedSet
     */
    public function createRoot(\App\DomainObject\Model $node)
    {
        /** @var $m \App\DomainObject\Model */
        $m = $this->getTreeManager()->createRoot($node);

        return $this->wrapNode($m);
    }

    /**
     * Wraps the node using the \App\DomainObject\Node\NestedSet class
     *
     * @param \App\DomainObject\Model $node
     * @return \App\DomainObject\Node\NestedSet
     */
    public function wrapNode(\App\DomainObject\Model $node)
    {
        $oid = spl_object_hash($node);
        if (!isset($this->wrappers[$oid]) || $this->wrappers[$oid]->getNode() !== $node) {
            $this->wrappers[$oid] = new \App\DomainObject\Node\NestedSet($node, $this);
        }

        return $this->wrappers[$oid];
    }

    /**
     * Resets the manager. Clears \App\DomainObject\Node\NestedSet caches.
     */
    public function reset()
    {
        $this->wrappers = array();
    }

    /**
     * @param string $dom_class
     */
    public function setDomainObjectClass($dom_class)
    {
        $this->dom_class = $dom_class;
    }

    /**
     * @param \App\DomainObject\Manager $dom
     */
    public function setDomainObjectManager(\App\DomainObject\Manager $dom)
    {
        $this->dom = $dom;
    }

    /**
     * @param \Doctrine_Tree $nsm
     * @throws \App\Project\Exception\LogicException
     */
    public function setTreeManager(\Doctrine_Tree $nsm)
    {
        if (!($nsm instanceof \Doctrine_Tree_NestedSet)) {
            throw new LogicException('Invalid nested set manager');
        }

        $this->nsm = $nsm;
    }

    /**
     * @return string
     */
    public function getDomainObjectClass()
    {
        return $this->dom_class;
    }

    /**
     * Returns the DomainObjectManager associated with this Manager
     *
     * @return \App\DomainObject\Manager
     */
    public function getDomainObjectManager()
    {
        return $this->dom;
    }

    /**
     * @return \Doctrine_Tree_NestedSet
     */
    public function getTreeManager()
    {
        return $this->nsm;
    }
}