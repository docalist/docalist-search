<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Store
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Store;

use Fooltext\Query\TermQuery;
use Fooltext\Query\MatchNothingQuery;
use Fooltext\Query\MatchAllQuery;
use Fooltext\Query\WildcardQuery;
use Fooltext\Query\PositionalQuery;
use Fooltext\Query\Query;
use Fooltext\Indexing\AnalyzerData;
use \XapianQuery;

/**
 * Une collection au sein d'une base de données Xapian.
 */
class XapianCollection implements CollectionInterface
{
    /**
     * Le store auquel appartient cette collection.
     *
     * @var StoreInterface
     */
    protected $store;

    /**
     * La base de données xapian en cours.
     *
     * @var \XapianDatabase
     */
    protected $db;

    /**
     * Le nom de la collection
     *
     * @var string
     */
    protected $name;

    /**
     * Le schéma de la collection.
     *
     * @var Fooltext\Schema\Collection
     */
    protected $collection;

    /**
     * L'id de la collection.
     *
     * @var string
     */
    protected $id;

    /**
     * Cache des analyseurs déjà créés pour l'indexation.
     *
     * @var array className => Analyzer
     */
    static protected $analyzerCache = array();


    public function __construct(StoreInterface $store, \XapianDatabase $db, $name)
    {
        $this->store = $store;
        $this->db = $db;
        $this->name = $name;
        $this->collection = $store->getSchema()->getCollection($name);
        $this->id = $this->collection->_id;
    }

    public function createDocument(array $data = array())
    {
        $class = $this->collection->documentClass;
        return new $class($data);
    }

    public function isReadonly()
    {
        return $this->store->isReadOnly();
    }

    protected function handleException(\Exception $e)
    {
        $message = $e->getMessage();
        if (false !== $pt = strpos($message, ':'))
        {
            $code = rtrim(substr($message, 0, $pt));
            $message = ltrim(substr($message, $pt+1));
            switch ($code)
            {
                case 'DocNotFoundError':
                    throw new Exception\DocumentNotFound($message, $e->getCode());
            }
        }

        throw $e;
    }

    // Convertit l'id au sein de la collection en id global pour la base
    protected function userIdToXapianId($id)
    {
        $term = $this->id . $id;

        $postlist = $this->db->postlist_begin($term);
        if ($postlist->equals($this->db->postlist_end($term)))
        {
            throw new Exception\DocumentNotFound("Le document $id n'existe pas dans cette collection");
        }

        return $postlist->get_docid();
    }

    public function get($id)
    {
        $id = $this->userIdToXapianId($id);

        // Charge les données de l'enregistrement Xapian
//         try
//         {
            $data = json_decode($this->db->get_document($id)->get_data(), true);
//         }
//         catch (\Exception $e)
//         {
//             $this->handleException($e);
//         }

        // Crée un document à partir des données en remplaçant les id des champs par leur nom
        //$document = $this->createDocument();
        $result = array();
        foreach($data as $id => $value)
        {
            $name = $this->collection->getFieldName($id);
            //if ($name) $document->add($name, $value);
            if ($name) $result[$name] = $value;
            // sinon : champ supprimé du schéma mais encore dans les données, on l'ignore
            // variante : si clé est une chaine, champ libre ?
        }
        //return $document;
        return $this->createDocument($result);
    }

    /**
     * Retourne une instance de l'analyseur dont le nom de classe
     * est passé en paramètre.
     *
     * @param string $class
     * @return Fooltext\Indexing\AnalyzerInterface
     */
    protected function getAnalyzer($class)
    {
        if (! isset(self::$analyzerCache[$class]))
        {
            self::$analyzerCache[$class] = new $class();
        }
        return self::$analyzerCache[$class];
    }

    public function put($document)
    {
        if (! (is_array($document) || $document instanceof Traversable))
        {
            throw new \InvalidArgumentException("Document incorrect, doit être itérable");
        }
        $doc = new \XapianDocument();

        //$fields = $this->schema->getChild('fields');
        $documentData = array();
        foreach($document as $name => $value)
        {
            // Vérifie que le champ indiqué existe dans la base
            $field = $this->collection->getField($name);

            // Stocke les données du champ s'il n'est pas vide
            $id = $field->_id;
            if (count($value)) $documentData[$id] = $value; // Ignore null et array()

            $prefix = $id.':';
            $weight = $field->weight;
var_export($weight);
            if (is_null($weight)) $weight = 1;

            // Analyse le champ
            if ($classes = $field->analyzer)
            {
                // todo : value = startEnd(value). Créer un "StartEndAnalyzer" ?
                $data = new AnalyzerData($field, $value);
                foreach((array)$classes as $class)
                {
                    self::getAnalyzer($class)->analyze($data);
                }
$data->dump("Champ $name");

                foreach($data->terms as $term)
                {
                    foreach((array)$term as $term)
                    {
                        echo "add_term('$prefix$term', $weight)\n";
                        $doc->add_term($prefix . $term, $weight);
                    }
                }

                $start = 0;
                foreach($data->postings as $position => $term)
                {

                    foreach((array)$term as $position => $term)
                    {
                        echo "add_posting('$prefix$term', ",$start + $position,", $weight)\n";
                        $doc->add_posting($prefix . $term, $start + $position, $weight);
                    }

                    $start += 100;
                    $start -= $start % 100;
                }

                foreach($data->keywords as $term)
                {
                    foreach((array)$term as $term)
                    {
                        echo "add_boolean_term('$prefix$term')\n";
                        $doc->add_boolean_term($prefix . $term);
                    }
                }

                foreach($data->spellings as $term)
                {
                    foreach((array)$term as $term)
                    {
                        echo "add_spelling('$term',1)\n";
                        $this->db->add_spelling($term, 1);
                    }
                }

                foreach($data->lookups as $term)
                {
                    foreach((array)$term as $term)
                    {
                        echo "add_boolean_term('T$prefix$term')\n";
                        $doc->add_boolean_term('T' . $prefix . $term);
                    }
                }

                foreach($data->sortkeys as $term)
                {
                    foreach((array)$term as $term)
                    {
                        echo "add_value('$term', $id)\n";
                        $doc->add_value($term, $id);
                    }
                }
            }
        }

        $doc->set_data(json_encode($documentData));
        $docId = $this->id . ((int) $documentData[1]); // @todo allouer un id au doc
        $doc->add_boolean_term($docId);
        echo "Appelle replace_document($docId)\n";
        $docId = $this->db->replace_document($docId, $doc);
        echo "ID ATTRIBUE PAR XAPIAN : $docId\n";
        return $this;
    }

