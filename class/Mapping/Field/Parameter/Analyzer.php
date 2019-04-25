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
 * Gère le paramètre "analyzer" d'un champ de mapping.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/analyzer.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Analyzer
{
    /**
     * Modifie le nom de l'analyzer utilisé par le champ.
     *
     * @param string $analyzer
     *
     * @return self
     */
    public function setAnalyzer(string $analyzer); // pas de return type en attendant covariant-returns

    /**
     * Retourne le nom de l'analyzer utilisé par le champ.
     *
     * @return string
     */
    public function getAnalyzer(): string;
}
