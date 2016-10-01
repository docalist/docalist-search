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

use Docalist\Search\Aggregation\Bucket\TermsAggregation;

/**
 * Vue par défaut pour les agrégations "terms".
 *
 * @var TermsAggregation    $this       L'agrégation à afficher.
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

    /*
    - hits : on peut toujours l'avoir
    - count : on ne peut l'avoir que si on a le bucket missing or ce n'est pas toujours les cas (dépend de size)
    - missing : on ne peut l'avoir que si on a le bucket missing
    - shown et hidden : on ne peut le calculer que si on affiche tous les buckets, et dans ce cas, shown=tout et hidden=0 donc pas d'intérêt.

        $missing = $this->getMissingBucket();
        $missing = is_null($missing) ? 0 : $missing->doc_count;     // Nb de fiches où le champ est vide
        $count = $hits - $missing;                                  // Nb de fiches où le champ est renseigné
        $hidden = $this->getResult('sum_other_doc_count');          // Nb de fiches qui ne sont pas dans les buckets affichés (ex. 10 buckets en tout mais seulement 5 affichés)
        $shown = $count - $hidden;                                  // Nb de fiches qui sont comptées dans les buckets affichés

        printf('<%s class="%s" data-hits="%d" data-count="%d" data-missing="%d" data-shown="%d" data-hidden="%d">',
            $container,
            $class,
            $hits,
            $count,
            $missing,
            $shown,
            $hidden
        );
    */

}

// Titre de la facette
printf('<h3>%s</h3>', $this->getTitle() ?: $this->getName());

// Liste des termes
echo '<ul>';
foreach ($buckets as $bucket) {
    $count = $bucket->doc_count;
    $term = $bucket->key;
    $label = $this->getBucketLabel($bucket);
    $class = $term;

    if ($term === static::MISSING) {
        $url = 'javascript:void(0)'; // plus tard : requête de la forme "_missing:field"
    } else {
        $searchUrl->hasFilter($field, $term) && $class .= ' filter-active';
        $url = $searchUrl->toggleFilter($field, $term);
    }

    printf(
        '<li class="%s" data-bucket="%s" data-count="%d"><a href="%s"><span>%s</span> <em>%d</em></a></li>',
        esc_attr($class), esc_attr($term), $count, esc_attr($url), $label, $count
    );
}
echo '</ul>';

// Fin du container
$container && printf('</%s>', $container);


// A propos de "sum_other_doc_count" :
// Le nom initialement prévu était "sum_of_other_buckets ".
// « it aims at making clear that it is the sum of the document counts for other buckets
//  and not the number of documents that have another term »
// = total_docs_in_parent - sum( doc_count in top terms)
// = the sum of the doc counts of the buckets that did not make it to the list of top buckets
//
// cf. https://github.com/elastic/elasticsearch/issues/5324#issuecomment-57782198
