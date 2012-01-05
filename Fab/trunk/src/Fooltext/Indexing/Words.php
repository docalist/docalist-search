<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Indexing
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Indexing;

use Fooltext\Indexing\AnalyzerInterface;

/**
 * Indexe les mots du texte.
 *
 * Les caractères [a-z0-9@_] sont utilisés pour découper le texte en mots.
 * Tous les autres caractères sont ignorés.
 *
 * Les sigles de 2 à 9 lettres sont convertis en mots.
 *
 * Cet analyseur ne fonctionne que sur du texte préalablement convertit en
 * minuscules non accentuées : dans votre chaine d'analyse, vous devez au
 * préalable utiliser un analyseur tel que {@link \Fooltext\Indexing\Lowercase}
 * ou {@link \Fooltext\Indexing\StripTags}.
 */
class Words implements AnalyzerInterface
{
    /**
     * Propriété de AnalyzerData dans laquelle seront stockés les termes.
     *
     * @var string
     */
    protected static $destination = 'terms';

    public function analyze(AnalyzerData $data)
    {
        $terms = & $data->{static::$destination};

        foreach ($data->content as $value)
        {
            // Convertit les sigles en mots
            $value = preg_replace_callback('~(?:[a-z0-9]\.){2,9}~i', array(__CLASS__, 'acronymToTerm'), $value);

            // Génère les tokens
            $terms[] = str_word_count($value, 1, '0123456789@_'); // 0..9@_
        }
    }

    /**
    * Fonction utilitaire utilisée par {@link tokenize()} pour convertir
    * les acronymes en mots
    *
    * @param array $matches
    * @return string
    */
    protected static function acronymToTerm($matches)
    {
        return str_replace('.', '', $matches[0]);
    }
}