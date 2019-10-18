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

use Docalist\Search\Aggregation\Bucket\TermsAggregation;
use Docalist\Search\Aggregation\TableBasedTrait;
use stdClass;

/**
 * Une agrégation de type "terms" qui traduit les termes obtenus en utilisant une table d'autorité docalist.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TableEntriesAggregation extends TermsAggregation
{
    use TableBasedTrait;

    /**
     * Constructeur
     *
     * @param string        $field          Champ sur lequel porte l'agrégation.
     * @param string|array  $tables         Nom des tables d'autorité utilisées pour convertir les termes en libellés.
     * @param array         $parameters     Autres paramètres de l'agrégation.
     * @param array         $options        Options d'affichage.
     */
    public function __construct($field, $tables, array $parameters = [], array $options = [])
    {
        parent::__construct($field, $parameters, $options);
        $this->setTables($tables);
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultOptions(): array
    {
        $options = parent::getDefaultOptions();

        // Tag à utiliser pour marquer le libellé des buckets invalides (ceux qui ne figurent pas dans la table)
        $options['bucket.invalid.tag']  = 'span';

        // Classe css des libellés de buckets invalides (non utilisé si 'bucket.invalid.tag' est vide)
        $options['bucket.invalid.css']  = 'invalid-table-entry';

        return $options;
    }

    public function getBucketLabel(stdClass $bucket): string
    {
        // Cas spécial "missing"
        if ($bucket->key === static::MISSING) {
            return $this->getMissingLabel();
        }

        // Récupère le libellé de l'entrée dans les tables d'autorité indiquées
        foreach ($this->getTables() as $table) {
            $label = $table->find('label', 'code=' . $table->quote($bucket->key));
            if ($label !== false) {
                return $label;
            }
        }

        // Entrée non trouvée, on le signale avec le tag et la classe css indiquées en option
        $tag = $this->getOption('bucket.invalid.tag');
        $css = $this->getOption('bucket.invalid.css');

        return $tag ? $this->renderTag($tag, $css ? ['class' => $css] : [], $bucket->key) : $bucket->key;
    }
}
