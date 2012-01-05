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

use Fooltext\DocumentSet\DocumentSetInterface;

/**
 * Représente une collection au sein d'une base de données.
 */
interface CollectionInterface
{
    /**
     * Retourne un document unique identifié par son ID.
     *
     * @param int $id l'ID du document recherché.
     *
     * @return DocumentInterface le document recherché ou null si
     * l'ID indiqué n'existe pas dans la collection.
     */
    public function get($id);

    /**
     * Ajoute ou modifie un document.
     *
     * Si le document ne figure pas déjà dans la collection (i.e. il
     * n'a pas encore d'ID), il est ajouté, sinon, il est mis à jour.
     *
     * Vous pouvez passer en paramètre un tableau ou un objet itérable.
     *
     * @param array|Traversable $document
     */
    public function put($document);

    /**
     * Supprime un document de la collection.
     *
     * @param int $id l'ID du document à supprimer.
     *
     * @return int le nombre d'enregistrements supprimés.
     */
    public function delete($id);

    /**
     * Recherche des documents.
     *
     * @param Query $query
     * @param array $options
     * @return DocumentSetInterface
     */
    public function find($query, array $options);

    // Manipulation de plusieurs enregistrements à la fois

    /**
     * Crée un objet document en utilisant la classe indiquée dans la collection.
     *
     * @param array $data
     * @return DocumentInterface
     */
    public function createDocument(array $data = array());

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
     * Indique si la collection est en lecture seule.
     */
    public function isReadonly();
}