<?php

namespace App\DomainObject\Node;

class NestedSet implements \App\DomainObject\Node
{
    /**
     * @var \App\DomainObject\Model The wrapped Node
     **/
    private $node;

    /** @var \App\DomainObject\Tree\NestedSet */
    private $tree;

    /** @var \Doctrine_Node_NestedSet */
    private $dn;

    /**
     * @param \App\DomainObject\Model     $node
     * @param \App\DomainObject\Tree\NestedSet $tree
     */
    public function __construct(\App\DomainObject\Model $node, \App\DomainObject\Tree\NestedSet $tree)
    {
        $this->node = $node;
        $this->tree = $tree;
        $this->dn   = $node->getNode();
    }

    /**
     * Gets first child or null
     *
     * @return \App\DomainObject\Node\NestedSet|null
     */
    public function getFirstChild()
    {
        /** @var $result \App\DomainObject\Model */
        $result = $this->dn->getFirstChild();

        if (!$result) {
            return null;
        }

        return $this->getTree()->wrapNode($result);
    }

    /**
     * Gets last child or null
     *
     * @return \App\DomainObject\Node\NestedSet|null
     */
    public function getLastChild()
    {
        /** @var $result \App\DomainObject\Model */
        $result = $this->dn->getLastChild();

        if (!$result) {
            return null;
        }

        return $this->getTree()->wrapNode($result);
    }

    /**
     * Gets descendants for this node
     *
     * @param int $depth or null for unlimited depth
     * @return \App\DomainObject\Node\NestedSet[]
     */
    public function getDescendants($depth = null)
    {
        if (!$this->hasChildren()) {
            return array();
        }

        /** @var $results \App\DomainObject\Model[] */
        $results = $this->dn->getDescendants($depth);

        if (!$results) {
            $results = array();
        }

        $descendants = array();
        foreach ($results as $result) {
            $descendants[] = $this->getTree()->wrapNode($result);
        }

        return $descendants;
    }

    /**
     * Gets children of this node (direct descendants only)
     *
     * @return \App\DomainObject\Node\NestedSet[]
     */
    public function getChildren()
    {
        return $this->getDescendants(1);
    }

    /**
     * Gets parent \App\DomainObject\Node\NestedSet or null
     *
     * @return \App\DomainObject\Node\NestedSet
     */
    public function getParent()
    {
        if ($this->isRoot()) {
            return null;
        }

        /** @var $p \App\DomainObject\Model */
        $p = $this->dn->getParent();
        if (!$p) {
            return null;
        }

        return $this->getTree()->wrapNode($p);
    }

    /**
     * Gets ancestors for node
     *
     * @return \App\DomainObject\Node\NestedSet[]
     */
    public function getAncestors()
    {
        if ($this->isRoot()) {
            return array();
        }

        /** @var $results \App\DomainObject\Model[] */
        $results = $this->dn->getAncestors();

        if (!$results) {
            $results = array();
        }

        $ancestors = array();
        foreach ($results as $result) {
            $ancestors[] = $this->getTree()->wrapNode($result);
        }

        return $ancestors;
    }


    /**
     * Gets the level of this node
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->dn->getLevel();
    }

    /**
     * gets path to node from root, uses Node::toString() method to get node
     * names
     *
     * @param string $separator   path separator
     * @param bool   $includeNode whether or not to include node at end of path
     *
     * @return string string representation of path
     */
    public function getPath($separator = ' > ', $includeNode = false)
    {
        return $this->dn->getPath($separator, $includeNode);
    }

    /**
     * Gets number of children (direct descendants)
     *
     * @return int
     */
    public function getNumberChildren()
    {
        return $this->dn->getNumberChildren();
    }

    /**
     * Gets number of descendants (children and their children ...)
     *
     * @return int
     */
    public function getNumberDescendants()
    {
        return $this->dn->getNumberDescendants();
    }