    public function delete($id)
    {
        $id = $this->id . $id;
        try
        {
            $this->db->delete_document($id);
        }
        catch (\Exception $e)
        {
            $this->handleException($e);
        }
        return $this;
    }

    public function find($query, array $options)
    {
    }

    /**
     * Convertit une équation de recherche ou un objet Fooltext\Query
     * en requête Xapian.
     *
     * @param Query $query
     * @return \XapianQuery
     */
    public function xapianQuery(Query $query, $prefixes = array())
    {
        $xop = array
        (
            Query::QUERY_OR => XapianQuery::OP_OR,
            Query::QUERY_AND => XapianQuery::OP_AND,
            Query::QUERY_NOT => XapianQuery::OP_AND_NOT,
            Query::QUERY_AND_MAYBE => XapianQuery::OP_AND_MAYBE,

            Query::QUERY_NEAR => XapianQuery::OP_NEAR,
            Query::QUERY_PHRASE => XapianQuery::OP_PHRASE,
//            Query::QUERY_TERM => XapianQuery::OP_OR
        );

        $type = $query->getType();
        $args = $query->getArgs();
        $field = $query->getField();
        if ($field) $prefixes = $this->getPrefixes($field);

        // near, phrase
        if ($query instanceof PositionalQuery)
        {
            // on a, par exemple, Mots="a b". On veut convertir en (Titre="a b" OR Resum="a b")
            // i.e. on a Mots:a PHRASE 2 Mots:b et on veut obtenir (Titre:a PHRASE 2 Titre:b) OR (Resum:a PHRASE 2 Resum:b)
            // on le fait nous même, car sinon, xapina génère toutes les combinaisons possibles :
            // (Titre:a PHRASE 2 Titre:b) OR (Titre:a PHRASE 2 Resum:b) OR (Resum:a PHRASE 2 Titre:b) (Resum:a PHRASE 2 Resum:b)

            $queries = array();
            foreach ($prefixes as $prefix)
            {
                $terms = array();
                foreach($args as $arg)
                {
                    $terms[] = $prefix . $arg;
                }
                $queries[] = new XapianQuery($xop[$type], $terms, count($terms) + $query->getGap());
            }
            if (count($queries) === 1) return $queries[0];
            return new XapianQuery(XapianQuery::OP_OR, $queries);
        }


        foreach ($args as & $arg)
        {
//             if (is_string($arg))
//             {
//                 var_export($arg);
//                 die('arg is string');
//             }
            if ($arg instanceof Query)
            {
//                 echo "Conversion de <code>", $arg, '</code>';
                $arg = $this->xapianQuery($arg, $prefixes);
//                 echo ' en <code>', $arg->get_description(), '</code><br />';
            }
        }

        if ($query instanceof WildcardQuery)
        {
            return $this->wildcardQuery($query, $prefixes);
        }

        if ($query instanceof TermQuery)
        {
            return $this->termQuery($query, $prefixes);
        }

        if ($query instanceof MatchAllQuery)
        {
            return new XapianQuery('');
        }

        if ($query instanceof MatchNothingQuery)
        {
            return new XapianQuery();
        }

        // or, and, not, and_maybe
        return new XapianQuery($xop[$type], $args);
    }

    protected function termQuery(TermQuery $query, $prefixes)
    {
        $term = $query->getTerm();

        foreach ($prefixes as & $prefix)
        {
            $prefix .= $term;
        }

        return new XapianQuery(XapianQuery::OP_OR, $prefixes);
    }

    protected function wildcardQuery(WildcardQuery $query, $prefixes)
    {
        $term = $query->getTerm();
        foreach ($prefixes as $prefix)
        {
            $terms[] = $this->expandWildcard($prefix, $term);
        }

        return new XapianQuery(XapianQuery::OP_OR, $terms);
    }

    protected function expandWildcard($prefix, $mask)
    {
        $start = substr($mask, 0, strcspn($mask, '?*'));
        $terms = array($start.'1', $start.'2', $start.'3'); // consulter la base et extraire tout ce qui commence par start et qui respecte le masque
        foreach($terms as & $term)
        {
            $term = $prefix . $term;
        }
        return new XapianQuery(XapianQuery::OP_SYNONYM, $terms);
    }

    protected function getPrefixes($field)
    {
        return array($field.'1:');
        return array($field.'1:', $field.'2:');
        return array('f1:', 'f2:', 'f3:');
        return array($this->collection->getField($field)->_id . ':', '4:');
    }
/*
    public function reopen()
    {
        $this->db->reopen();
        return $this;
    }
*/
}