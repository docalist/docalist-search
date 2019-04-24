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

namespace Docalist\Search\Analysis;

use Docalist\Search\Analysis\Component;

/**
 * Un composant d'analyse qui filtre les tokens de recherche générés par un tokenizer.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/analyzer-anatomy.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface TokenFilter extends Component
{
}
