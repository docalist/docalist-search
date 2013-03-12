<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist;
require_once __DIR__ . '/../lib/PHP-DocBlock-Parser/docblock-parser.php';

/**
 * Thin namespaced wrapper around icio/PHP-DocBlock-Parser/DocBlock
 */
class DocBlock extends \DocBlock {
}
