<?php
/**
 * This file is part of the 'Docalist Search' plugin.
 *
 * Copyright (C) 2016-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Views\Aggregation;

use Docalist\Search\Aggregation;

/**
 * Vue par défaut pour les agrégations.
 *
 * @var Aggregation $this L'agrégation à afficher.
 */
printf(
    '<h3>%s</h3><pre>%s</pre>',
    $this->getTitle() ?: $this->getName(),
    json_encode($this->getResults(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);
