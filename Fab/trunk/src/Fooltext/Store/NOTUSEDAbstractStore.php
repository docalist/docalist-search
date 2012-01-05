<?php
namespace Fooltext\Store;

use Fooltext\Schema\Schema;

abstract class AbstractStore implements StoreInterface
{
    public function getMany($id)
    {
        // Un tableau
        if (is_array($id))
        {
            return array_map(array($this, 'get'), $id); // array_walk ???
        }

        // Un objet itérable
        if ($id instanceof Traversable)
        {
            $result = array();
            foreach($id as $i) $result[] = $this->get($i);
            return $id;
        }

        // Erreur
        throw new InvalidArgumentException;
    }

    public function putMany(& $documents)
    {
        // Un tableau
        if (is_array($documents))
        {
            return array_map(array($this, 'put'), $documents);
        }

        // Un objet itérable
        if ($documents instanceof Traversable)
        {
            foreach($documents as & $document) $this->put($document);
            return;
        }

        // Erreur
        throw new InvalidArgumentException;
    }

    public function deleteMany($documents)
    {
        // Un tableau
        if (is_array($documents))
        {
            return array_map(array($this, 'delete'), $documents);
        }

        // Un objet itérable
        if ($documents instanceof Traversable)
        {
            foreach($documents as & $document) $this->delete($document);
            return;
        }

        // Erreur
        throw new InvalidArgumentException;
    }
}