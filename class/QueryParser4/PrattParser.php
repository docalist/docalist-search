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
 * Classe de base pour l'analyseur de requêtes docalist-search.
 */
class PrattParser
{
    /*
     * Liens utiles :
     *
     * - L'algorithme de Vaughan-Pratt (Mathieu Turcotte)
     *   Une présentation en français de l'analyse par descente récursive et de l'algorithme de Pratt.
     *   http://mathieuturcotte.ca/textes/vaughan-pratt-expose/
     *
     * - Top Down Operator Precedence (Vaughan R. Pratt)
     *   Une republication en html du papier de 1973 de V. Pratt (l'original n'est plus dispo).
     *   https://tdop.github.io/
     *
     * - Top Down Operator Precedence (Douglas Crockford)
     *   L'article de D. Crockford qui a contribué à faire redécouvrir l'algorithme de Pratt (utilisé pour JSLint).
     *   http://javascript.crockford.com/tdop/tdop.html
     *
     * - TDOP / Pratt parser in pictures (Matthieu Lemerre)
     *   Un diagramme animé qui visualise le fonctionnement d'un Pratt Parser.
     *   http://l-lang.org/blog/TDOP---Pratt-parser-in-pictures/
     *
     * - Pratt Parsers: Expression Parsing Made Easy (Bob Nystrom)
     *   Un article sur l'intérêt et le fonctionnement d'un Pratt Parser pour analyser des expressions.
     *   http://journal.stuffwithstuff.com/2011/03/19/pratt-parsers-expression-parsing-made-easy/
     *
     * - Simple Top-Down Parsing in Python (Fredrik Lundh)
     *   Une implémentation assez complète en python pour parser un language complet.
     *   http://effbot.org/zone/simple-top-down-parsing.htm
     *
     * - Top-Down operator precedence parsing (Eli Bendersky)
     *   Présentation de l'algorihme de Pratt.
     *   http://eli.thegreenplace.net/2010/01/02/top-down-operator-precedence-parsing
     *
     * - Parsing Expressions by Recursive Descent (Theodore Norvell)
     *   Présente plusieurs méthodes d'analyse d'expression : décente récursive, Shunting Yard, Precedence Climbing
     *   et une version généralisée de l'algorithme Precedence Climbing.
     *   https://www.engr.mun.ca/~theo/Misc/exp_parsing.htm
     *
     * - Scato/cricket
     *   Un pratt parser en php.
     *   https://github.com/scato/cricket/tree/master/src/Cricket/CricketBundle/Parser
     *
     * - JMESPath.php
     *   Un pratt parser en php.
     *   https://github.com/jmespath/jmespath.php/blob/master/src/Parser.php
     *
     * - Zend Framework Plural Rule Parser
     *   Basé sur l'algorithme de Pratt.
     *   https://github.com/zendframework/zend-i18n/blob/develop/src/Translator/Plural/Parser.php
     */

    /**
     * La chaine de caractères à analyser.
     *
     * @var string
     */
    protected $string;

    /**
     * La position du prochain caractère dans la chaine analysée.
     *
     * @var int
     */
    protected $position;

    /**
     * Le token en cours, sous la forme d'un tableau [id, matches].
     *
     * @var array[]
     */
    protected $token;

    /**
     * Le Runtime chargé de générer quelque chose à partir de la chaine analysée.
     *
     * @var Runtime
     */
    protected $runtime;

    /**
     * Liste des symboles reconnus par l'analyseur.
     *
     * @var array
     */
    protected $symbols = [
    //  ID                  lbp     nud             led         pattern                 ignore ?
        'eof'       =>  [      0,   'emptyInput' ,  '',         '$'                             ],
        'space'     =>  [   9999,   '',             '',         '\s+',                  true    ],
    ];

    /**
     * Liste des options PCRE qui sont automatiquement ajoutées aux expressions régulières lorsqu'elles sont exécutées.
     *
     * - u (PCRE_UTF8) : le masque et la chaine analysée sont gérées comme de l'utf-8.
     * - A (PCRE_ANCHORED) : le masque est ancré de force à la position en cours dans la chaine analysée.
     * - s (PCRE_DOTALL) : le caractère '.' présent dans les masques matchent également les caractères CR/LF.
     * - x (PCRE_EXTENDED) : ignore les espaces présents dans les masques et autorise les commentaires (#)
     * - S (PCRE_STUDY) : optimise l'expression régulière compilée pour qu'elle soit plus efficace.
     *
     * @var string
     */
    const PCRE_MODIFIERS = 'uAsxS';

    /**
     * Crée un nouvel analyseur.
     *
     * @param Runtime $runtime Runtime à utiliser.
     */
    public function __construct(Runtime $runtime = null)
    {
        $this->runtime = $runtime ?: new Runtime();
    }

