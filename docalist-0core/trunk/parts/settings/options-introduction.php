<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 * 
 * Title:       Attention
 * Setting:     docalist-options
 * Order:       -1
 * 
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */
 
namespace Docalist;

_e("
    <p>
        Les plugins Docalist disposent de plusieurs options que vous pouvez 
        modifier en fonction de vos besoins. Néanmoins, certaines de ces 
        options sont sensibles et peuvent rendre votre système instable ou
        non utilisable.
    </p>
    <p>
        En cas de problème, vous pouvez réinitialiser <em>tous les plugins
        Docalist</em> avec leurs options par défaut en supprimant la clé
        <code>docalist_options</code> de la table <code>wp_options</code> dans
        la base de données Wordpress.
    </p>
", 'docalist-core');
