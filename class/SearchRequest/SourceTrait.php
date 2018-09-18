<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\SearchRequest;

use InvalidArgumentException;

/**
 * Contrôle les champs qui seront retournés pour chacun des hits obtenus.
 */
trait SourceTrait
{
    /**
     * Clause Elasticsearch indiquant les champs à retourner pour chaque hit.
     *
     * - false : ne retourner aucun champ (valeur par défaut).
     * - true  : retourner tous les champs
     * - string : une liste de champ ou de masques (exemple : "creation,title*,event.*")
     * - array : une liste de champ ou de masques (exemple : ['creation', 'title*', 'event.*'])
     *
     * @var bool|string|array
     */
    protected $source = false;

    /**
     * Définit le filtre utilisé par elasticsearch pour déterminer les champs qui seront retournés pour chaque hit.
     *
     * cf. https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-source-filtering.html
     *
     * Remarque : le filtre ne concerne que les champs présents dans les documents indexés. Les champs spéciaux de
     * elasticsearch (_index, _type, _id, etc.) sont toujours retournés pour chaque hit.
     *
     * @param bool|string|array $source Une clause indiquant les champs à retourner :
     * - false : ne retourner aucun champ (valeur par défaut).
     * - true  : retourner tous les champs
     * - string : une liste de champ ou de masques (exemple : "creation,title*,event.*")
     * - array : une liste de champ ou de masques (exemple : ['creation', 'title*', 'event.*'])
     *
     * @return self
     */
    public function setSource($source)
    {
        // Bool : true=tous les champs, false=aucun
        if (is_bool($source)) {
            $this->source = $source;

            return $this;
        }

        // String : une chaine contenant un ou plusieurs noms de champs, on traite comme un tableau
        if (is_string($source)) {
            return $this->setSource(explode(',', $source));
        }

        // Array : une liste de champs à inclure
        if (is_array($source)) {
            if (empty($source)) {
                return $this->setSource(false);
            }

            if (count($source) === 1) {
                return $this->setSource(reset($source));
            }

            $this->source = array_map('trim', $source);

            return $this;
        }

        // Argument incorrect
        throw new InvalidArgumentException('Invalid source filter, expected bool, string or array');
    }

    /**
     * Retourne une clause indiquant les champs que Elasticsearch doit retourner pour chaque hit.
     *
     * @return bool|string|array
     *
     * - false : aucun champ (valeur par défaut).
     * - true  : tous les champs
     * - string : une liste de champ ou de masques (exemple : "creation,title*,event.*")
     * - array : une liste de champ ou de masques (exemple : ['creation', 'title*', 'event.*'])
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Stocke dans la requête la clause indiquant à Elasticsearch les chaps à retourner.
     *
     * @param array $request Le tableau contenant la requête à modifier.
     */
    protected function buildSourceClause(array & $request)
    {
        $source = $this->getSource();
        ($source !== true) && $request['_source'] = $this->getSource();
    }
}
