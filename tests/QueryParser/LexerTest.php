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

namespace Docalist\Tests\Search\QueryParser;

use WP_UnitTestCase;
use Docalist\Search\QueryParser\Lexer as L;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class LexerTest extends WP_UnitTestCase
{
    /**
     * Vérifie qu'on gère correctement les caractères unicodes.
     */
    public function testUnicode()
    {
        $string = "salut! guàtertag أهلا Прывiтанне Здравей 你好 päivää mba'éichapa ŋlɩwa'lɛ سلام Здравствуйте สดี Вітаю";
        $tokens = [
            [L::T_TERM, "salut"],
            [L::T_TERM, "guàtertag"],
            [L::T_TERM, "أهلا"],
            [L::T_TERM, "Прывiтанне"],
            [L::T_TERM, "Здравей"],
            [L::T_TERM, "你好"],
            [L::T_TERM, "päivää"],
            [L::T_TERM, "mba'éichapa"],
            [L::T_TERM, "ŋlɩwa'lɛ"],
            [L::T_TERM, "سلام"],
            [L::T_TERM, "Здравствуйте"],
            [L::T_TERM, "สด"],
            [L::T_TERM, "Вітаю"],
            [L::T_END, null],
        ];
        $lexer = new L();
        $result = $lexer->tokenize($string);
        $this->assertSame($tokens, $result);
    }

    /**
     * Vérifie qu'on gère correctement les caractères spéciaux pouvant faire partie d'un "mot".
     */
    public function testWordChars()
    {
        $string="Aujourd'hui après-midi d'été à la campagne O’Connor UB_40 4non-blondes T.V. M6 at&t 24h C++ AB- 7zip";

        $tokens = [
            [L::T_TERM, "Aujourd'hui"],
            [L::T_TERM, "après-midi"],
            [L::T_TERM, "d'été"],
            [L::T_TERM, "à"],
            [L::T_TERM, "la"],
            [L::T_TERM, "campagne"],
            [L::T_TERM, "O’Connor"],
            [L::T_TERM, "UB_40"],
            [L::T_TERM, "4non-blondes"],
            [L::T_TERM, "T.V."],
            [L::T_TERM, "M6"],
            [L::T_TERM, "at&t"],
            [L::T_TERM, "24h"],
            [L::T_TERM, "C++"],
            [L::T_TERM, "AB-"],
            [L::T_TERM, "7zip"],
            [L::T_END, null],
        ];
        $lexer = new L();
        $result = $lexer->tokenize($string);
        $this->assertSame($tokens, $result);
    }

    /**
     * Vérifie que la requête passée en paramètre génère bien les tokens indiqués.
     *
     * @dataProvider queriesProvider
     */
    public function testTokenize($string, $tokens)
    {
        $lexer = new L();
        $this->assertSame($tokens, $lexer->tokenize($string));
    }

    /**
     * Requêtes de test pour testLexer().
     *
     * @return array
     */
    public function queriesProvider()
    {
        $end = [L::T_END, null];
        $none = [L::T_NONE, null];
        $term = [L::T_TERM, 'terme'];
        $prefix = [L::T_PREFIX, 'bonj'];
        $a = [L::T_TERM, 'a'];
        $b = [L::T_TERM, 'b'];
        $hello = [L::T_PHRASE, 'hello'];
        $world = [L::T_PHRASE, 'world'];
        $plus = [L::T_PLUS, '+'];
        $minus = [L::T_MINUS, '-'];
        $and = [L::T_AND, 'AND'];
        $or = [L::T_OR, 'OR'];
        $not = [L::T_NOT, 'NOT'];
        $status = [L::T_FIELD, 'status'];
        $open = [L::T_OPEN_PARENTHESIS, '('];
        $close = [L::T_CLOSE_PARENTHESIS, ')'];
        $all = [L::T_STAR, '*'];
        $range = [L::T_RANGE, '..'];

        return [
            // Vide
            [ '', [$end] ],
            [ " \t\n\r\0\x0B", [$end] ],
            [ '`\\^@]={}^¨$£¤%,?;.:/!§', [$end] ],

            // Terme
            [ 'terme', [$term, $end] ],
            [ ' terme ', [$term, $end] ],
            [ " \n\r   terme \n\r   ", [$term, $end] ],

            // Préfixe
            [ 'bonj*', [$prefix, $end] ],
            [ ' bonj* ', [$prefix, $end] ],
            [ 'bonj**', [$prefix, $end] ],

            // Non-préfixe
            [ 'terme *', [$term, $end] ],
            [ 'terme"*', [$term, $end] ],

            // Phrase
            [ ' "hello" ', [$hello, $none, $end] ],
            [ ' "hello world" ', [$hello, $world, $none, $end] ],
            [ ' "   hello world  " ', [$hello, $world, $none, $end] ],
            [ ' " « hello,;/:world!?" ', [$hello, $world, $none, $end] ],
            [ ' "hello world" "hello" "hello world"', [$hello, $world, $none, $hello, $none, $hello, $world, $none, $end] ],

            // Non-phrase
            [ ' "terme ', [$term, $end] ],
            [ ' terme" ', [$term, $end] ],
            [ ' "hello world" "terme ', [$hello, $world, $none, $term, $end] ],
            [ ' "hello world" terme" ', [$hello, $world, $none, $term, $end] ],

            // Love / hate
            [ '+a', [$plus, $a, $end ]],
            [ '-b', [$minus, $b, $end ]],
            [ '+"hello"', [$plus, $hello, $none, $end] ],
            [ '-"hello world"', [$minus, $hello, $world, $none, $end] ],

            [ 'a +b ', [$a, $plus, $b, $end] ],
            [ '+a b ', [$plus, $a, $b, $end] ],
            [ 'a -b ', [$a, $minus, $b, $end] ],
            [ '-a b ', [$minus, $a, $b, $end] ],

            [ ' +a +b ', [$plus, $a, $plus, $b, $end] ],
            [ ' -a -b ', [$minus, $a, $minus, $b, $end] ],
            [ ' +a -b ', [$plus, $a, $minus, $b, $end] ],
            [ ' -a +b ', [$minus, $a, $plus, $b, $end] ],
            [ ' +"hello world" +"hello" ', [$plus, $hello, $world, $none, $plus, $hello, $none, $end] ],
            [ ' -"hello world" -"hello" ', [$minus, $hello, $world, $none, $minus, $hello, $none, $end] ],

            // Non-opérateur
            [ ' a+b ', [[L::T_TERM, 'a+b'], $end] ],
            [ ' a-b ', [[L::T_TERM, 'a-b'], $end] ],
            [ '+"a', [$plus, $a, $end] ],
            [ '-"a b', [$minus, $a, $b, $end] ],
            [ 'C++', [[L::T_TERM, 'C++'], $end] ],
            [ 'C++C', [[L::T_TERM, 'C++C'], $end] ],
            [ 'O-O', [[L::T_TERM, 'O-O'], $end] ],


            // Opérateurs booléens - and / or / not
            [ ' a AND b ', [$a, $and, $b, $end] ],
            [ ' a AND b OR a NOT b', [$a, $and, $b, $or, $a, $not, $b, $end] ],

            // Non-booléen
            [ ' a and b ', [$a, [L::T_TERM, 'and'], $b, $end] ],
            [ ' a or b ', [$a, [L::T_TERM, 'or'], $b, $end] ],
            [ ' a not b ', [$a, [L::T_TERM, 'not'], $b, $end] ],

            [ ' a "AND" b ', [$a, [L::T_PHRASE, 'AND'], $none, $b, $end] ],
            [ ' a "OR" b ', [$a, [L::T_PHRASE, 'OR'], $none, $b, $end] ],
            [ ' a "NOT" b ', [$a, [L::T_PHRASE, 'NOT'], $none, $b, $end] ],

            [ ' "hello AND world" ', [$hello, [L::T_PHRASE, 'AND'], $world, $none, $end] ],
            [ ' "hello OR world" ', [$hello, [L::T_PHRASE, 'OR'], $world, $none, $end] ],
            [ ' "hello NOT world" ', [$hello, [L::T_PHRASE, 'NOT'], $world, $none, $end] ],

            // Sous-expressions
            [ '(a)', [$open, $a, $close, $end] ],
            [ '(a +b)', [$open, $a, $plus, $b, $close, $end] ],
            [ '+(a b)', [$plus, $open, $a, $b, $close, $end] ],
            [ '(((a b)))', [$open, $open, $open, $a, $b, $close, $close, $close, $end] ],

            // Parenthèses balancées : le lexer ignore les parenthèses fermantes en trop et ajoute celles qui manquent
            [ 'a)', [$a, $end] ],
            [ 'a)))', [$a, $end] ],
            [ '(a', [$open, $a, $close, $end] ],
            [ '(a))', [$open, $a, $close, $end] ],
            [ '((a', [$open, $open, $a, $close, $close, $end] ],
            [ '(a)) ((b)', [$open, $a, $close, $open, $open, $b, $close, $close, $end] ],

            // Champs
            [ 'status:terme ', [$status, $term, $end] ],
            [ ' status:"hello" ', [$status, $hello, $none, $end] ],
            [ 'status:"hello world" ', [$status, $hello, $world, $none, $end] ],
            [ 'status:(a b)', [$status, $open, $a, $b, $close, $end] ],

            // Match all
            [ '*', [$all, $end] ],
            [ 'status:* ', [$status, $all, $end] ],
            [ '***', [$all, $end] ],
            [ 'status:*** ', [$status, $all, $end] ],

            // Non-match all
            [ '(*)', [$open, $close, $end] ],
            [ 'terme : * ', [$term, $end] ],

            // Divers
            [ '3*2', [[L::T_TERM, '3*2'], $end] ],
            [ '3+2', [[L::T_TERM, '3+2'], $end] ],
            [ '3-2', [[L::T_TERM, '3-2'], $end] ],

            [ '3 * 2', [[L::T_TERM, '3'], [L::T_TERM, '2'], $end] ],
         // [ '3 + 2', [[L::T_TERM, '3'], [L::T_TERM, '2'], $end] ],

            [ '..', [$range, $end] ],
            [ 'a..', [$a, $range, $end] ],
            [ '..a', [$range, $a, $end] ],
            [ 'a..b', [$a, $range, $b, $end] ],
            [ 'a ..', [$a, $range, $end] ],
            [ ' .. a', [$range, $a, $end] ],
            [ 'a .. b', [$a, $range, $b, $end] ],
        ];
    }

    /**
     * Teste la méthode getTokenName().
     *
     * On prend toutes les constantes qui commencent par "T_" et on vérifie que getTokenName() retourne leur nom.
     */
    public function testGetTokenNames()
    {
        $lexer = new L();

        $class = new \ReflectionObject($lexer);
        foreach($class->getConstants() as $name => $value) {
            if (substr($name, 0, 2) === 'T_') {
                $this->assertSame($name, $lexer->getTokenName($value));
            }
        }

        // Si on appelle getTokenName() avec autre chose qu'un token, elle retourne ce qu'on lui passe
        $this->assertSame(432512, $lexer->getTokenName(432512));
    }
}
