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
use Docalist\Search\Mapping\Field;
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
    final public static function buildMapping(Mapping $mapping): void
    {
        $mapping
            ->text(static::SEARCH_FIELD)
            ->setFeatures([Field::FULLTEXT])
            ->setDescription(__(
                "Recherche textuelle libre sur la date du post (date complète, année, année et mois).",
                'docalist-search'
            ));

        $mapping
            ->date(static::DATE_FILTER)
            ->setFeatures([Field::AGGREGATE, Field::FILTER, Field::EXCLUSIVE])
            ->setDescription(__(
                'Facette et filtre sur la date du post.',
                'docalist-search'
            ));

        $mapping
            ->keyword(static::HIERARCHY_FILTER)
            ->setFeatures([Field::AGGREGATE, Field::FILTER, Field::EXCLUSIVE])
            ->setLabel(__(
                "Facette hiérarchique et filtre sur la date du post (niveau 1=année, niveau 2=mois, niveau 3=jour.",
                'docalist-search'
            ));
    }

    /**
     * Indexe les données du champ post_date.
     *
     * @param int   $date       Date à indexer (date mysql de la forme "yyyy-mm-dd hh:mm:ss").
     * @param array $document   Document elasticsearch.
     */
    final public static function map(string $date, array & $document): void
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

        $document[static::SEARCH_FIELD] = [$year, $yearMonth, $yearMonthDay, $phrase1, $phrase2];
        // TODO : ajouter le nom du mois ?

        $document[static::DATE_FILTER] = $date;
        $document[static::HIERARCHY_FILTER] = $dateTime->format('Y/m/d');
    }
}
