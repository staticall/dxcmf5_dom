<?php

namespace App\DomainObject;

interface Tree
{
    /**
     * Fetches a/the root node.
     *
     * @param int|null $root_id
     */
    public function fetchRoot($root_id = null);

    /**
     * Fetches the complete tree, returning the root node of the tree
     *
     * @param mixed $root_id the root id of the tree (or null if model doesn't
     *                       support multiple trees
     * @param int   $depth   the depth to retrieve or null for unlimited
     * @return \App\DomainObject\Node|bool $root
     */
    public function fetchTree($root_id = null, $depth = null);

    /**
     * Fetches the complete tree, returning a flat array of node wrappers with
     * parent, children, ancestors and descendants pre-populated.
     *
     * @param mixed $root_id the root id of the tree (or null if model doesn't
     *                       support multiple trees
     * @param int   $depth   the depth to retrieve or null for unlimited
     *
     * @return \App\DomainObject\Node[]|bool
     */
    public function fetchTreeAsArray($root_id = null, $depth = null);

    /**
     * Fetches a branch of a tree, returning the starting node of the branch.
     * All children and descendants are pre-populated.
     *
     * @param mixed $pk    the primary key used to locate the node to traverse
     *                     the tree from
     * @param int   $depth the depth to retrieve or null for unlimited
     *
     * @return \App\DomainObject\Node $root_branch
     */
    public function fetchBranch($pk, $depth = null);

    /**
     * Fetches a branch of a tree, returning a flat array of node wrappers with
     * parent, children, ancestors and descendants pre-populated.
     *
     * @param mixed $pk    the primary key used to locate the node to traverse
     *                     the tree from
     * @param int   $depth the depth to retrieve or null for unlimited
     *
     * @return \App\DomainObject\Node[]|bool
     */
    public function fetchBranchAsArray($pk, $depth = null);

    /**
     * Creates a new root node
     *
     * @param \App\DomainObject\Model $node
     * @return \App\DomainObject\Node
     */
    public function createRoot(\App\DomainObject\Model $node);

    /**
     * Wraps the node using the DomainObjectNode class
     *
     * @param \App\DomainObject\Model $node
     * @return \App\DomainObject\Node
     */
    public function wrapNode(\App\DomainObject\Model $node);

    /**
     * Resets the manager. Clears DomainObjectNode caches.
     */
    public function reset();

    /**
     * Returns the DomainObjectManager associated with this Manager
     *
     * @return \App\DomainObject\Manager
     */
    public function getDomainObjectManager();

    /**
     * @return \Doctrine_Tree
     */
    public function getTreeManager();

    /**
     * @param \App\DomainObject\Manager $dom
     */
    public function setDomainObjectManager(\App\DomainObject\Manager $dom);

    /**
     * @param \Doctrine_Tree $nsm
     */
    public function setTreeManager(\Doctrine_Tree $nsm);
}
