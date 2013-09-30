<?php
/**
 * This file is part of a "Docalist Biblio" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Biblio
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */
namespace Docalist\Table;

use Docalist\Cache\FileCache;
use Docalist\Tokenizer;
use PDO;

/**
 * Une table
 *
 */
interface TableInterface {

    /**
     * Retourne le type de la table.
     *
     * @return string Le type de la table (<code>sqlite</code>, <code>csv</code>
     * ou <code>php</code>).
     */
    public function type();

    /**
     * Retourne le path de la table.
     *
     * Pour une table au format <code>SQLite</code>, le path retourné correspond
     * au path indiqué lors de l'ouverture de la table.
     *
     * Pour une table "compilée" (<code>csv</code> ou <code>php</code>), le
     * path retourné correspond au chemin du fichier source de la table (et
     * non pas celui de la version compilée).
     *
     * @return string
     */
    public function path();

    /**
     * Indique si la table peut être mise à jour ou non.
     *
     * Seules les tables au format <code>SQLite</code> peuvent être mises à
     * jour. Les tables "compilées" (<code>csv</code> ou <code>php</code>) ne
     * peuvent pas être modifiées.
     *
     * @return boolean
     */
    public function readOnly();

    /**
     * Retourne la liste des champs de la table.
     *
     * Chaque champ existe en deux versions :
     * - la version d'origine, qui contient les données exacte du champ,
     * - une version préfixée avec "_" qui contient la version tokenisée
     *   (c'est-à-dire minusculisée et désaccentuée) des données du champ.
     *
     * Le champ spécial permet de faire des recherches insensibles à la casse
     * (exemple : {@link lookup}).
     *
     * Ainsi, pour une table avec les colonnes <code>code</code> et
     * <code>label</code>, la méthode retournera le tableau :
     *
     * <code>
     *     array('code', 'label', '_code', '_label')
     * </code>
     *
     * @return string[] Un tableau contenant les champs de la table puis les
     * champs spéciaux.
     */
    public function fields();

    /**
     * Recherche des entrées dans la table.
     *
     * La méthode <code>search()</code> permet de faire une recherche dans une
     * table un peu comme si on exécutait la requête SQL suivante :
     *
     * <code>
     *     SELECT $what FROM table
     *     [ WHERE $where ]
     *     [ ORDER BY $order ]
     *     [ LIMIT $limit [ OFFSET $offset ] ];
     * </code>
     *
     * Le format des réponses retournées dépend des champs demandés dans
     * <code>$what</code> :
     *
     * - Un seul champ, retourne un tableau contenant les valeurs du champ :
     *   <code>
     *      $countries->search('name') :
     *      // array( 'Aruba', 'Afghanistan', ...)
     *   </code>
     *
     * - Deux champs, retourne un tableau associatif de la forme
     *   <code>champ1 => champ2</code> :
     *   <code>
     *      $countries->search('alpha3,name')
     *      // array( 'ABW' => 'Aruba', 'AFG' => 'Afghanistan', ...)
     *   </code>
     *
     * - Plus de deux champs (ou "*"), retourne un tableau associatif de la
     *   forme <code>premier champ => objet contenant les autres champs</code> :
     *   <code>
     *      $countries->search() ou $countries->search('alpha3,name,alpha2')
     *      // array(
     *      //    'ABW' => StdClass('name' => 'Aruba', 'alpha2' => 'AW'),
     *      //    'AFG' => StdClass('name' => 'Afghanistan', 'alpha2' => 'AF'),
     *      //    ...
     *      // )
     *   </code>
     *
     * Remarques :
     *
     * - Lorsqu'un tableau associatif est retourné, une seule valeur est
     *   retournée pour chaque clé. Vous pouvez utiliser <code>ROWID</code>
     *   si les entrées du champ utilisé comme clé ne sont pas uniques :
     *   <code>
     *       // toutes les entrées de la table, indexées par ROWID
     *       $countries->search('ROWID, *')
     *   </code>
     *
     * - Pour les tableaux associatifs, le champ utilisé comme clé ne figure
     *   pas dans l'objet associé. Vous pouvez forcer sa présence dans l'objet
     *   en répétant (ou vous voulez) le nom du champ :
     *   <code>
     *       $countries->search('alpha3,name,alpha2')
     *       // -> array(alpha3 => object(name,alpha2))
     *
     *       $countries->search('alpha3, alpha3,name,alpha2')
     *       // -> array(alpha3 => object(alpha3,name,alpha2))
     *   </code>
     *
     * @param string $what Le ou les champs à retourner (* par défaut).
     *
     * @param string $where Critères de recherche (par exemple
     * <code>code="fra"</code>).
     *
     * @param string $order Ordre de tri (par exemple <code>name</code> ou
     * <code>name desc</code>).
     *
     * @param int $limit Nombre maximum de réponses à retourner.
     *
     * @param int $offset Offset des réponses.
     *
     * @return array La méthode retourne toujours un tableau (éventuellement
     * vide).
     */
    public function search($what = '*', $where = '', $order = '', $limit = null, $offset = null);

    /**
     * Recherche une entrée unique dans dans la table.
     *
     * Identique à search() mais retourne uniquement le premier enregistrement.
     *
     * Exemples :
     * <code>
     *     echo $countries->find('name', 'alpha3="fra"'); // string 'France'
     *     $france = $countries->find('*', 'alpha3="fra"'); // StdClass
     * </code>
     *
     * @param string $what Le ou les champs à retourner (* par défaut).
     *
     * @param string $where Critères de recherche de la clause.
     *
     * @return null|scalar|object Le premier élément trouvé ou null si aucune
     * réponse n'a été trouvée.
     *
     * Le type de la valeur retournée dépend du nombre de champs demandés
     * (consultez la documentation de {@link search()}).
     */
    public function find($what = '*', $where = '');

    /**
     * Recherche les entrées qui commencent par un préfixe donné.
     *
     * La recherche est effectuée sur le premier champ indiqué (ou le premier
     * champ de la table si vous indiquez "*"). Ce champ est également utilisé
     * pour trier les réponses obtenues.
     *
     * La recherche (et le tri des réponses) sont insensibles aux accents et
     * à la casse des caractères.
     *
     * Exemple :
     * <code>
     *     // Recherche tous les pays dont le nom commence par E (ou E, é, É, ...),
     *     // génère un tableau associatif de la forme nom => code et trie le
     *     // résultat par nom sans tenir compte de la casse et des accents
     *     // (natural sort order) :
     *     $countries->lookup('name,alpha3', 'e')
     *
     *     array(
     *         'Égypte' => 'EGY'
     *         'Espagne' => 'ESP'
     *         'Éthiopie' => 'ETH'
     *     )
     * </code>
     *
     * @param string $what Le ou les champs à retourner (* par défaut).
     *
     * @param string $prefix Le préfixe recherché.
     *
     * @param int $limit Nombre maximum de réponses à retourner.
     *
     * @return array Un tableau (éventuellement vide) contenant les réponses
     * obtenues.
     *
     * Le format des entrées retournées dépend du nombre de champs demandés
     * (consultez la documentation de {@link search()}).
     */
    public function lookup($what = '*', $prefix, $limit = null);
}