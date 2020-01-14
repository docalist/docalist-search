<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2020 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search\Mapping\Field;

use Docalist\Search\Mapping\Field;
use Docalist\Search\Mapping\Options;

/**
 * Un champ de type date/heure.
 *
 * Les valeurs acceptées sont des chaines contenant une date et une heure optionnelle. Les formats acceptés sont ceux
 * retournés par la méthode getFormats().
 *
 * En interne, la date est stockée sous la forme d'un entier long représentant le nombre de millisecondes écoulées
 * depuis epoch (important : millisecondes, pas secondes).
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/date.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class DateField extends Field
{
    /**
     * {@inheritDoc}
     */
    final public function getSupportedFeatures(): int
    {
        return self::FILTER | self::EXCLUSIVE | self::AGGREGATE | self::SORT;
    }

    /**
     * {@inheritDoc}
     */
    final public function getMapping(Options $options): array
    {
        // Génère le mapping de base
        $mapping = parent::getMapping($options);

        // Type de champ
        $mapping['type'] = 'date';

        // Format
        $mapping['format'] = implode('||', $this->getFormats());
        $mapping['ignore_malformed'] = true;

        // Ok
        return $mapping;
    }


    /**
     * Retourne la liste des formats acceptés.
     *
     * @return string[]
     */
    final public function getFormats()
    {
        static $formats;

        // Si on a déjà initialisé $formats, terminé
        if (! is_null($formats)) {
            return $formats;
        }

        // Liste des formats de date supportés. La valeur associée indique si on peut avoir l'heure après
        // @see https://fr.wikipedia.org/wiki/Date#Variations_par_pays
        $dates = [
            // big endian
            'yyyy-MM-dd' => true, 'yyyy-MM' => false,
            'yyyy/MM/dd' => true, 'yyyy/MM' => false,
            'yyyy.MM.dd' => true, 'yyyy.MM' => false,
            'yyyyMMdd'   => true, 'yyyyMM'  => false,
            'yyyy'       => false, // important : en dernier sinon "19870101" matche 'yyyy' au lieu de 'yyyyMMdd'

            // little endian
            'dd-MM-yyyy' => true, 'MM-yyyy' => false,
            'dd/MM/yyyy' => true, 'MM/yyyy' => false,
            'dd.MM.yyyy' => true, 'MM.yyyy' => false,
            // ddMMyyyy et MMyyyy : non disponibles car ça donnerait le même format que yyyyMMdd et yyyyMM
        ];

        // Liste des formats d'heure supportés
        $times = [
            'HH:mm:ss',
            'HH:mm', "HH'h'mm",
        ];
        // Remarque : joda est insensible à la casse donc on n'a pas besoin de gérer "H" en majuscule
        // Source : http://joda-time.sourceforge.net/apidocs/org/joda/time/format/DateTimeFormatterBuilder.html#appendLiteral(char)

        // Initialise la liste des formats
        $formats = [];
        foreach ($dates as $date => $canHaveTime) {
            $formats[] = $date;
            if ($canHaveTime) {
                foreach ($times as $time) {
                    $formats[] = $date . ' ' . $time;
                }
            }
        }

        // Ok
        return $formats;
    }
}
