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

namespace Docalist\Search\SearchRequest;

use InvalidArgumentException;

/**
 * Gère le nombre de résultats par page à retourner dans la requête.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait SizeTrait
{
    /**
     * Nombre de réponses par page (10 par défaut).
     *
     * @var int
     */
    protected $size = 10;

    /**
     * Modifie le nombre de résultats par page.
     *
     * @param int $size Un entier >= 0.
     *
     * @return self
     */
    public function setSize($size)
    {
        $size = (int) $size;
        if ($size < 0) {
            throw new InvalidArgumentException('Incorrect size');
        }
        $this->size = $size;

        return $this;
    }

    /**
     * Retourne le nombre de résultats par page (10 par défaut).
     *
     * @return int Un entier >= 0
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Stocke le nombre de résultats à retourner dans la requête qui sera envoyée à Elasticsearch.
     *
     * @param array $request Le tableau contenant la requête à modifier.
     */
    protected function buildSizeClause(array & $request)
    {
        // On n'ajoute rien si c'est la valeur par défaut
        if (10 === $this->size) {
            return;
        }

        // Indique à Elasticsearch le nombre de hits à retourner
        $request['size'] = $this->size;
    }
}
