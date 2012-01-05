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
 * Indexe les mots du texte et stocke leur position pour permettre
 * la recherche par phrase (expression entre guillemets) et la
 * recherche de proximité (opérateur NEAR).
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
class Phrases extends Words
{
    protected static $destination = 'postings';
}