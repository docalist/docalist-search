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

/**
 * Remplace les entités html/xml (&amp;acirc; &amp;quot; &amp;#039; ...) par le caractère correspondant
 * Supprime tous les tags html/xml.
 * Supprime les commentaires (&lt;!-- --&gt;) et les directives (&lt;?xxx &gt;).
 * Applique {@link \Fooltext\Indexing\Lowercase} pour convertir le texte en minuscules non accentuées.
 */
class StripTags extends Lowercase
{
    public function analyze(AnalyzerData $data)
    {
        foreach ($data->content as & $value)
        {
            $value = strip_tags($value); // supprime aussi les commentaires (<!-- -->) et les directives (<?xxx >)
            $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        }
        parent::analyze($data);
    }
}