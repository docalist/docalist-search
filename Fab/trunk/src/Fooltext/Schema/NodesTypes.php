<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Schema
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Schema;

/**
 * Classe statique utilisée pour définir les classes PHP utilisées pour représenter
 * les noeuds qui composent un schéma.
 *
 * Dans un schéma, les noeuds sont définis par un nom symbolique qui indique leur type
 * (field, index, etc.)
 *
 * Lorsqu'un schéma est chargé en mémoire, chaque noeud est représenté par un objet
 * PHP. La méthode {@link register()} permet de définir la classe PHP à utiliser
 * pour un nom symbolique donné.
 *
 * Par défaut, des classes sont fournies pour tous les types de noeud. Vous pouvez
 * utiliser {@link register()} pour remplacer une classe prédéfinie par votre
 * propre classe. Cela peut être utile pour introduire de nouvelles méthodes, pour
 * définir de nouvelles propriétés ou encore pour modifier les valeurs par défaut
 * d'un noeud.
 *
 * Pour cela, il suffit de définir une nouvelle classe descendante de
 * {@link Fooltext\Schema\Node}, de surcharger sa propriété statique $defaultProperties et
 * d'appeller la méthode {@link register()} en indiquant le nom symbolique
 * correspondant au type de noeud que vous voulez surcharger.
 */
use Fooltext\Schema\Exception\BadClass;
use Fooltext\Schema\Exception\BadNodeType;
use Fooltext\Schema\Exception\ClassNotFound;

abstract class NodesTypes
{
    /**
     * Tableau de conversion entre les noms symboliques et les noms de classes.
     *
     * @var array
     */
    protected static $typemap = array
    (
    	'schema'               => 'Fooltext\Schema\Schema',
    	'collection'           => 'Fooltext\Schema\Collection',
    	'fields'               => 'Fooltext\Schema\Fields',
            'field'            => 'Fooltext\Schema\Field',
            'groupfield'       => 'Fooltext\Schema\GroupField',
    	'indices'              => 'Fooltext\Schema\Indices',
    		'index'            => 'Fooltext\Schema\Index',
        	'indexfield'       => 'Fooltext\Schema\IndexField',
    	'aliases'              => 'Fooltext\Schema\Aliases',
        	'alias'	           => 'Fooltext\Schema\Alias',
    		'aliasindex'       => 'Fooltext\Schema\AliasIndex',
    	'lookuptables'         => 'Fooltext\Schema\LookupTables',
        	'lookuptable'      => 'Fooltext\Schema\LookupTable',
        	'lookuptablefield' => 'Fooltext\Schema\LookupTableField',
        'sortkeys'	           => 'Fooltext\Schema\Sortkeys',
    		'sortkey'	       => 'Fooltext\Schema\Sortkey',
        	'sortkeyfield'     => 'Fooltext\Schema\SortkeyField',
    );


    /**
     * Tableau inverse de {@link $typemap}, indique le nom symbolique associé
     * à chaque classe.
     *
     * Utilisé et construit automatiquement par {@link nodetypeToClass()}.
     *
     * @var array
     */
    private static $nodetype;


    /**
     * Retourne un tableau contenant toutes les associations actuellement définies.
     *
     * Le tableau retourné est sous la forme nom symbolique => nom de classe php.
     *
     * @return array
     */
    public static function all()
    {
        return self::$typemap;
    }


    /**
     * Définit le nom de la classe PHP à utiliser pour représenter un type de noeud
     * donné au sein d'un schéma.
     *
     * @param string $nodetype le type de noeud à surcharger (nom symbolique).
     * @param string $class le nom de la classe PHP à utiliser pour ce type de noeud.
     * @throws \Fooltext\Schema\Exception\ClassNotFOund si la classe indiquée n'existe pas.
     * ou qui n'hérite pas de Fooltext\Schema\Node).
     * @throws \Fooltext\Schema\Exception\BadClass si la classe indiquée n'est pas correcte
     * (n'hérite pas de Fooltext\Schema\Node).
     */
    public static function register($nodetype, $class)
    {
        if (! class_exists($class))
        {
            throw new ClassNotFound("Classe $class non trouvée");
        }

        if (! is_subclass_of($class, 'Fooltext\Schema\Node'))
        {
            throw new BadClass("Classe incorrecte");
        }

        self::$typemap[$nodetype] = $class;

        self::$nodetype = null; // le tableau inverse doit être recréé
    }


    /**
     * Retourne la classe PHP à utiliser pour représenter un noeud d'un type donné.
     *
     * @param string $nodetype type de noeud (nom symbolique).
     * @throws \Fooltext\Schema\Exception\BadNodeType si le nom symbolique indiqué n'est pas référencé.
     * @return string
     */
    public static function nodetypeToClass($nodetype)
    {
        if (! isset(self::$typemap[$nodetype]))
        {
            throw new BadNodeType("Type de noeud inconnu : '$nodetype'");
        }
        return self::$typemap[$nodetype];
    }


    /**
     * Retourne le nom symbolique associé à une classe PHP donnée.
     *
     * @param string $class
     * @throws \Fooltext\Schema\Exception\ClassNotFound si la classe indiquée n'est pas référencée.
     * @return string
     */
    public static function classToNodetype($class)
    {
        if (is_null(self::$nodetype)) self::$nodetype = array_flip(self::$typemap);

        if (! isset(self::$nodetype[$class]))
        {
            throw new ClassNotFound("Nom de classe inconnue : '$class'");
        }

        return self::$nodetype[$class];
    }
}