    /**
     * Gets siblings for node
     *
     * @param bool $includeNode whether to include this node in the list of
     *                          sibling nodes (default: false)
     *
     * @return \App\DomainObject\Node\NestedSet[]
     */
    public function getSiblings($includeNode = false)
    {
        $siblings = array();

        /** @var $results \App\DomainObject\Model[] */
        $results = $this->dn->getSiblings($includeNode);
        foreach ($results as $result) {
            $siblings[] = $this->getTree()->wrapNode($result);
        }

        return $siblings;
    }

    /**
     * Gets prev sibling or null
     *
     * @return \App\DomainObject\Node\NestedSet|null
     */
    public function getPrevSibling()
    {
        /** @var $result \App\DomainObject\Model */
        $result = $this->dn->getPrevSibling();

        if (!$result) {
            return null;
        }

        return $this->getTree()->wrapNode($result);
    }

    /**
     * Gets next sibling or null
     *
     * @return \App\DomainObject\Node\NestedSet|null
     */
    public function getNextSibling()
    {
        /** @var $result \App\DomainObject\Model */
        $result = $this->dn->getNextSibling();

        if (!$result) {
            return null;
        }

        return $this->getTree()->wrapNode($result);
    }

    /**
     * Test if node has previous sibling
     *
     * @return bool
     */
    public function hasPrevSibling()
    {
        return $this->dn->hasPrevSibling();
    }

    /**
     * Test if node has next sibling
     *
     * @return bool
     */
    public function hasNextSibling()
    {
        return $this->dn->hasNextSibling();
    }

    //
    // Tree Modification Methods
    //

    /**
     * Inserts node as parent of given node
     *
     * @param \App\DomainObject\Node $node
     */
    public function insertAsParentOf(\App\DomainObject\Node $node)
    {
        $this->dn->insertAsParentOf($node->getModel());
    }

    /**
     * Inserts node as previous sibling of given node
     *
     * @param \App\DomainObject\Node $node
     */
    public function insertAsPrevSiblingOf(\App\DomainObject\Node $node)
    {
        $this->dn->insertAsPrevSiblingOf($node->getModel());
    }

    /**
     * Inserts node as next sibling of given node
     *
     * @param \App\DomainObject\Node $node
     */
    public function insertAsNextSiblingOf(\App\DomainObject\Node $node)
    {
        $this->dn->insertAsNextSiblingOf($node->getModel());
    }

    /**
     * Inserts node as first child of given node
     *
     * @param \App\DomainObject\Node $node
     */
    public function insertAsFirstChildOf(\App\DomainObject\Node $node)
    {
        $this->dn->insertAsFirstChildOf($node->getModel());
    }

    /**
     * Inserts node as last child of given node
     *
     * @param \App\DomainObject\Node $node
     */
    public function insertAsLastChildOf(\App\DomainObject\Node $node)
    {
        $this->dn->insertAsLastChildOf($node->getModel());
    }

    /**
     * Moves node as previous sibling of the given node
     *
     * @param \App\DomainObject\Node $node
     */
    public function moveAsPrevSiblingOf(\App\DomainObject\Node $node)
    {
        $this->dn->moveAsPrevSiblingOf($node->getModel());
    }

    /**
     * Moves node as next sibling of the given node
     *
     * @param \App\DomainObject\Node $node
     */
    public function moveAsNextSiblingOf(\App\DomainObject\Node $node)
    {
        $this->dn->moveAsNextSiblingOf($node->getModel());
    }

    /**
     * Moves node as first child of the given node
     *
     * @param \App\DomainObject\Node $node
     */
    public function moveAsFirstChildOf(\App\DomainObject\Node $node)
    {
        $this->dn->moveAsFirstChildOf($node->getModel());
    }

    /**
     * Moves node as last child of the given node
     *
     * @param \App\DomainObject\Node $node
     */
    public function moveAsLastChildOf(\App\DomainObject\Node $node)
    {
        $this->dn->moveAsLastChildOf($node->getModel());
    }

    /**
     * Makes this node a root node.
     *
     * @param int $new_root_id
     */
    public function makeRoot($new_root_id)
    {
        $this->dn->makeRoot($new_root_id);
    }

