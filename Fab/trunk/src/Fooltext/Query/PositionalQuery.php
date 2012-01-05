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
 * Classe de base (abstraite) pour les requêtes qui tiennent compte de la position
 * des termes au sein du document (PhraseQuery, NearQuery).
 *
 * Ce type de requête dispose d'une propriété supplémentaire (gap) qui
 * détermine l'espace maximal autorisé entre les termes qui composent la requête.
 *
 * Par exemple, pour une recherche par expression, lorsque le gap vaut zéro,
 * les termes recherchés devront être adjacents ; lorsque le gap vaut 1, un mot
 * au maximum sera autorisé entre les termes recherchés ; etc.
 *
 * Le gap indiqué influe fortement sur les résultats qui seront retournés par la requête.
 * Par exemple si on recherche "formation des personnels", selon la valeur du gap, on
 * pourra trouver (les mots ignorés sont entre parenthèses) :
 * - gap=0 : uniquement "formation des personnels"
 * - gap=1 : trouvera en plus "formation (continue) des personnels".
 * - ...
 * - gap=4 : trouvera en plus "formation (à la sécurité incendie) des personnels".
 * - etc.
 */
abstract class PositionalQuery extends Query
{
    /**
     * Espace autorisé entre les termes de la requête.
     *
     * @var int
     */
    protected $gap;

    /**
     * Crée une nouvelle requête.
     *
     * @param array $args les arguments de la requête (i.e. les noeuds fils).
     * @param string|null $field le nom du champ sur lequel porte cette requête.
     * @param int $gap le gap, c'est-à-dire l'espace autorisé entre les termes.
     *
     * @throws \Exception si les paramètres indiqués ne sont pas valides.
     */
    public function __construct(array $args, $field = null, $gap = 0)
    {
        parent::__construct($args, $field);
        $this->gap = $gap;
    }

    /**
     * Retourne l'espace autorisé entre les termes de la requête.
     *
     * @return int
     */
    public function getGap()
    {
        return $this->gap;
    }
}