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

namespace Docalist\Search\Views;

use Docalist\Search\SettingsPage;
use Docalist\Search\SearchAttributes;
use Docalist\Search\Mapping;

/**
 * Liste des attributs de recherche.
 *
 * @var SettingsPage        $this
 * @var SearchAttributes    $searchAttributes
 * @var string              $feature
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
?>
<div class="wrap">
    <h1><?= __('Attributs de recherche', 'docalist-search') ?></h1>

    <p class="description"><?=
        __(
            "Le tableau ci-dessous liste par ordre alphabétique les attributs de recherche qui ont
            été créés par docalist-search lors de l'indexation de vos contenus. Chaque ligne du
            tableau indique le nom complet de l'attribut, sa description, les caractéristiques qu'il
            supporte et des précisions éventuelles.",
            'docalist-search'
        ) ?>
    </p>

    <p class="description"><?=
        __(
            "Les codes qui figurent dans la colonne \"caractéristiques\" indiquent comment chaque
            attribut peut être utilisé. Une <a href=\"#doc\">documentation sur les caractéristiques
            des attributs</a> est disponible après le tableau.",
            'docalist-search'
        ) ?>
    </p>

    <p class="description"><?=
        __(
            "Le sélecteur ci-dessous vous permet de filtrer la liste et de n'afficher que les attributs
            de recherche qui ont une caractéristique donnée (par exemple uniquement les filtres ou
            seulement les clés de tri). La zone à droite vous indique le nombre total d'attributs
            correspondants.",
            'docalist-search'
        ) ?>
    </p>

    <?php
        // Récupère la liste de toutes les features qui existent (feature => code)
        $features = array_flip(Mapping::describeFeatures()); // on inverse : code => feature

        // Par défaut, on affiche tous les champs
        $fields = $searchAttributes->getAllFeatures();

        // Si on nous a indiqué un code valide, on filtre la liste
        if (!empty($feature)) {
            $value = $features[$feature] ?? 0; // convertit le code en valeur
            $value && $fields = $searchAttributes->filterByFeatures($value);
        }
    ?>

    <form id="posts-filter" method="get" action="#">
        <input type="hidden" name="page" value="<?=$_GET['page']?>">
        <input type="hidden" name="m" value="<?=$_GET['m']?>">
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="feature" onchange="this.form.submit()">
                    <option value="">
                        <?= __('Filtrer la liste par type', 'docalist-search') ?>
                    </option><?php
                    foreach (array_keys($features) as $filter) { ?>
                        <option value="<?= $filter ?>" <?php selected($feature, $filter) ?>>
                            <?= $filter ?>
                        </option><?php
                    } ?>
                </select>
            </div>

            <div class="tablenav-pages one-page">
                <span class="displaying-num">
                    <?= $feature
                        ? sprintf(__('%d attributs de type "%s".', 'docalist-search'), count($fields), $feature)
                        : sprintf(__('%d attributs de recherche.', 'docalist-search'), count($fields))
                    ?>
                </span>
            </div>
        </div>

        <table class="widefat fixed">
            <thead><?php
                ob_start() ?>
                <tr>
                    <th><?= __("Nom de l'attribut", 'docalist-search') ?></th>
                    <th><?= __('Description', 'docalist-search') ?></th>
                    <th><?= __('Caractéristiques', 'docalist-search') ?></th>
                    <th><?= __('Précisions', 'docalist-search') ?></th>
                </tr><?php
                $columns = ob_get_flush(); ?>
            </thead>
            <tfoot>
                <?= $columns ?>
            </tfoot>
            <tbody><?php
            $nb = 0;
            foreach (array_keys($fields) as $field) {
                ++$nb;
                $features = array_map(
                    function (string $feature): string {
                        return sprintf('<a href="#%s">%s</a>', $feature, $feature);
                    },
                    Mapping::describeFeatures($searchAttributes->getFeatures($field))
                );
                $features = Mapping::describeFeatures($searchAttributes->getFeatures($field));
                ?>
                <tr class="<?= $nb % 2 ? 'alternate' : '' ?>">
                    <td class="column-title"><strong><?= $field ?></strong></td>
                    <td><?= $searchAttributes->getLabel($field) ?></td>
                    <td><?= implode(', ', $features) ?></td>
                    <td><?= $searchAttributes->getDescription($field) ?></td>
                </tr>
            <?php
            }?>
            </tbody>
        </table>
    </form>

    <h2 id="doc"><?= __('Documentation sur les caractéristiques des attributs', 'docalist-search') ?></h2>
    <p><?=
        __(
            "Chaque attribut a une ou plusieurs caractéristiques (filtre, clé de tri, etc.) qui
            indiquent comment il peut être utilisé. Les différentes caractéristiques possibles
            sont documentées ci-dessous.",
            'docalist-search'
        )?>
    </p>
    <ul class="ul-square features">
        <li id="fulltext">
            <h3><?= __('fulltext : champs de recherches', 'docalist-search') ?></h3>
            <p><?=
                __(
                    "Les champs fulltext sont des attributs de recherche qui permettent de sélectionner
                    parmi les documents indexés ceux qui contiennent un mot ou une expression dans le
                    champ correspondant.",
                    'docalist-search'
                )?>
            </p>
            <p><?=
                __(
                    "Les champs fulltext sont insensibles à la casse des caractères et aux accents car les
                    mots sont convertis en minuscules non accentuées avant d'être stockés dans l'index.",
                    'docalist-search'
                )?>
                <?=
                __(
                    "Certains champs appliquent également un algorithme de stemming (modifiable dans les
                    paramètres de docalist-search) qui permet de stocker dans l'index un terme de recherche
                    unique pour représenter les différentes formes d'un même mot (féminin, pluriel...)",
                    'docalist-search'
                )?>
            </p>
            <p><?=
                __(
                    "Vous pouvez utiliser un champ fulltext dans une équation de recherche en indiquant
                    son nom et le mot à rechercher. Par exemple, la requête <code>content:recherche</code>
                    sélectionne les documents qui contiennent le terme « recherche » (ou l'un de ses dérivés
                    comme « recherches ») dans le champ <code>content</code> des documents indexés.",
                    'docalist-search'
                )?>
            </p>
            <p><?=
                __(
                    "Vous pouvez également rechercher une expression en indiquant plusieurs mots entre
                    guillemets. Par exemple, la requête <code>content:\"recherche fulltext\"</code>
                    sélectionne les documents qui contiennent les termes « recherche » et « fulltext »
                    (ou des dérivés) l'un à coté de l'autre.",
                    'docalist-search'
                )?>
            </p>
            <p><?=
                __(
                    "Enfin, les champs fulltext permettent de calculer le score des réponses obtenues
                    en tenant compte du poids respectif de chaque champ fulltext et du nombre d'occurences
                    des mots recherchés dans les documents obtenus.",
                    'docalist-search'
                )?>
            </p>
        </li>

        <li id="filter">
            <h3><?= __('filter : filtres', 'docalist-search') ?></h3>
            <p><?=
                __(
                    "Les filtres sont des attributs de recherche qui permettent d'exclure des résultats
                    de recherche les documents qui ne respectent pas un critère donné.",
                    'docalist-search'
                )?>
            </p>
            <p><?=
                __(
                    "Lors de l'indexation d'un filtre, la totalité du contenu du champ est stockée telle
                    quelle dans l'index de recherche sous la forme d'un terme de recherche unique. Aucun
                    traitement n'est effectué, il n'y a pas d'extraction des mots ou de conversion en
                    minuscules comme c'est le cas pour un champ fulltext.",
                    'docalist-search'
                )?>
            </p>
            <p><?=
                __(
                    "En recherche, il faut donc indiquer la valeur exacte du filtre en tenant compte des
                    majuscules, des accents, des signes de ponctuation, etc. En général, ce n'est pas
                    l'utilisateur qui saisit la valeur des filtres, ils sont générés automatiquement
                    via des liens (dans des facettes par exemple) ou via des menus qui lui sont proposés
                    dans l'interface. Cependant, rien n'empêche de saisir un filtre directement dans
                    une équation de recherche quand la valeur du filtre est suffisamment simple (par
                    exemple <code>in:posts</code> ou <code>ref:12</code>).",
                    'docalist-search'
                )?>
            </p>
            <p><?=
                __(
                    "Les filtres permettent de faire des requêtes très efficaces car il n'y a pas de
                    phase de recherche à proprement parler : le système se contente d'exclure tous les
                    documents dont le champ est différent de la valeur indiquée et il est optimisé
                    pour ce genre de requête (il crée des \"vues\" qui sont mises en cache).",
                    'docalist-search'
                )?>
            </p>
            <p><?=
                __(
                    "Lors de l'exécution d'un filtre, aucun score n'est calculé car le traitement est
                    purement booléen : soit le document passe le filtre et il est conservé, soit il ne
                    le passe pas et il est exclu.",
                    'docalist-search'
                )?>
            </p>
            <p><?=
                __(
                    "Les filtres constituent également la brique de base utilisée pour les facettes :
                    comme chaque filtre génère un terme de recherche unique, le système peut facilement
                    générer la liste de toutes les valeurs qui existent. De fait, presque tous les attributs
                    qui ont la caractéristique \"filter\" ont également la caractéristiques \"aggregate\".
                    La seule exception concerne les attributs qui ont un nombre infini de valeurs possibles
                    (par exemple un filtre sur le numéro de référence) et pour lesquels une facette n'aurait
                    pas de sens.",
                    'docalist-search'
                )?>
            </p>
        </li>

        <li id="exclusive">
            <h3><?= __('exclusive : filtres exclusifs', 'docalist-search') ?></h3>
            <p><?=
                __(
                    "Par défaut, la majorité des filtres sont de type \"inclusif\" : si plusieurs valeurs
                    sont indiquées pour un même filtre, celles-ci sont combinées en \"ET\". Par exemple
                    si une requête contient deux filtres mots-clés, seuls les documents qui ont les deux
                    mots-clés seront conservés (et non pas les documents qui contiennent l'un ou l'autre
                    des deux mots-clés).",
                    'docalist-search'
                )?>
            </p>
            <p><?=
                __(
                    "Cependant, pour un champ qui ne peut contenir qu'une seule valeur et pour les champs
                    multivalués dont les valeurs sont mutuellement exclusives, ça n'a pas de sens. Par
                    exemple, un document ne peut pas être à la fois en statut \"pending\" et en statut
                    \"publish\" : si on combine les deux valeurs en \"ET\", on n'obtiendra aucune réponse.",
                    'docalist-search'
                )?>
            </p>
            <p><?=
                __(
                    "Les filtres qui sont dans ce cas sont qualifiés de \"filtre exclusifs\" et l'attribut
                    correspondant dispose de la caractéristique \"exclusive\" qui indique au système
                    qu'il faut croiser les différentes valeurs du filtre en \"OU\".",
                    'docalist-search'
                )?>
            </p>
        </li>

        <li id="aggregate">
            <h3><?= __('aggregate : facettes et métriques', 'docalist-search') ?></h3>
            <p><?=
                __(
                    "La caractéristique \"aggregate\" indique que les valeurs de l'attribut de recherche
                    (souvent un filtre) peuvent être regroupées et agrégées. Pour les attributs qui
                    contiennent du texte (mots-clés, statut de publication...), cela permet de générer
                    une facette qui liste les valeurs possibles et permet d'affiner la requête.
                    Pour les attributs qui contiennent des nombres (âge, prix...), cela permet de calculer
                    des métriques (somme, moyenne, etc.)",
                    'docalist-search'
                )?>
            </p>
        </li>

        <li id="sort">
            <h3><?= __('sort', 'docalist-search') ?></h3>
            <p><?=
                __(
                    "doc sur les clés de tri.",
                    'docalist-search'
                )?>
            </p>
        </li>

        <li id="lookup">
            <h3><?= __('lookup', 'docalist-search') ?></h3>
            <p><?=
                __(
                    "doc sur les champs autocomplete.",
                    'docalist-search'
                )?>
            </p>
        </li>

        <li id="spellcheck">
            <h3><?= __('spellcheck', 'docalist-search') ?></h3>
            <p><?=
                __(
                    "doc sur la correction orthographique.",
                    'docalist-search'
                )?>
            </p>
        </li>

        <li id="highlight">
            <h3><?= __('highlight', 'docalist-search') ?></h3>
            <p><?=
                __(
                    "doc sur la mise en surbrillance.",
                    'docalist-search'
                )?>
            </p>
        </li>
    </ul>
</div>
