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
namespace Docalist\Search\QueryParser4;

/**
 * Analyseur pour les requêtes docalist-search.
 */
class QueryParser extends PrattParser
{
    /**
     * Liste des symboles gérés par l'analyseur.
     *
     * @var array
     */
    protected $symbols = [
    //  ID                  lbp     nud             led         pattern                 ignore ?
        'eof'       =>  [      0,   'emptyInput' ,  '',         '$'                             ],
        'space'     =>  [   9999,   '',             '',         '\s+',                  true    ],

        'field'     =>  [    0,   'field',        'field',         '([a-zA-Z0-9_]+):'              ],

        'or'        =>  [     60,   '',             'or',       'OR\b'                          ],
        'and'       =>  [     70,   '',             'and',      'AND\b'                         ],
        'not'       =>  [     80,   'pureNot',      'not',      'NOT\b'                         ], // '(?:AND\s+)?NOT\b'

        'range'     =>  [      0,   '',             '',         '([\w-/]+)\.\.([\w-/]+)'                  ],
        'torange'   =>  [      0,   '',             '',         '\[([\w-/]+)\s*TO\s*([\w-/]+)\]'        ],

        'wildcard'  =>  [     10,   '',             '',         '\w+[*?][\w*?]* | [*?]+\w[\w*?]*'          ],

        'term'      =>  [     0,   'terms',         '',         "\w[\w'’.&+/-]*"          ],
        'phrase'    =>  [     0,   'phrase',       '',         '"\s*([^"]*)\s*"'                     ],

        'asterisk'  =>  [      0,   '',             '',         '\*[?*]*'                            ],

        'lparen'   =>  [      0,   'lparen',        '',     '\('                            ],
        'rparen'   =>  [      0,   'rparen',        '',         '\)'                            ],

        'must'      =>  [     60,   'must',         '',    '\+'                            ],
        'mustnot'   =>  [     60,   'mustNot',      '',    '\-'                            ],
    ];
// '+' = the require operator
// '-' = the prohibit operator

//     0	non-binding operators like ;
//     10	assignment operators like =
//     20	?
//     30	|| &&
//     40	relational operators like ===
//     50	+ -
//     60	* /
//     70	unary operators like !
//     80	. [ (

/*
    const T_RANGE = 14;             // L'opérateur "range" (start..end)

    const TOK_PHRASE_WILD = 44;
    const TOK_RANGE_START = 60;
    const TOK_RANGE_END = 61;
*/
    public function __construct()
    {
//         parent::__construct(new MultiRuntime([
//             'debug' => new DebugRuntime(),
//             'result' => new EvalRuntime()
//         ]));
        parent::__construct(new QueryBuilder());
    }

    protected function field(array $match)
    {
        if ($this->token[0] === 'lparen') {
            $field = $this->runtime->getField();
            $this->runtime->setField($match[1]);
            $result = $this->expression();
            $this->runtime->setField($field);

            return $result;
        } else {
            $this->runtime->setField($match[1]);

            return $this->expression();
        }
    }

    protected function terms(array $match)
    {
        $terms = [$match[0]];
        return $this->runtime->terms($terms);
/*
        while($this->token[0] === 'term') {
            $terms[] = $this->token[1][0];
            $this->token = $this->getNextToken();
        }

        return $this->runtime->terms($terms);
*/
    }

    protected function phrase(array $match)
    {
        return $this->runtime->phrase($match[1]);
    }

    protected function and($value, $left)
    {
        return $this->runtime->and($left, $this->expression($this->lbp('and')));
    }

    protected function or($value, $left)
    {
        return $this->runtime->or($left, $this->expression($this->lbp('or')));
    }

    protected function not($value, $left)
    {
        return $this->runtime->not($left, $this->expression($this->lbp('not')));
    }

    protected function pureNot(array $match)
    {
        return $this->runtime->pureNot($this->expression($this->lbp('not')));
    }

    protected function must($value)
    {
        return $this->runtime->must($this->expression(/*$this->lbp('must')*/));
    }

    protected function mustNot($value)
    {
        return $this->runtime->mustNot($this->expression());
    }

    protected function lparen($value)
    {
        $field = $this->runtime->getField();
        $result = $this->expression(0);
        if ($this->token[0] !== 'rparen') {
            die('il manque une parenthèse fermante');
        }
        $this->token = $this->getNextToken();

        // Si le champ en cours a été modifié, on restaure le champ qui était actif avant "("
        if ($field !== $this->runtime->getField()) {
            $this->runtime->setField($field);
        }

        return $result;
    }

    protected function rparen($value)
    {
        return $this->expression();
    }
}
