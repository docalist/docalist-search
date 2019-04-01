<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Search\Views\Aggregation;

use Docalist\Search\Aggregation;

/**
 * Vue par défaut pour les agrégations.
 *
 * @var Aggregation     $this       L'agrégation à afficher.
 * @var string|false    $container  Optionnel, tag à générer pour le container (div par défaut), ou false.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */

// Valeur par défaut des paramètres de la vue
!isset($container) && $container = 'div';

// Début du container
if ($container) {
    // Détermine les classes css à appliquer au container
    $class = sprintf('%s %s',                                   // "facet"
        $this->getType(),                                       // type de la facette (e.g. "facet-terms")
        $this->getName()                                        // nom de la facette (e.g. "facet-category")
    );

    // Génère le tag ouvrant du container
    printf('<%s class="%s">', $container, $class);

}

// Dump de l'agrégation
printf(
    '<h3>%s</h3><pre>%s</pre>',
    $this->getTitle() ?: $this->getName(),
    json_encode($this->getResult(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

// Fin du container
$container && printf('</%s>', $container);
