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
 * Classe de base abstraite utilisée pour représenter une requête.
 *
 * En interne, les requêtes sont représentées sous la forme d'un arbre.
 * La classe Query est la classe de base utilisée pour stocker les noeuds
 * de cet arbre.
 *
 * Certains types de requête disposent également d'options supplémentaires
 * (par exemple, la classe {@link PositionalQuery} permet de spécifier le "gap"
 * autorisé entre les termes).
 */
abstract class Query implements QueryInterface
{
    /**
     * Tableau utilisé pour convertir les types de requête.
     *
     * @var array
     */
    protected static $types = array
    (
        self::QUERY_AND => 'AND',
        self::QUERY_AND_MAYBE => 'AND_MAYBE',
        self::QUERY_MATCH_ALL => 'ALL',
        self::QUERY_MATCH_NOTHING => 'NOTHING',
        self::QUERY_NEAR => 'NEAR',
        self::QUERY_NOT => 'NOT',
        self::QUERY_OR => 'OR',
        self::QUERY_PHRASE => 'PHRASE',
        self::QUERY_TERM => 'TERM',
        self::QUERY_WILDCARD => 'WILDCARD'
    );

    /**
     * Le type de la requête.
     *
     * Il s'agit d'une des constantes QUERY_* (cf. {@link QueryInterface}).
     *
     * @var int
     */
    protected static $type = 0;

    /**
     * Le nom du champ sur lequel porte cette requête.
     *
     * @var string|null
     */
    protected $field;

    /**
     * Les arguments de la requête (i.e. les noeuds fils).
     *
     * @var array
     */
    protected $args;

    /**
     * Crée une nouvelle requête.
     *
     * @param array $args les arguments de la requête (i.e. les noeuds fils).
     * @param string|null $field le nom du champ sur lequel porte cette requête.
     *
     * @throws \Exception si les paramètres indiqués ne sont pas valides.
     */
    public function __construct(array $args, $field = null)
    {
        if (count($args) < 2)
        {
            var_export($args);
            throw new \Exception('Vous devez indiquer au moins deux clauses.');
        }
        $this->args = $args;
        $this->field = $field;
    }

    public function optimize()
    {
        // Si les sous-requêtes sont du même type que la requête en cours,
        // on les fusionne dans la requête en cours. Autrement dit, on supprime
        // les parenthèses inutiles (associativité).
        // Exemples :
        // (a or b) OR (c or d) -> (a or b OR c or d)
        // (a and b) AND (c and d) -> (a and b AND c and d)
        for ($offset = count($this->args) - 1 ; $offset >= 0 ; $offset--)
        {
            $arg = $this->args[$offset];
            if (! $arg instanceof Query) continue;

            $arg->optimize();
            //if ($arg::$type === $this::$type)
            if ($arg->getType() === $this->getType())
            {
                array_splice($this->args, $offset, 1, $arg->args);
            }
        }
        return $this;
    }

    public function getType($asString = false)
    {
        return $asString ? self::$types[static::$type] : static::$type;
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function setField($field)
    {
        $this->field = $field;
    }

    public function getField()
    {
        return $this->field;
    }

    public function __toString()
    {
        if (count($this->args) === 1)
        {
            return
                (is_null($this->field))
                ? reset($this->args)
                : $this->field . ':' . reset($this->args);
        }

        $h = is_null($this->field) ? '(' : ($this->field . ':(');
        foreach($this->args as $i=>$arg)
        {
            if ($i) $h .= ' ' . $this->getType(true) . ' ';
            $h .= (string) $arg;
        }
        $h .= ')';
        return $h;
    }

    public function toXapian()
    {
        $xop = array
        (
            self::QUERY_AND => \XapianQuery::OP_AND,
            self::QUERY_OR => \XapianQuery::OP_OR,
            self::QUERY_NOT => \XapianQuery::OP_AND_NOT,
            self::QUERY_AND_MAYBE => \XapianQuery::OP_AND_MAYBE,
            self::QUERY_NEAR => \XapianQuery::OP_NEAR,
            self::QUERY_PHRASE => \XapianQuery::OP_PHRASE,
            self::QUERY_TERM => \XapianQuery::OP_OR
        );

        $args = $this->getArgs();
        foreach ($args as & $arg)
        {
            if ($arg instanceof Query) $arg=$arg->toXapian();
        }

        if (! isset($xop[$this::$type]))
        {
            throw new \Exception('Type de requête non géré : ' . $this::$type);
        }

        if ($this::$type === self::QUERY_PHRASE) $this->option = 5;

        if ($this->option)
        {
            return new \XapianQuery($xop[$this::$type], $args, $this->option);
        }
        else
        {
            return new \XapianQuery($xop[$this::$type], $args);
        }
    }
}