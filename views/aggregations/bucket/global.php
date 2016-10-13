<?php
/**
 * This file is part of the 'Docalist Search' plugin.
*
* Copyright (C) 2016-2016 Daniel Ménard
*
* For copyright and license information, please view the
* LICENSE.txt file that was distributed with this source code.
*
* @package     Docalist
* @subpackage  Search
* @author      Daniel Ménard <daniel.menard@laposte.net>
*/
namespace Docalist\Search\Views\Aggregation\Metrics;

use Docalist\Search\Aggregation\Bucket\GlobalAggregation;

/**
 * Vue par défaut pour les agrégations "terms".
 *
 * Remarque : les paramètres standards (container, title...) peuvent être passés à la vue mais ils ne sont pas
 * utilisés : l'agrégation 'global' se contente d'appeler la méthode display() pour chacune des sous-agrégations
 * qu'elle contient.
 *
 * @var GlobalAggregation   $this       L'agrégation à afficher.
 * @var array               $view       Les paramètres passés à la vue.
 */
false && $view = $view; // juste pour éviter warning IDE (ne gère pas bien une @var utilisée comme tableau)
$this->prepareBucket($this->result);
foreach($this->getAggregations() as $aggregation) {
    $aggregation->display($view['data']);
}
