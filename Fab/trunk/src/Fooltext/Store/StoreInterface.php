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

use Fooltext\Schema\Schema;

/**
 * Interface des bases de données.
 *
 */
interface StoreInterface
{
    /**
     * Crée un nouvel objet Store.
     *
     * Les options disponibles dépendent du backend utilisé.
     * Elles déterminent s'il faut créer une base de données
     * ou ouvrir une base existante, si la base doit être
     * ouverte en lecture seule ou en lecture/écriture, etc.
     *
     * @param array $options
     */
    public function __construct(array $options = array());

    /**
     * Retourne un document unique identifié par son ID.
     *
     * @param int $id l'ID du document recherché.
     *
     * @return Document le document recherché ou null si
     * l'ID demandé n'existe pas.
     */
    // public function get($id);

    /**
     * Ajoute ou modifie un document.
     *
     * Si le document ne figure pas déjà dans la base (i.e. il n'a pas
     * encore d'ID), il est ajouté, sinon, il est mis à jour.
     *
     * @param Document $doc
     *
     * todo : pourrait accepter un tableau de documents ?
     * (ajout/modification par lots)
     */
    // public function put(Document $document);

    /**
     * Supprime un document.
     *
     * @param int $id l'ID du document à supprimer.
     *
     * @return int le nombre d'enregistrements supprimés.
     */
    // public function delete($id);

    /**
     * Recherche des documents.
     *
     * @param unknown_type $query
     * @param array $options
     * @return DocumentSet
     */
    // public function find($query, array $options);

    // Manipulation de plusieurs enregistrements à la fois

    /**
     * Retourne plusieurs documents identifiés par leurs ID.
     *
     * @param array|Traversable $id un tableau ou un objet
     * itérable contenant les identifiants des documents à
     * retourner.
     *
     * @return array(Document)
     */
    // public function getMany($id);


    /**
     * Ajoute ou modifie plusieurs documents.
     *
     * @param array|Traversable $documents
     */
    // public function putMany(& $documents);

    /**
     * Supprime plusieurs documents.
     *
     * @param array|Traversable $documents
     */
    // public function deleteMany($documents);


    // Fonctions d'information

    /**
     * Indique si la base de données est en lecture seule.
     */
    public function isReadonly();


    // Manipulation du schéma de la base

    /**
     * Retourne le schéma de la base.
     *
     * @return \Fooltext\Schema\Schema
     */
    public function getSchema();

    /**
     * Modifie le schéma de la base.
     *
     * @param \Fooltext\Schema\Schema $schema
     */
    public function setSchema(Schema $schema);

    /**
     * Retourne les noms des collections définies dans la base.
     *
     * @return array
     */
    public function getCollectionNames();

    /**
     * Retourne la collection dont le nom est indiqué.
     *
     * __get() est une méthode magique de php qui permet d'accèder
     * à une collection comme s'il s'agissait d'une propriété de la
     * base de données (exemple : $db->records).
     *
     * @param string $collection
     * @return CollectionInterface
     * @throws \Exception si la collection indiquée n'existe pas.
     */
    public function __get($collection);

    /**
     * Indique si la collection dont le nom est indiqué existe dans la base.
     *
     * __isset() est une méthode magique de php qui permet d'accèder
     * à une collection comme s'il s'agissait d'une propriété de la
     * base de données (exemple : isset($db->records)).
     *
     * @param string $collection
     * @return bool
     */
    public function __isset($collection);
}