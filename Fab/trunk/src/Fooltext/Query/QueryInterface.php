<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Query
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Query;

/**
 * Interface utilisée pour représenter une requête.
 *
 * En interne, les requêtes sont représentées sous la forme d'un arbre.
 * L'interface QueryInterface définit le type des classes utilisées pour
 * stocker les noeuds de cet arbre.
 *
 * Chaque noeud comporte :
 * - un type (cf {@kink getType()}),
 * - des noeuds fils (arguments) de la requête (cf {@link getArgs()}),
 * - un nom de champ sur lequel porte la requête (cf {@link getField()}).
 */
interface QueryInterface
{
    /**
     * Type d'une requête qui retourne les documents qui contiennent l'un des arguments.
     *
     * @var int
     */
    const QUERY_OR = 1;

    /**
     * Type d'une requête qui retourne les documents qui contiennent tous les arguments.
     *
     * @var int
     */
    const QUERY_AND = 2;

    /**
     * Type d'une requête qui retourne les documents qui contiennent le premier argument
     * de la requête et qui ne contiennent aucun des arguments suivants.
     *
     * @var int
     */
    const QUERY_NOT = 3;

    /**
     * Type d'une requête qui retourne les documents qui contiennent le premier argument
     * de la requête et augmente le score des documents qui contiennent également
     * un ou plusieurs des arguments suivants.
     *
     * @var int
     */
    const QUERY_AND_MAYBE = 4;

    /**
     * Type d'une requête qui retourne les documents qui contiennent les arguments indiqués
     * dans n'importe quel ordre mais à une certaine distance maximale les uns des autres.
     *
     * @var int
     */
    const QUERY_NEAR = 5;

    /**
     * Type d'une requête qui retourne les documents qui contiennent les arguments indiqués
     * dans l'ordre indiqués à une certaine distance maximale les uns des autres.
     *
     * @var int
     */
    const QUERY_PHRASE = 6;

    /**
     * Type d'une requête qui retourne les documents qui contiennent au moins un terme
     * correspondant au masque qui figure dans la requête.
     *
     * @var int
     */
    const QUERY_WILDCARD = 7;

    /**
     * Type d'une requête qui retourne les documents qui contiennent le terme unique qui
     * figure dans la requête.
     *
     * @var int
     */
    const QUERY_TERM = 8;

    /**
     * Type d'une requête qui retourne tous les documents.
     *
     * @var int
     */
    const QUERY_MATCH_ALL = 9;

    /**
     * Type d'une requête qui ne retourne jamais aucun document.
     *
     * @var int
     */
    const QUERY_MATCH_NOTHING = 10;

    /**
     * Optimise la requête.
     *
     * Exemples :
     * - (a or b) OR (c or d) est transformée en (a or b OR c or d)
     * - (a and b) AND (c and d) est transformée en (a and b AND c and d)
     *
     * @return QueryInterface $this
     */
    public function optimize();

    /**
     * Retourne le type de la requête.
     *
     * @param bool $asString par défaut, la méthode retourne le type de la requête
     * sous la forme d'un entier (une des constantes QUERY_XXX).
     * Quand $asString vaut true, elle retourne une chaine représentant le type de la requête.
     *
     * @return int|string
     */
    public function getType($asString = false);

    /**
     * Retourne les arguments de la requête (les noeuds fils).
     *
     * @return array
     */
    public function getArgs();

    /**
     * Retourne le nom du champ sur lequel porte la requête.
     *
     * @return string|null
     */
    public function getField();

    /**
     * Définit le nom du champ sur lequel porte la requête.
     *
     * @param string $field
     */
    public function setField($field);

    /**
     * Retourne une représentation textuelle de la requête.
     *
     * @return string
     */
    public function __toString();
}