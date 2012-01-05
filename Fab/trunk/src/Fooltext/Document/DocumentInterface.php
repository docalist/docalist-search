<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Document
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id: AnalyzerInterface.php 10 2011-12-13 15:45:47Z daniel.menard.35@gmail.com $
 */
namespace Fooltext\Document;

/**
 * Interface de base pour représenter un document.
 *
 * Un document est une liste ordonnée de champs composés d'un nom
 * et d'une valeur.
 *
 * Un objet Document possède les caractéristiques suivantes :
 * - Les champs sont itérables, vous pouvez utiliser un objet Document
 *   dans une boucle foreach (interface IteratorAggragate).
 * - Vous pouvez accèder aux champs comme si le document était un tableau
 *   (interface ArrayAcces)
 * - Les champs sont dénombrables, vous pouvez utiliser count($document)
 *   pour obtenir le nombre de champs présents dans le document.
 *   (interface Countable)
 * - Vous pouvez accèder aux champs comme s'il s'agissait de propriétés
 *   de l'objet Document. Ceci est possible grace aux méthodes magiques
 *   de php __get, __set, __isset et __unset.
 * - Un Document peut être créé à partir d'un tableau (constructeur) ou
 *   convertit en tableau (méthode toArray()).
 * - Un document peut être "dumpé" directement (echo $document), __toString()
 *
 * Remarques :
 * - quand on accède à un champ qui n'existe pas ($doc->title ou $doc['title'])
 *   l'objet Document doit retourner la valeur null. Il ne doit pas générer d'exception,
 *   ni d'erreurs, ni de warnings.
 */
interface DocumentInterface extends \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * Construit un nouveau document contenant les données
     * passées en paramètre.
     *
     * @param array $data
     */
    public function __construct(array $data = array());

    /**
     * Retourne le contenu du champ dont le nom est indiqué.
     *
     * @param string $field
     * @return mixed retourne null si le champ n'existe pas.
     */
    public function __get($field);

    /**
     * Modifie le contenu du champ dont le nom est indiqué.
     *
     * @param string $field
     * @param mixed $value
     */
    public function __set($field, $value);

    /**
     * Indique si le document contient le champ dont le nom est indiqué.
     *
     * @param string $field
     * @return bool
     */
    public function __isset($field);

    /**
     * Supprime le champ dont le nom est indiqué.
     *
     * @param string $field
     */
    public function __unset($field);

    /**
     * Convertit le document en tableau.
     *
     * @return array
     */
    public function toArray();

    /**
     * Affiche le contenu du document.
     */
    public function __toString();
}