    /**
     * adds given node as the last child of this entity
     *
     * @param \App\DomainObject\Model|\App\DomainObject\Node $node
     * @return \App\DomainObject\Node\NestedSet
     */
    public function addChild($node)
    {
        if ($node instanceof \App\DomainObject\Node\NestedSet) {
            if ($node === $this) {
                return $this;
            }

            $node->insertAsLastChildOf($this);
            return $node;
        }

        $this->dn->addChild($node);

        return $this->getTree()->wrapNode($node);
    }

    /**
     * Deletes this node and it's decendants
     *
     */
    public function delete()
    {
        $this->dn->delete();
    }

    /**
     * Returns the wrapped node
     *
     * @return \App\DomainObject\Model
     */
    public function getNode()
    {
        return $this->node;
    }


    /**
     * Test if node has children
     *
     * @return bool
     */
    public function hasChildren()
    {
        return $this->dn->hasChildren();
    }


    /**
     * Test if node has parent
     *
     * @return bool
     */
    public function hasParent()
    {
        return $this->isValidNode() && !$this->isRoot();
    }

    /**
     * Determines if node is root
     *
     * @return bool
     */
    public function isRoot()
    {
        return $this->dn->isRoot();
    }

    /**
     * Determines if node is leaf
     *
     * @return bool
     */
    public function isLeaf()
    {
        return $this->dn->isLeaf();
    }

    /**
     * Determines if node is valid
     *
     * @return bool
     */
    public function isValidNode()
    {
        return $this->dn->isValidNode();
    }

    /**
     * ReMoves all cached ancestor/descendant entites
     */
    public function invalidate()
    {
        $this->parent      = null;
        $this->ancestors   = null;
        $this->descendants = null;
        $this->children    = null;
    }

    /**
     * Determines if this node is a child of the given node
     *
     * @param \App\DomainObject\Node $node
     * @return bool
     */
    public function isDescendantOf(\App\DomainObject\Node $node)
    {
        return $this->dn->isDescendantOf($node->getModel());
    }

    /**
     * Determines if this node is an ancestor of the given node
     *
     * @param \App\DomainObject\Node $node
     * @return bool
     */
    public function isAncestorOf(\App\DomainObject\Node $node)
    {
        return $this->dn->isAncestorOf($node->getModel());
    }

    /**
     * determines if this node is equal to the given node
     *
     * @param \App\DomainObject\Node $node
     * @return bool
     */
    public function isEqualTo(\App\DomainObject\Node $node)
    {
        return $this->dn->isEqualTo($node->getModel());
    }

    /**
     * Returns the \App\DomainObject\Tree\NestedSet Tree
     *
     * @return \App\DomainObject\Tree\NestedSet
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * @return \App\DomainObject\Model
     */
    public function getModel()
    {
        return $this->node;
    }

    /**
     * @return string
     */
    public function getLeftFieldName()
    {
        return 'lft';
    }

    /**
     * @return string
     */
    public function getRightFieldName()
    {
        return 'rgt';
    }

    /**
     * @return string
     */
    public function getRootFieldName()
    {
        return 'root_id';
    }

    /**
     * @return bool
     */
    public function hasManyRoots()
    {
        return $this->getTree()->getNestedSetManager()->getAttribute('hasManyRoots');
    }

    //
    // Node Interface Methods
    //

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->getNode()->getId();
    }

    /**
     * @return int
     */
    public function getLeftValue()
    {
        return $this->dn->getLeftValue();
    }

    /**
     * @param int $lft
     */
    public function setLeftValue($lft)
    {
        $this->dn->setLeftValue($lft);
    }

    /**
     * @return int
     */
    public function getRightValue()
    {
        return $this->dn->getRightValue();
    }

    /**
     * @param int $rgt
     */
    public function setRightValue($rgt)
    {
        $this->dn->setRightValue($rgt);
    }

    /**
     * @return int|mixed
     */
    public function getRootValue()
    {
        return $this->dn->getRootValue();
    }

    /**
     * @param int $root
     */
    public function setRootValue($root)
    {
        $this->dn->setRootValue($root);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '';
    }
}