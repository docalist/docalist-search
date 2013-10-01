<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist;

/**
 * Tokenizer
 *
 * Convertit un texte en minuscules non accentuées
 */
class Tokenizer {
    /**
     * Table de conversion des caractères.
     *
     * @var array
     */
    public static $map = [
        // U0000 - Latin de base (http://fr.wikipedia.org/wiki/Table_des_caractères_Unicode/U0000)
        'A' => 'a',    'B' => 'b',    'C' => 'c',    'D' => 'd',    'E' => 'e',    'F' => 'f',
        'G' => 'g',    'H' => 'h',    'I' => 'i',    'J' => 'j',    'K' => 'k',    'L' => 'l',
        'M' => 'm',    'N' => 'n',    'O' => 'o',    'P' => 'p',    'Q' => 'q',    'R' => 'r',
        'S' => 's',    'T' => 't',    'U' => 'u',    'V' => 'v',    'W' => 'w',    'X' => 'x',
        'Y' => 'y',    'Z' => 'z',

        // U0080 - Supplément Latin-1 (http://fr.wikipedia.org/wiki/Table_des_caractères_Unicode/U0080)
        'À' => 'a',    'Á' => 'a',    'Â' => 'a',    'Ã' => 'a',    'Ä' => 'a',    'Å' => 'a',
        'Æ' => 'ae',   'Ç' => 'c',    'È' => 'e',    'É' => 'e',    'Ê' => 'e',    'Ë' => 'e',
        'Ì' => 'i',    'Í' => 'i',    'Î' => 'i',    'Ï' => 'i',    'Ð' => 'd',    'Ñ' => 'n',
        'Ò' => 'o',    'Ó' => 'o',    'Ô' => 'o',    'Õ' => 'o',    'Ö' => 'o',    'Ø' => 'o',
        'Ù' => 'u',
        'Ú' => 'u',    'Û' => 'u',    'Ü' => 'u',    'Ý' => 'y',    'Þ' => 'th',   'ß' => 'ss',
        'à' => 'a',    'á' => 'a',    'â' => 'a',    'ã' => 'a',    'ä' => 'a',    'å' => 'a',
        'æ' => 'ae',   'ç' => 'c',    'è' => 'e',    'é' => 'e',    'ê' => 'e',    'ë' => 'e',
        'ì' => 'i',    'í' => 'i',    'î' => 'i',    'ï' => 'i',    'ð' => 'd',    'ñ' => 'n',
        'ò' => 'o',    'ó' => 'o',    'ô' => 'o',    'õ' => 'o',    'ö' => 'o',    'ø' => 'o',
        'ù' => 'u',    'ú' => 'u',    'û' => 'u',    'ü' => 'u',    'ý' => 'y',    'þ' => 'th',
        'ÿ' => 'y',

        // U0100 - Latin étendu A (http://fr.wikipedia.org/wiki/Table_des_caractères_Unicode/U0100)
        'Ā' => 'a',    'ā' => 'a',    'Ă' => 'a',    'ă' => 'a',    'Ą' => 'a',    'ą' => 'a',
        'Ć' => 'c',    'ć' => 'c',    'Ĉ' => 'c',    'ĉ' => 'c',    'Ċ' => 'c',    'ċ' => 'c',
        'Č' => 'c',    'č' => 'c',    'Ď' => 'd',    'ď' => 'd',    'Đ' => 'd',    'đ' => 'd',
        'Ē' => 'e',    'ē' => 'e',    'Ĕ' => 'e',    'ĕ' => 'e',    'Ė' => 'e',    'ė' => 'e',
        'Ę' => 'e',    'ę' => 'e',    'Ě' => 'e',    'ě' => 'e',    'Ĝ' => 'g',    'ĝ' => 'g',
        'Ğ' => 'g',    'ğ' => 'g',    'Ġ' => 'g',    'ġ' => 'g',    'Ģ' => 'g',    'ģ' => 'g',
        'Ĥ' => 'h',    'ĥ' => 'h',    'Ħ' => 'h',    'ħ' => 'h',    'Ĩ' => 'i',    'ĩ' => 'i',
        'Ī' => 'i',    'ī' => 'i',    'Ĭ' => 'i',    'ĭ' => 'i',    'Į' => 'i',    'į' => 'i',
        'İ' => 'i',    'ı' => 'i',    'Ĳ' => 'ij',   'ĳ' => 'ij',   'Ĵ' => 'j',    'ĵ' => 'j',
        'Ķ' => 'k',    'ķ' => 'k',    'ĸ' => 'k',    'Ĺ' => 'l',    'ĺ' => 'l',    'Ļ' => 'l',
        'ļ' => 'l',    'Ľ' => 'L',    'ľ' => 'l',    'Ŀ' => 'l',    'ŀ' => 'l',    'Ł' => 'l',
        'ł' => 'l',    'Ń' => 'n',    'ń' => 'n',    'Ņ' => 'n',    'ņ' => 'n',    'Ň' => 'n',
        'ň' => 'n',    'ŉ' => 'n',    'Ŋ' => 'n',    'ŋ' => 'n',    'Ō' => 'O',    'ō' => 'o',
        'Ŏ' => 'o',    'ŏ' => 'o',    'Ő' => 'o',    'ő' => 'o',    'Œ' => 'oe',   'œ' => 'oe',
        'Ŕ' => 'r',    'ŕ' => 'r',    'Ŗ' => 'r',    'ŗ' => 'r',    'Ř' => 'r',    'ř' => 'r',
        'Ś' => 's',    'ś' => 's',    'Ŝ' => 's',    'ŝ' => 's',    'Ş' => 's',    'ş' => 's',
        'Š' => 's',    'š' => 's',    'Ţ' => 't',    'ţ' => 't',    'Ť' => 't',    'ť' => 't',
        'Ŧ' => 't',    'ŧ' => 't',    'Ũ' => 'u',    'ũ' => 'u',    'Ū' => 'u',    'ū' => 'u',
        'Ŭ' => 'u',    'ŭ' => 'u',    'Ů' => 'u',    'ů' => 'u',    'Ű' => 'u',    'ű' => 'u',
        'Ų' => 'u',    'ų' => 'u',    'Ŵ' => 'w',    'ŵ' => 'w',    'Ŷ' => 'y',    'ŷ' => 'y',
        'Ÿ' => 'y',    'Ź' => 'Z',    'ź' => 'z',    'Ż' => 'Z',    'ż' => 'z',    'Ž' => 'Z',
        'ž' => 'z',    'ſ' => 's',

        // U0180 - Latin étendu B (http://fr.wikipedia.org/wiki/Table_des_caractères_Unicode/U0180)
        // Voir ce qu'il faut garder : slovène/croate, roumain,
        // 'Ș' => 's',    'ș' => 's',    'Ț' => 't',    'ț' => 't',   // Supplément pour le roumain

        // U20A0 - Symboles monétaires (http://fr.wikipedia.org/wiki/Table_des_caractères_Unicode/U20A0)
        // '€' => 'E',

        // autres symboles monétaires : Livre : 00A3 £, dollar 0024 $, etc.

        // Caractères dont on ne veut pas dans les mots
        // str_word_count inclut dans les mots les caractères a-z, l'apostrophe et le tiret.
        // on ne veut conserver que les caractères a-z. Neutralise les deux autres
        "'" => ' ',    '-' => ' ',
    ];

    public static function tokenize($text)
    {
        $text = strtr($text, self::$map);
        $text = str_word_count($text, 1, '0123456789@_');

        return $text;
    }
}