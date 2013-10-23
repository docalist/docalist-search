<?php
/**
 * This file is part of the 'Docalist Core' plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Response
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     $Id$
 */
namespace Docalist\Http;
use Docalist;

/**
 * Une vue de type "page d'administration".
 *
 * Cette classe sert juste de marqueur : les réponses de ce type sont
 * automatiquement "décorées" avec l'entête, les menus et le pied de page
 * standard du back-office de wordpress.
 */
class AdminViewResponse extends ViewResponse {
}