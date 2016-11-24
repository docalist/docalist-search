<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Aggregation;

/**
 * Classe de base pour les agrégations de type "metrics".
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-metrics.html
 */
abstract class MetricsAggregation extends BaseAggregation
{
    /**
     * Constructeur
     *
     * @param string    $field          Champ sur lequel porte l'agrégation.
     * @param array     $parameters     Autres paramètres de l'agrégation.
     * @param array     $renderOptions  Options d'affichage.
     */
    public function __construct($field, array $parameters = [], array $renderOptions = [])
    {
        $parameters['field'] = $field;
        parent::__construct($parameters, $renderOptions);
    }

    public function getDefaultRenderOptions()
    {
        $options = parent::getDefaultRenderOptions();
        $options['title.tag'] = 'em';
        $options['title.before'] = false;
        $options['content.tag']  = 'span';
        $options['metric.decimals']  = 2; // Nombre de chiffres après la virgule.
        $options['metric.point']  = ','; // Caractère utilisé pour le point décimal.
        $options['metric.thousands']  = ' '; // Séparateur de milliers (espace insécable par défaut).
        $options['metric.format']  = '%s'; // Format final (exemple : '%s €' ou '$%s').
        $options['metric.zero']  = false; // Affiche ou non les valeurs zéro.

        // On pourrait utiliser :
        // metric.point     = $wp_locale->number_format['decimal_point']
        // metric.thousands = $wp_locale->number_format['thousands_sep']

        return $options;
    }

    /**
     * Formatte la valeur passée en paramètre.
     *
     * @param int|float $value
     *
     * @return string
     */
    public function formatValue($value)
    {
        // Arrondit la valeur au nombre de chiffres après la virgule qui figure dans les options.
        $value = round($value, $this->renderOptions['metric.decimals']);

        // On ne génère rien si la valeur est à 0 et que l'option 'metric.zero' est à false
        if (0 == $value && !$this->renderOptions['metric.zero']) {
            return '';
        }

        // Formatte le nombre en fonction des options d'affichage
        $value = number_format(
            $value,
            ($value == (int) $value) ? 0 : $this->renderOptions['metric.decimals'],
            $this->renderOptions['metric.point'],
            $this->renderOptions['metric.thousands']
        );

        // Retourne la valeur formattée
        return sprintf($this->renderOptions['metric.format'], $value);
    }
}
