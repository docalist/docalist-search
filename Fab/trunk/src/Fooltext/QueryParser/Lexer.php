<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  QueryParser
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id: Words.php 10 2011-12-13 15:45:47Z daniel.menard.35@gmail.com $
 */
namespace Fooltext\QueryParser;

use Fooltext\Indexing\Lowercase;
class Lexer
{
    const
        TOK_END = -1, TOK_BLANK = 1,
        TOK_AND = 10, TOK_OR = 11, TOK_AND_NOT = 12,
        TOK_NEAR = 20, TOK_ADJ = 21,
        TOK_LOVE = 30, TOK_HATE = 31,
        TOK_INDEX_NAME = 40,
        TOK_TERM = 41, TOK_WILD_TERM = 42,
        TOK_PHRASE_TERM = 43, TOK_PHRASE_WILD_TERM = 44,
        TOK_MATCH_ALL = 45,
        TOK_START_PARENTHESE = 50, TOK_END_PARENTHESE = 51,
        TOK_RANGE_START = 60, TOK_RANGE_END = 61;

    protected static $tokenName=array
    (
        self::TOK_END => 'END',
        self::TOK_BLANK => 'BLANK',

        self::TOK_AND => 'AND',
        self::TOK_OR => 'OR',
        self::TOK_AND_NOT => 'AND_NOT',

        self::TOK_NEAR => 'NEAR',
        self::TOK_ADJ => 'ADJ',

        self::TOK_LOVE => 'LOVE',
        self::TOK_HATE => 'HATE',

        self::TOK_INDEX_NAME => 'INDEX_NAME',

        self::TOK_TERM => 'TERM',
        self::TOK_WILD_TERM => 'WILD_TERM',
        self::TOK_PHRASE_TERM => 'PHRASE_TERM',
        self::TOK_PHRASE_WILD_TERM => 'PHRASE_WILD_TERM',
        self::TOK_MATCH_ALL => 'MATCH_ALL',

        self::TOK_START_PARENTHESE => 'START_PARENTHESE',
        self::TOK_END_PARENTHESE => 'END_PARENTHESE',

        self::TOK_RANGE_START => 'RANGE_START',
        self::TOK_RANGE_END => 'RANGE_END'
    );

    protected $id;
    protected $token;

    /**
     * L'équation de recherche en cours d'analyse.
     *
     * @var string|null
     */
    protected $equation = null;

    /**
     * La position du caractère en cours au sein de $equation
     *
     * @var int
     */
    protected $position;

    /**
     * Un flag qui indique si on est au sein d'une expression entre guillemets ou non
     * @var string
     */
    protected $inString;

    /**
     * Liste des caractères dont on tient compte dans l'équation (après avoir appliqué
     * Lowercase). Tous les autres caractères sont ignorés.
     *
     * @var string
     */
    protected static $chars = "0123456789abcdefghijklmnopqrstuvwxyz§:@()*+-.\"\0";


