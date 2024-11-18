<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2020 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
declare(strict_types=1);

namespace Docalist\Search;

use Docalist\Kernel\Kernel;
use Docalist\Search\Extension\DocalistSearchExtension;

Kernel::registerExtension(DocalistSearchExtension::class);
