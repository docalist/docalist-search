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

use Docalist\Search\Aggregation\Metrics\StatsAggregation;

/**
 * Vue par défaut pour les agrégations "stats".
 *
 * @var StatsAggregation    $this       L'agrégation à afficher.
 * @var string|false        $container  Optionnel, tag à générer pour le container (div par défaut), ou false.
 */

// On ne génère rien si on n'a pas de stats
$count = $this->getCount();
if (empty($count)) {
    return;
}

// Valeur par défaut des paramètres de la vue
!isset($container) && $container = 'div';

// Début du container
if ($container) {
    // Détermine les classes css à appliquer au container
    $class = sprintf('%s %s',                                   // "facet"
        $this->getType(),                                       // type de la facette (e.g. "facet-terms")
        $this->getName()                                        // nom de la facette (e.g. "facet-category")
        );

    // Génère le tag ouvrant du container
    printf('<%s class="%s">', $container, $class);
}

// Génère les stats
$details = sprintf('%s fiche(s), min %s, max %s, moyenne %s',
    (string) $count,
    $this->formatValue($this->getMin()),
    $this->formatValue($this->getMax()),
    $this->formatValue($this->getAvg())
);

printf(
    '<span title="%s">%s</span> <em>%s</em>',
    esc_attr($details),
    $this->formatValue($this->getSum()),
    $this->getTitle() ?: $this->getName()
);

// Fin du container
$container && printf('</%s>', $container);
