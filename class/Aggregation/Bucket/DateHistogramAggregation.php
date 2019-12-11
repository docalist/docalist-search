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

namespace Docalist\Search\Aggregation\Bucket;

use Docalist\Search\Aggregation\Bucket\HistogramAggregation;
use stdClass;
use InvalidArgumentException;

/**
 * Une agrégation de type "histogram" spécialisée sur les dates.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/
 * search-aggregations-bucket-datehistogram-aggregation.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class DateHistogramAggregation extends HistogramAggregation
{
    const TYPE = 'date_histogram';

    /**
     * Constructeur
     *
     * @param string    $field          Champ sur lequel porte l'agrégation.
     * @param array     $interval       Taille de chacune des barres de l'histogramme généré.
     * @param array     $parameters     Autres paramètres de l'agrégation.
     * @param array     $options        Options d'affichage.
     */
    public function __construct(string $field, string $interval = 'year', array $parameters = [], array $options = [])
    {
        switch ($interval) {
            case 'year':
                $format = 'yyyy';
                break;

            case 'month':
                $format = 'yyyyMM';
                break;

            case 'day':
                $format = 'yyyyMMdd';
                break;

            default:
                throw new InvalidArgumentException('invalid interval');
        }
        $parameters['format'] = $format;
        $parameters['min_doc_count'] = 1;

        parent::__construct($field, $interval, $parameters, $options);
//         if ($esVersion >= 7.2) {
//             $this->setParameter('calendar_interval', $interval);
//             $this->setParameter('interval', null);
//         }
    }

    protected function getBucketFilter(stdClass $bucket): string
    {
        return $bucket->key_as_string;
    }

    public function getBucketLabel(stdClass $bucket): string
    {
        return $bucket->key_as_string;
    }
}
