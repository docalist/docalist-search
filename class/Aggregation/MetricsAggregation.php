<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search\Aggregation;

/**
 * Classe de base pour les agrégations de type "metrics".
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-metrics.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class MetricsAggregation extends BaseAggregation
{
    /**
     * Constructeur
     *
     * @param string    $field          Champ sur lequel porte l'agrégation.
     * @param array     $parameters     Autres paramètres de l'agrégation.
     * @param array     $options        Options d'affichage.
     */
    public function __construct($field, array $parameters = [], array $options = [])
    {
        $parameters['field'] = $field;
        parent::__construct($parameters, $options);
    }

    public function getDefaultOptions()
    {
        $options = parent::getDefaultOptions();
        $options['title.tag'] = 'em';
        $options['title.before'] = false;
        $options['content.tag']  = 'span';
        $options['metric.decimals']  = 2; // Nombre de chiffres après la virgule.
        $options['metric.point']  = ','; // Caractère utilisé pour le point décimal.
        $options['metric.thousands']  = ' '; // Séparateur de milliers (espace insécable par défaut).
        $options['metric.format']  = '%s'; // Format final (exemple : '%s €' ou '$%s').
        $options['metric.zero']  = false; // Affiche ou non les valeurs zéro.

        // On pourrait aussi utiliser la propriété 'number_format' de la variable globale '$wp_locale' de WordPress
        // (paramètres 'decimal_point' et 'thousands_sep')

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
        $value = round($value, $this->options['metric.decimals']);

        // On ne génère rien si la valeur est à 0 et que l'option 'metric.zero' est à false
        if (0 == $value && !$this->options['metric.zero']) {
            return '';
        }

        // Formatte le nombre en fonction des options d'affichage
        $value = number_format(
            $value,
            ($value == (int) $value) ? 0 : $this->options['metric.decimals'],
            $this->options['metric.point'],
            $this->options['metric.thousands']
        );

        // Retourne la valeur formattée
        return sprintf($this->options['metric.format'], $value);
    }
}
