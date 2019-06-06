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

namespace Docalist\Search\Indexer\Field;

use Docalist\Search\Mapping;
use DateTime;

/**
 * Indexeur pour le champ post_date.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class PostDateIndexer // pas final, surchargée par PostModifiedIndexer
{
    /**
     * Nom du champ de recherche.
     *
     * @var string
     */
    public const SEARCH_FIELD = 'creation';

    /**
     * Nom du filtre sur le code.
     *
     * @var string
     */
    public const DATE_FILTER = 'filter.creation';

    /**
     * Nom du filtre hiérarchique.
     *
     * @var string
     */
    public const HIERARCHY_FILTER = 'hierarchy.creation';

    /**
     * Construit le mapping du champ post_date.
     *
     * @param Mapping $mapping
     */
    public static function buildMapping(Mapping $mapping): void // pas final, surchargée par PostModifiedIndexer
    {
        $mapping
            ->literal(static::SEARCH_FIELD)
            ->setFeatures(Mapping::FULLTEXT)
            ->setLabel(__(
                "Recherche sur la date de création du post WordPress ou de la référence docalist.",
                'docalist-search'
            ))
            ->setDescription(strtr(
                __(
                    'Exemples :
                    <code>{field}:{year}</code>,
                    <code>{field}:{year}{month}</code>,
                    <code>{field}:{year}{month}{day}</code>,
                    <code>{field}:"{year}/{month}"</code>,
                    <code>{field}:"{month}/{year}"</code>,
                    <code>{field}:"{year}/{month}/{day}"</code>,
                    <code>{field}:"{day}/{month}/{year}"</code>.',
                    'docalist-search'
                ),
                [
                    '{field}' => static::SEARCH_FIELD,
                    '{year}' => date('Y'),
                    '{month}' => date('m'),
                    '{day}' => date('d'),
                ]
            ));

        $mapping
            ->date(static::DATE_FILTER)
            ->setFeatures(Mapping::AGGREGATE | Mapping::FILTER | Mapping::EXCLUSIVE | Mapping::SORT)
            ->setLabel(__(
                'Filtre sur la date de création du post WordPress ou de la référence docalist.',
                'docalist-search'
            ))
            ->setDescription(__(
                "La date est stockée sous la forme d'un nombre (secondes écoulées depuis epoch) qui peut
                être utilisé à la fois comme filtre, comme clé de tri ou comme agrégation.",
                'docalist-search'
            ));

        $mapping
            ->hierarchy(static::HIERARCHY_FILTER)
            ->setFeatures(Mapping::AGGREGATE | Mapping::FILTER)
            ->setLabel(__(
                "Filtre hiérarchique sur la date de création du post WordPress ou de la référence docalist.",
                'docalist-search'
            ))
            ->setDescription(__(
                "La date est stockée sous la forme d'un path année/mois/jour qui permet de créer une
                facette hiérarchique.",
                'docalist-search'
            ));
    }

    /**
     * Indexe les données du champ post_date.
     *
     * @param int   $date   Date à indexer (date mysql de la forme "yyyy-mm-dd hh:mm:ss").
     * @param array $data   Document elasticsearch.
     */
    final public static function buildIndexData(string $date, array & $data): void
    {
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        if (empty($dateTime)) {
            return;
        }

        $year = $dateTime->format('Y');             // Année ("2019")
        $yearMonth = $dateTime->format('Ym');       // Année + mois ("201904")
        $yearMonthDay = $dateTime->format('Ymd');   // Année + mois + jour ("20190408")
        $phrase1 = $dateTime->format('Y m d');      // Recherche par phrase ordre y m d ("2019 04 08")
        $phrase2 = $dateTime->format('d m Y');      // Recherche par phrase ordre y m d ("08 04 2019")

        $data[static::SEARCH_FIELD] = [$year, $yearMonth, $yearMonthDay, $phrase1, $phrase2];
        $data[static::DATE_FILTER] = $date;
        $data[static::HIERARCHY_FILTER] = $dateTime->format('Y/m/d');
    }
}
