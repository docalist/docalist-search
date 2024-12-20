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

namespace Docalist\Search\Aggregation\Standard;

use Docalist\Search\Aggregation\Bucket\TermsAggregation;
use Docalist\Search\Indexer;
use Docalist\Search\Indexer\Field\CollectionIndexer;
use Docalist\Search\IndexManager;
use stdClass;

/**
 * Une agrégation standard de type "terms" sur le champ "in" qui retourne le nombre de documents pour chacune
 * des collections docalist-search indexées.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TermsIn extends TermsAggregation
{
    /**
     * Liste des indexeurs disponibles, indexés par nom de collection ('in').
     *
     * @var Indexer[]
     */
    private $collections;

    /**
     * Constructeur
     *
     * @param array $parameters     Autres paramètres de l'agrégation.
     * @param array $options        Options d'affichage.
     */
    public function __construct(array $parameters = [], array $options = [])
    {
        !isset($parameters['size']) && $parameters['size'] = 1000;
        !isset($options['title']) && $options['title'] = __('Type de contenu', 'docalist-search');
        parent::__construct(CollectionIndexer::FILTER, $parameters, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function getBucketLabel(stdClass $bucket): string
    {
        // Initialise la liste des collections au premier appel
        if (is_null($this->collections)) {
            /** @var IndexManager $indexManager */
            $indexManager = docalist(IndexManager::class);
            $this->collections = $indexManager->getCollections();
        }

        if (isset($this->collections[$bucket->key])) {
            return $this->collections[$bucket->key]->getLabel();
        }

        return $bucket->key;
    }
}