    /**
     * Analyse la chaine passée en paramètre et retourne le résultat généré par le runtime.
     *
     * @param string $string Chaine à analyser.
     *
     * @return mixed Le résultat généré par le runtime.
     */
    public function parse($string)
    {
        $this->string   = $string;
        $this->position = 0;
        $this->token    = $this->getNextToken();

        $result = [];
        while ($this->token[0] !== 'eof') {
            $result[] = $this->expression();
        }

        return $result;
    }

    /**
     * Analyse une expression et retourne le résultat généré par le runtime.
     *
     * Cette méthode est au coeur du Pratt Parser : elle "mange" les tokens et exécute les fonctions associées
     * à ceux-ci jusqu'à ce qu'elle rencontre un token avec une priorité supérieure à celle passée en paramètre.
     *
     * @param int $rbp Priorité en cours ("Right Binding Power" = précédence de l'expression à gauche).
     *
     * @return mixed
     */
    protected function expression($rbp = 0)
    {
        list($token, $value) = $this->token;            // Récupère le token en cours
        $this->token = $this->getNextToken();           // Lit le token suivant

        $left = $this->nud($token, $value);
        while ($rbp < $this->lbp($this->token[0])) {    // Tant que le token suivant n'a pas une priorité supérieure
            list($token, $value) = $this->token;        // Passe au token suivant
            $this->token = $this->getNextToken();
            $left = $this->led($token, $value, $left);
        }

        return $left;
    }

    /**
     * Retourne la priorité ("left Binding Power") du symbole indiqué.
     *
     * @param string $symbol Symbole recherché.
     *
     * @return int
     */
    protected function lbp($symbol)
    {
        return $this->symbols[$symbol][0];
    }

    /**
     * Exécute la fonction nud (Null Denotation) associée au symbole indiqué.
     *
     * @param string $symbol Symbole recherché.
     * @param mixed  $value  Valeur à transmettre à la fonction nud.
     *
     * @return mixed Le résultat généré par le runtime.
     */
    protected function nud($symbol, $value)
    {
        $nud = $this->symbols[$symbol][1];
        if (empty($nud)) {
            die('syntax error ' . $symbol);
        }

        return $this->$nud($value);
    }

    /**
     * Exécute la fonction led (Left Denotation) associée au symbole indiquée.
     *
     * @param string $symbol Symbole recherché.
     * @param mixed  $value  Valeur à transmettre à la fonction led.
     * @param mixed  $left   Résultat généré par le runtime pour l'expression qui précède.
     *
     * @return mixed Le résultat généré par le runtime.
     */
    protected function led($symbol, $value, $left)
    {
        $led = $this->symbols[$symbol][2];
        if (empty($led)) {
            die('Uknnown operator ' . $symbol);
        }

        return $this->$led($value, $left);
    }

    /**
     * Gère le cas où la chaine d'entrée est vide (ou ne contient que des tokens ignorés).
     *
     * @return mixed
     */
    protected function emptyInput()
    {
        return $this->runtime->emptyInput();
    }

    /**
     * Retourne le prochain token de la chaine analysée.
     *
     * @return array Un tableau de la forme [id, value].
     */
    protected function getNextToken()
    {
        $match = null;
        for (;;) {
            foreach ($this->symbols as $id => $symbol) {
                $re = '~' . $symbol[3] . '~' . self::PCRE_MODIFIERS;
                if (preg_match($re, $this->string, $match, 0, $this->position)) {
                    $this->position += strlen($match[0]);

                    if (!isset($symbol[4]) || !$symbol[4]) { // le flag 'ignore' n'est pas à true pour ce symbole
//                      return [$id, isset($match[1]) ? $match[1] : $match[0]];
                        return [$id, $match];
                    }

                    continue 2; // recommence au début de la liste des patterns si le pattern trouvé est ignoré
                }
            }

            // Avance au caractère (unicode) suivant
            if (preg_match('~.~Aus', $this->string, $match, 0, $this->position)) {
                $this->position += strlen($match[0]);
            } else {
                echo 'WARNING : match 1 char failed. pos= ', $this->position, " (bad utf8 ?)\n";
                $this->position = strlen($this->string); // utf8 mal formé ? abandonne en allant à la fin de la chaine
            }
        }
    }

    /**
     * Analyse la chaine passée en paramètre et retourne la liste des tokens obtenus.
     *
     * @param string $string La chaine à analyser.
     *
     * @return array[] Un tableau de tokens (chaque token est un tableau de la forme [id, value]).
     */
    public function tokenize($string)
    {
        $this->string   = $string;
        $this->position = 0;

        $tokens = [];
        for (;;) {
            $token = $this->getNextToken();
            $tokens[] = $token;
            if ($token[0] === 'eof') {
                break;
            }
        }

        return $tokens;
    }
}
