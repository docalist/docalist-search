<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search\QueryParser;

/**
 * Analyseur lexical pour le QueryParser de docalist-search.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Lexer
{
    // Codes des tokens
    const T_END = 0;                // Fin de la chaine d'entrée
    const T_NONE = 1;               // Un blanc ou un séparateur
    const T_TERM = 2;               // Un terme (un mot)
    const T_PHRASE = 3;             // Un terme dans une phrase
    const T_FIELD = 4;              // Un nom de champ
    const T_OPEN_PARENTHESIS = 5;   // Une parenthèse ouvrante (début d'une sous-expression)
    const T_CLOSE_PARENTHESIS = 6;  // Une parenthèse fermante (fin d'une sous-expression)
    const T_PLUS = 7;               // Le signe "+" (terme requis)
    const T_MINUS = 8;              // Le signe "-" (terme prohibé)
    const T_AND = 9;                // L'opérateur booléen "et"
    const T_OR = 10;                // L'opérateur booléen "ou"
    const T_NOT = 11;               // L'opérateur booléen "not"
    const T_STAR = 12;              // L'étoile (isolée = match_all ou field:*)
    const T_PREFIX = 13;            // Un terme avec troncature (bonj*)
    const T_RANGE = 14;             // L'opérateur "range" (start..end)

    const TOK_PHRASE_WILD = 44;
    const TOK_RANGE_START = 60;
    const TOK_RANGE_END = 61;

    // Caractères ayant une signification particulière
    protected static $chars = [
        '+' => self::T_PLUS,
        '-' => self::T_MINUS,
    ];

    /**
     * Liste des caractères blancs.
     *
     * @var string
     */
    protected static $spaces = " \t\n\r\0\x0B";

    /**
     * Expression régulière utilisée pour reconnaître les mots.
     *
     * On utilise la séquence \w de pcre qui en mode unicode reconnaît (supposition) :
     * - \p{L}  : les lettres majuscules, minuscules, accentuées, ligatures (oe, ǲ, ǈ..), spéciales (ª, º...)
     * - \p{N}  : les chiffres, les nombres (Ⅳ, ⅳ...) et les caractères représentant des nombres (¹, ½, ¾, ⒈, ①...)
     * - \p{Pd} : les tirets (standard, cadratin, etc.)
     * - \p{Pc} : le signe "souligné" (et d'autres caractères de continuation qui ont un rôle similaire)
     *
     * Un mot peut également contenir (mais ne peut pas commencer par) :
     * - une apostrophe : aujourd'hui (U+0027), O’Connor (U+2019)
     * - un tiret (apparemment, pas inclut dans \w) : après-midi, self-control...
     * - un point : pour reconnaître les sigles : T.V.
     * - le signe '&' : AT&T
     * - le signe '+' : utile pour des mots comme C++, AB+, etc.
     *
     * Références :
     * - http://unicode.org/reports/tr18/#word
     * - http://www.fileformat.info/info/unicode/category/index.htm
     * - https://fr.wikipedia.org/wiki/Apostrophe_(typographie)#Codage_du_caract.C3.A8re_apostrophe
     *
     * Remarque : l'expression régulière n'est pas ancrée (pas de /A) car on l'utilise à la fois pour reconnaître les
     * termes simples et pour extraire les mots des phrases (cf. tokenize).
     *
     * Remarque :
     * ~[\p{L}\p{N}][\p{L}\p{Mn}\p{N}\p{Pc}&\']*[\+\#]{0,3}~u (http://phpir.com/special-interest-tokenisation/)
     * @var string
     */
    protected static $word = '~[\w][\w\'’\.&\*+/-]*~u';

    /**
     * Expression régulière utilisée pour reconnaître les phrases.
     *
     * @var string
     */
    protected static $phrase = '~"([^"]*)"~Au';

    /**
     * Expression régulière utilisée pour reconnaître les opérateurs booléens.
     *
     * @var string
     */
    protected static $operators = '~(AND|OR|NOT)\b~A'; // pas de /u : on n'a que de l'ascii dans la regex

    /**
     * Convertit les opérateurs booléens reconnus par $operators en token.
     *
     * @var array opérateur -> token
     */
    protected static $bool = [
        'AND'  => self::T_AND,
        'OR'   => self::T_OR,
        'NOT'  => self::T_NOT,
    ];

    /**
     * Expression régulière utilisée pour détecter un nom de champ suivi du signe ":".
     *
     * @var string
     */
    protected static $field = '~([a-z_][a-z0-9._-]+):~A'; // pas de /u : un nom de champ est un ident, pas d'accents

    /**
     * Expression régulière utilisée pour passer au caractère suivant quand rien n'est reconnu.
     *
     * @var string
     */
    protected static $anychar = '~.~Aus'; //

    /**
     * Découpe la chaine passée en paramètre en tokens.
     *
     * @param string $string
     *
     * @return array Un tableau de tokens de la forme [TOK_xxx, "token"]
     */
    public function tokenize($string)
    {
        // Simplifie la détection des ranges en remplaçant ".." par un caractère unique
        $range = chr(29); // ascii 29 ("group separator")
        $string = str_replace('..', $range, $string);

        // Initialisation
        $end = strlen($string);
        $position = 0;
        $tokens = [];
        $match = null;
        $word = self::$word . 'A'; // La version "commence par" (ancrée) de self::$word;
        $parenthesis = 0; // Nombre de parenthèses ouvrantes qui ont été rencontrées

        // Lance l'analyse lexicale
        while ($position < $end) {
            // Blancs (aucun token n'est généré, on les ignore)
            if ($len = strspn($string, self::$spaces, $position)) {
                $position += $len;

                continue;
            }

            $char = $string[$position];

            // Parenthèse ouvrante
            if ($char === '(') {
                ++$position;
                $tokens[] = [self::T_OPEN_PARENTHESIS, $char];
                ++$parenthesis;

                continue;
            }

            // Parenthèse fermante : ignore celles qui sont en trop
            if ($char === ')') {
                ++$position;
                if ($parenthesis) {
                    $tokens[] = [self::T_CLOSE_PARENTHESIS, $char];
                    --$parenthesis;
                }

                continue;
            }

            // Etoile isolée
            if ($char === '*') {
                ++$position;
                $last = empty($tokens) ? null : end($tokens)[0];
                if ($last === null || $last === self::T_FIELD) {
                    $tokens[] = [self::T_STAR, $char];
                }

                continue;
            }

            // Caractères spéciaux
            if (isset(self::$chars[$char])) {
                $tokens[] = [self::$chars[$char], $char];
                ++$position;

                continue;
            }

            // Range
            if ($char === $range) {
                $tokens[] = [self::T_RANGE, '..'];
                ++$position;

                continue;
            }

            // Phrases
            if (preg_match(self::$phrase, $string, $match, 0, $position)) {
                $position += strlen($match[0]);

                // Extrait les mots de la phrase. Si la phrase est vide (" "), on l'ignore.
                if (preg_match_all(self::$word, $match[0], $match)) {
                    foreach ($match[0] as $match) {
                        $tokens[] = [self::T_PHRASE, $match];
                    }
                    $tokens[] = [self::T_NONE, null];
                    // On émet T_NONE à la fin de chaque phrase pour séparer deux phrases qui se suivent
                    // sinon, '"a b" "c"' serait interprété comme "a b c" par le parser.
                }

                continue;
            }

            // Opérateurs booléens
            if (preg_match(self::$operators, $string, $match, 0, $position)) {
                $tokens[] = [self::$bool[$match[1]], $match[1]];
                $position += strlen($match[0]);

                continue;
            }

            // Nom de champ
            if (preg_match(self::$field, $string, $match, 0, $position)) {
                $tokens[] = [self::T_FIELD, $match[1]];
                $position += strlen($match[0]);

                continue;
            }

            // Un mot éventuellement suivi de "*" (troncature)
            if (preg_match($word, $string, $match, 0, $position)) {
                $token = self::T_TERM;
                $position += strlen($match[0]);
                if (substr($match[0], -1) === '*') {
                    $token = self::T_PREFIX;
                    $match[0] = rtrim($match[0], '*');
                }
                $tokens[] = [$token, $match[0]];

                continue;
            }

            // Avance au caractère (unicode) suivant
            if (preg_match(self::$anychar, $string, $match, 0, $position)) {
                $position += strlen($match[0]);
            } else {
                break;
            }
        }

        // S'il manque des parenthèses fermantes, on les ajoute
        while ($parenthesis) {
            $tokens[] = [self::T_CLOSE_PARENTHESIS, ')'];
            --$parenthesis;
        }

        // Ajoute le token "fin de la chaine d'entrée"
        $tokens[] = [self::T_END, null];

        // Terminé
        return $tokens;
    }

    /**
     * Retourne le nom du token passé en paramètre.
     *
     * @param int $token La valeur d'une des constantes T_xxx définies par cette classe.
     *
     * @return string Le nom de la constante qui définit ce token.
     *
     * Remarque : si la valeur passée en paramètre n'est pas reconnue, elle est retournée telle quelle.
     */
    public function getTokenName($token) // Literals
    {
        static $name = [
            self::T_END => 'T_END',
            self::T_NONE => 'T_NONE',
            self::T_TERM => 'T_TERM',
            self::T_PHRASE => 'T_PHRASE',
            self::T_FIELD => 'T_FIELD',
            self::T_OPEN_PARENTHESIS => 'T_OPEN_PARENTHESIS',
            self::T_CLOSE_PARENTHESIS => 'T_CLOSE_PARENTHESIS',
            self::T_PLUS => 'T_PLUS',
            self::T_MINUS => 'T_MINUS',
            self::T_AND => 'T_AND',
            self::T_OR => 'T_OR',
            self::T_NOT => 'T_NOT',
            self::T_STAR => 'T_STAR',
            self::T_PREFIX => 'T_PREFIX',
            self::T_RANGE => 'T_RANGE',
        ];

        return isset($name[$token]) ? $name[$token] : $token;
    }
}
