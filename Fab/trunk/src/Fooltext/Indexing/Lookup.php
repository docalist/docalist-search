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
 * Cet analyseur crée une "table de lookup" pour le champ auquel il est ajouté.
 *
 * Une table de lookup permet de consulter la liste des entrées distinctes
 * qui figurent dans le champ. En général, cela n'a de sens que pour un champ
 * de type "articles".
 *
 * Le plus souvent, on souhaite que la table contienne les entrées sous leur
 * forme riche (majuscules et minuscules, accents, etc.) Dans ce cas, cet
 * analyseur doit figurer en tout début de chaine d'analyse, avant d'avoir
 * appliqué un analyseur tel que {@link \Fooltext\Indexing\Lowercase}
 * ou {@link \Fooltext\Indexing\StripTags} qui convertissent les caractères en
 * minuscules non accentuées.
 */
class Lookup implements AnalyzerInterface
{
    public function analyze(AnalyzerData $data)
    {
        $data->lookups = array_merge($data->lookups, $data->content);
    }
}