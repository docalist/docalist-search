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

use Docalist\Search\Aggregation\Bucket\RangeAggregation;

/**
 * Vue par défaut pour les agrégations "range".
 *
 * @var RangeAggregation    $this       L'agrégation à afficher.
 * @var string|false        $container  Optionnel, tag à générer pour le container (div par défaut), ou false.
 */

// On ne génère rien si on n'a pas de buckets
$buckets = $this->getBuckets();
if (empty($buckets)) {
    return;
}

// Valeur par défaut des paramètres de la vue
!isset($container) && $container = 'div';

// Initialisation
$field = $this->getParameter('field');
$searchUrl = $this->getSearchRequest()->getSearchUrl();

// ES génère les buckets même si le doc_count obtenu est à zéro.
// Comme on ne veut que les buckets pas "vides", il faut qu'on teste.
// Potentiellement, tous les buckets peuvent être vides, et dans ce cas, on ne veut pas générer le titre et le ul.
// Du coup, on génère les buckets dans une chaine et on n'affiche la facette que si on a quelque chose.
$items = '';
foreach ($buckets as $bucket) {
    $count = $bucket->doc_count;
    if ($count === 0) {
        continue;
    }
    $label = $this->getBucketLabel($bucket);

    $from = isset($bucket->from) ? $bucket->from : '';
    $to = isset($bucket->to) ? $bucket->to : '';

    $class = sprintf('range-%s-%s', $from ?: 'less-than', $to ?: 'and-more');

    $term = $from . '..' . $to;

    $searchUrl->hasFilter($field, $term) && $class .= ' filter-active';
    $url = $searchUrl->toggleFilter($field, $term);

    $items .= sprintf(
        '<li class="%s" data-from="%s" data-to="%s" data-count="%d"><a href="%s"><span>%s</span> <em>%d</em></a></li>',
        esc_attr($class), $from, $to, $count, esc_attr($url), $label, $count
    );
}

if (empty($items)) {
    return;
}

// Début du container
if ($container) {
    // Détermine les classes css à appliquer au container
    $class = sprintf('facet %s %s%s',                           // "facet"
        $this->getType(),                                       // type de la facette (e.g. "facet-terms")
        $this->getName(),                                       // nom de la facette (e.g. "facet-category")
        $searchUrl->hasFilter($field) ? ' facet-active' : ''    // "facet-active" si l'une des valeurs est filtrée
    );

    // Calcule les stats sur la facette
    $hits = $this->getSearchResponse()->getHitsCount();         // Nb total de hits pour la requête

    // Génère le tag ouvrant du container
    printf('<%s class="%s" data-hits="%d">', $container, $class, $hits);
}

// Titre de la facette
printf('<h3>%s</h3>', $this->getTitle() ?: $this->getName());

// Liste des termes
echo '<ul>', $items, '</ul>';

// Fin du container
$container && printf('</%s>', $container);
