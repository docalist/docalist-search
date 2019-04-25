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

namespace Docalist\Search\Mapping\Field\Parameter;

use Docalist\Search\Mapping\Options;

/**
 * Gère le paramètre "index_options" d'un champ de mapping.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/index-options.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface IndexOptions
{
    /**
     * index_options : pour chaque terme, stocke l'ID des documents qui contiennent ce terme.
     *
     * term1 => doc1, doc2
     * term2 => doc2, doc3
     *
     * Permet de faire des recherches booléennes (quels sont les documents qui contiennent tel terme ?)
     *
     * @var string
     */
    const INDEX_DOCS = 'docs';

    /**
     * index_options : stocke en plus la fréquence du terme au sein de chaque document.
     *
     * term1 => doc1 (freq:1), doc2 (freq:2)
     * term2 => doc2 (freq:1), doc3 (freq:3)
     *
     * Lors d'une recherche, un terme répété plusieurs fois aura plus de poids.
     *
     * @var string
     */
    const INDEX_DOCS_AND_FREQS = 'freqs';

    /**
     * index_options : stocke en plus le numéro d'ordre de chaque occurence du terme.
     *
     * term1 => doc1 (freq:1, pos:[1]), doc2 (freq:2, pos:[2,4])
     * term2 => doc2 (freq:1, pos:[2]), doc3 (freq:3, pos:[3,7,9])
     *
     * Permet de faire des recherches positionnelles (proximité, recherche par phrase, etc.)
     *
     * @var string
     */
    const INDEX_DOCS_FREQS_AND_POSITIONS = 'positions';

    /**
     * index_options : stocke en plus l'offset de début et l'offset de fin de chaque occurence du terme.
     *
     * term1 => doc1 (freq:1, pos:[1], off:[4]), doc2 (freq:2, pos:[2,4], off:[4,16])
     * term2 => doc2 (freq:1, pos:[2], off:[1]), doc3 (freq:3, pos:[3,7,9], off:[10,27,48])
     *
     * Permet d'accélérer le module highlighter (génération d'extraits, mise en surbrillance des termes).
     *
     * @var string
     */
    const INDEX_DOCS_FREQS_POSITIONS_AND_OFFSETS = 'offsets';

    /**
     * Modifie les options d'indexation du champ.
     *
     * @param string $indexOptions
     *
     * @return self
     */
    public function setIndexOptions(string $indexOptions); // pas de return type en attendant covariant-returns

    /**
     * Retourne les options d'indexation du champ.
     *
     * @return string
     */
    public function getIndexOptions(): string;
}
