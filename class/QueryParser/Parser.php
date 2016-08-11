<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2011-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\QueryParser;

use Docalist\Search\QueryParser\Builder;

/**
 * Analyseur syntaxique pour les requêtes docalist-search.
 */
class Parser
{

    // Ressources :
    // Bing, précédence des opérateurs : https://msdn.microsoft.com/en-us/library/ff795639.aspx
    // "and" est ignoré par Google : http://searchresearch1.blogspot.fr/2012/01/what-is-and-about-really.html
    // Google ne gère pas les parenthèses : https://support.google.com/websearch/answer/2466433?hl=en&rd=1
    // Dans Google, OR est prioritaire sur AND : https://goo.gl/5dEEQM, https://goo.gl/3mGHRQ
    // vacation london OR paris = vacation AND (london OR paris)
    // Idem pour Duckduckgo : https://duck.co/help/results/syntax
    // Blog de Irina Shamaeva : http://booleanstrings.com/

    /**
     * Analyseur lexical
     *
     * @var Lexer
     */
    protected $lexer;

    /**
     * Liste des tokens retournés par l'analyseur lexical.
     *
     * @var array
     */
    protected $tokens;

    /**
     * Position du token en cours dans la liste des tokens
     *
     * @var int
     */
    protected $position;

    /**
     * Code du token en cours.
     *
     * @var int
     */
    protected $token;

    /**
     * Valeur du token en cours.
     *
     * @var string
     */
    protected $tokenText;

    /**
     * Code du prochain token.
     *
     * @var int
     */
    protected $nextToken;

    /**
     * Code du dernier token.
     *
     * @var int
     */
    protected $lastToken;

    protected $defaultOp = 'and';

    /**
     * Builder en cours : un QueryBuilder pour parse(), un ExplainBuilder pour explain().
     *
     * @var Builder
     */
    protected $builder;

    /**
     * Initialise l'analyseur.
     *
     */
    public function __construct()
    {
        $this->lexer = new Lexer();
    }

    /**
     * Lit le prochain token.
     */
    protected function read()
    {
        $this->lastToken = $this->token;
        list($this->token, $this->tokenText) = $this->tokens[$this->position++];
        $this->nextToken = ($this->position < count($this->tokens)) ? $this->tokens[$this->position][0] : null;
    }

    /**
     * Analyse une équation de recherche et construit l'objet Query correspondant.
     *
     * @param string $string
     * @param string $defaultField
     *
     * @return array
     */
    public function parse($string, $field = '_all', $operator = 'and')
    {
        return $this->parseString(new QueryBuilder(), $string, $field, $operator);
    }

    public function explain($string, $field = '_all', $operator = 'and')
    {
        return $this->parseString(new ExplainBuilder(), $string, $field, $operator);
    }

    protected function parseString(Builder $builder, $string, $field, $operator)
    {
        $this->builder = $builder;

        // Initialise le lexer
        $this->tokens = $this->lexer->tokenize($string);
        $this->position = 0;
        $this->read();

        // Analyse la requête
        $queries = [];
        while ($this->token !== Lexer::T_END) {
            $this->parseExpression($field, $queries);
        }

        // Combine les requêtes obtenues et retourne le résultat
        if (empty($queries)) {
            return null;
        }
        if (count($queries) === 1) {
            return $queries[0];
        }
        return $this->builder->bool([], $queries); // defaultop === 'and'
//      return $this->bool($queries); // defaultop === 'or'
    }

    protected function parseExpression($field, array & $queries)
    {
        $default = $love = $hate = [];
        for(;;)
        {
            switch($this->token)
            {
                case Lexer::T_END:
                    break 2;

                case Lexer::T_NONE:
                    $this->read();
                    break;

                case Lexer::T_TERM:
                case Lexer::T_PREFIX:
                case Lexer::T_PHRASE:
                case Lexer::T_FIELD:
                case Lexer::T_AND:  // explication : la requête commence par un mot-clé. On le traite comme un terme
                case Lexer::T_OR:   // car ça peut être le début d'une phrase (exemple : near death experience)
                case Lexer::T_OPEN_PARENTHESIS:
                    $this->parseOr($field, $default);
                    break;

                case Lexer::T_PLUS:
                    $this->read();
                    if (true) { // defaultop = 'and'
                        $this->parseCompound($field, $default);
                    } else {
                        $this->parseCompound($field, $love);
                    }

                    break;

                case Lexer::T_MINUS:
                    $this->read();
                    $this->parseCompound($field, $hate);
                    break;

                case Lexer::T_NOT:
                    $this->read();
                    $this->parseCompound($field, $hate); // Loi de Morgan ?
                    break;

                case Lexer::T_STAR:
                    $this->parseCompound($field, $default);
                    break;

                default:
                    $this->read(); // important : parseString() continue à nous appeller tant qu'il ne trouve pas T_END
                    break 2;
            }
        }
        if (empty($love) && empty($hate)) {
            if ($default) {
                $queries = array_merge($queries, $default);
            }
        } else {
            $must = array_merge($default, $love);
            $queries[] = $this->builder->bool([], $must, $hate);
        }
    }