    /**
     * Analyseur lexical des équations de recherche : retourne le prochaine token
     * de l'équation analysée.
     *
     * Lors du premier appel, read() doit être appellée avec l'équation à analyser. Les
     * appels successifs se font sans passer aucun paramètre.
     *
     * En sortie, read() intialise deux propriétés :
     * - id : le type du token reconnu (l'une des constantes self::TOK_*)
     * - token : le token lu
     *
     * @param string $text l'équation de recherche à analyser
     * @return int l'id obtenu (également stocké dans $this->id)
     */
    public function read($equation = null)
    {
        // Les mots reconnus comme opérateur et le token correspondant
        static $opValue=array
        (
            'et'   => self::TOK_AND,
            'ou'   => self::TOK_OR,
            'sauf' => self::TOK_AND_NOT,

            'and'  => self::TOK_AND,
            'or'   => self::TOK_OR,
            'not'  => self::TOK_AND_NOT,
            'but'  => self::TOK_AND_NOT, // ancien bis

            'near' => self::TOK_NEAR,
            'adj'  => self::TOK_ADJ
        );

        // Initialisation si on nous passe une nouvelle équation à parser
        if (!is_null($equation))
        {
            //$equation = str_replace(array('[',']'), array('"@break ', ' @break"'), $equation);
            $map = Lowercase::$map;
            unset($map['-']);
            $equation = strtr($equation, $map);

            //$equation = Utils::convertString($equation, 'queryparser');
            $equation = trim($equation) . "\0";
            $this->equation = $equation;
            $this->position = 0;
            $this->inString = false;
        }
        elseif(is_null($this->equation))
        {
            throw new \Exception('lexer non initialisé');
        }

        // Extrait le prochain token
        for(;;)
        {
            // Passe les blancs
            while(false === strpos(self::$chars, $this->equation[$this->position])) ++$this->position; // strcspn ?

            $this->token = $this->equation[$this->position];
            switch($this->token)
            {
                case "\0": return $this->id = self::TOK_END;
                case '+': ++$this->position; return $this->id = self::TOK_LOVE;
                case '-': ++$this->position; return $this->id = self::TOK_HATE;
                case '(': ++$this->position; return $this->id = self::TOK_START_PARENTHESE;
                case ')': ++$this->position; return $this->id = self::TOK_END_PARENTHESE;
                case '*': ++$this->position; return $this->id = self::TOK_MATCH_ALL;

                case ':':
                case '=': ++$this->position; break;
                // explication : la requête commence par ":" ou "=" qui servent normallement
                // pour indiquer les noms de champs. Comme ce n'est pas valide, on ignore les
                // caractères comme s'il s'agissait d'un blanc.

                case '"':
                    ++$this->position;
                    $this->inString = ! $this->inString;

                    // Fin de la chaine en cours : retourne un blanc (sinon "a b" "c d" est interprété comme "a b c d")
                    if (! $this->inString)
                    {
                        return $this->id=self::TOK_BLANK;
                    }

                    // Début d'une chaine : ignore les caractères spéciaux et retourne le premier mot
                    if (false === $pt = strpos($this->equation, '"', $this->position))
                    {
                        throw new \Exception('guillemet fermant non trouvé');
                    }
                    $len = $pt - $this->position;
                    $string = strtr(substr($this->equation, $this->position, $len), '+-():=[]', '       ');
                    $this->equation = substr_replace($this->equation, $string, $this->position, $len);
                    return $this->read();

                default:
                    $len = 1 + strspn($this->equation, 'abcdefghijklmnopqrstuvwxyz0123456789?*', $this->position + 1);
                    $this->token = substr($this->equation, $this->position, $len);
                    $this->position += $len;

                    // Un mot avec troncature à droite ?
                    if (strcspn($this->token, '?*')< strlen($this->token))
                    {
                        return $this->id = ($this->inString ? self::TOK_PHRASE_WILD_TERM : self::TOK_WILD_TERM);
                    }

                    // Un mot dans une phrase
                    if ($this->inString)
                    {
                        return $this->id = self::TOK_PHRASE_TERM;
                    }

                    // Un opérateur ?
                    if (isset($opValue[$this->token]))
                    {
                        return $this->id = $opValue[$this->token];
                    }

                    // Un nom d'index ?
                    while($this->equation[$this->position] === ' ') ++$this->position; // Espaces optionnels entre le nom d'index et le signe ":"
                    if ($this->equation[$this->position] === ':' || $this->equation[$this->position] === '=')
                    {
                        ++$this->position;
                        return $this->id = self::TOK_INDEX_NAME;
                    }

                    // Juste un mot
                    return $this->id = self::TOK_TERM;
            }
        }
    }

    /**
     * Retourne le code du dernier token retourné par read().
     *
     * @return string|null
     */
    public function getToken()
    {
        return $this->id;
    }

    /**
     * Retourne le nom d'un token ou le nom du dernier token lu par read() si aucun
     * paramètre n'est indiqué.
     *
     * @param unknown_type $token
     * @return Ambigous <string, multitype:string >
     */
    public function getTokenName($token = null)
    {
        if (is_null($token)) $token = $this->id;
        return isset(self::$tokenName[$token]) ? self::$tokenName[$token] : 'BAD TOKEN';
    }

    /**
     * Retourne le texte du dernier token retourné par read().
     *
     * @return string|null
     */
    public function getTokenText()
    {
        return $this->token;
    }

    public function dumpTokens($equation)
    {
        $this->read($equation);
        for($secu=0; $secu < 100; $secu++)
        {
            echo '<code>',$this->getTokenName(), ' : [', $this->getTokenText(), ']</code><br />';
            if ($this->getToken() === self::TOK_END) break;
            $this->read();
        }
    }
}