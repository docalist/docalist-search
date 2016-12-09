<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Mapping\Field;

use Docalist\Search\Mapping\Field;

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
 */
class Date extends Field
{
    public function getDefaultParameters()
    {
        return [
            'type' => 'date',
            'format' => implode('||', $this->getFormats()),
            'ignore_malformed' => true,
        ];
    }

    /**
     * Retourne la liste des formats acceptés.
     *
     * @return string[]
     */
    public function getFormats()
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