    /*
     * - un terme : match query
     * - une phrase : match_phrase query
     * - une troncature : prefix query
     * - un champ : recurse
     * - une parenthèse ouvrante : appelle parseExpression
     * sinon : null
     */
    protected function parseCompound($field, array & $queries)
    {
        switch($this->token)
        {
            case Lexer::T_TERM:
            case Lexer::T_AND:  // explication : la requête commence par un mot-clé. On le traite comme un terme
            case Lexer::T_OR:   // car ça peut être le début d'une phrase (exemple : near death experience)
                $terms = [];
                do {
                    $terms[] = $this->tokenText;
                    $this->read();
//                     if (    ($this->token === Lexer::T_AND && $this->nextToken === Lexer::T_TERM && $this->defaultOp === 'and')
//                         ||  ($this->token === Lexer::T_OR && $this->nextToken === Lexer::T_TERM && $this->defaultOp === 'or')
//                        ) {
//                         $this->read();
//                    }
//                  break;
                } while ($this->token === Lexer::T_TERM);

                if ($this->token === Lexer::T_RANGE) {
                    $this->read(); // T_RANGE
                    $start = empty($terms) ? null : array_pop($terms);
                    $end = null;
                    if ($this->token === Lexer::T_TERM) {
                        $end = $this->tokenText;
                        $this->read();
                    }
                    $terms && $queries[] = $this->builder->match($field, $terms);
                    $queries[] = $this->builder->range($field, $start, $end);
                }
                $terms && $queries[] = $this->builder->match($field, $terms);
                break;

            case Lexer::T_PHRASE:
                $terms = [];
                do {
                    $terms[] = $this->tokenText;
                    $this->read();
                } while ($this->token === Lexer::T_PHRASE);
                $this->read(); // T_NONE = fin de phrase
                $queries[] = $this->builder->phrase($field, $terms);
                break;

            case Lexer::T_PREFIX:
                $prefix = $this->tokenText;
                $this->read();
                $queries[] = $this->builder->prefix($field, $prefix);
                break;

            case Lexer::T_FIELD:
                $newField = $this->tokenText;
                $this->read();
                if ($this->token === Lexer::T_STAR) { // field:*
                    $this->read();
                    $queries[] = $this->builder->exists($newField);
                } else {
                    $this->parseCompound($newField, $queries);
                }
                break;

            case Lexer::T_OPEN_PARENTHESIS:
                $this->read();
                $this->parseExpression($field, $queries);
                if ($this->token === Lexer::T_CLOSE_PARENTHESIS) {
                    $this->read(); // else ignore parenthèse fermante qui manque
                }
                break;

            case Lexer::T_STAR:
                $this->read();
                $queries[] = $this->builder->all();
                break;

            case Lexer::T_END:
                break;
        }
    }

    protected function parseOr($field, array & $queries)
    {
        $clauses = [];
        $this->parseAnd($field, $clauses);
        while ($this->token === Lexer::T_OR) {
            $this->read();
            $this->parseAnd($field, $clauses); // c'était parseExpression dans Fooltext
        }
        $clauses && $queries[] = (count($clauses) === 1) ? $clauses[0] : $this->builder->bool($clauses);
    }

    protected function parseAnd($field, array & $queries)
    {
        $clauses = [];
        $this->parseNot($field, $clauses);
        while ($this->token === Lexer::T_AND) {
            $this->read();
            $this->parseNot($field, $clauses);
        }
        $clauses && $queries[] = (count($clauses) === 1) ? $clauses[0] : $this->builder->bool([], $clauses);
    }

    protected function parseNot($field, array & $queries)
    {
        $must = $not = [];
        $this->parseCompound($field, $must);
        while ($this->token === Lexer::T_NOT) {
            $this->read();
            $this->parseCompound($field, $not);
        }

        if (empty($not)) {
            $queries = array_merge($queries, $must);
        } else {
            $queries[] = $this->builder->bool([], $must, $not);
        }
    }
}
