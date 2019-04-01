<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Search\Widget;

use stdClass as StdClass;
use DateTime;
use WP_Roles;
use WP_Widget;
use Docalist\Forms\Container;
use Exception;
use Docalist\Search\SearchEngine;
use Docalist\Search\SearchRequest;

class DisplayAggregations extends WP_Widget
{
    /**
     * Le formulaire permettant de paramètrer le widget.
     *
     * @var Container
     */
    protected $settingsForm;

    /**
     * Initialise le widget.
     */
    public function __construct()
    {
        // Identifiant du widget
        $id = 'docalist_search_display_aggregations';

        // Titre affiché dans le back office
        $title = __('Agrégations docalist-search', 'docalist-search');

        // Description affichée dans le back office
        $description = __('Affiche les agrégations présentes dans la recherche en cours.', 'docalist-search');

        // Paramètres
        $args  = [
            'classname' => 'docalist-search-display-aggregations',
            'description' => $description,
        ];

        // Options d'affichage dans le back-office
        $options = [
            'width' => 800,     // Largeur requise pour le formulaire de saisie des paramètres
            'height' => 800,
        ];

        // Initialise le widget
        parent::__construct($id, $title, $args, $options);
    }

    /**
     * Retourne la recherche en cours.
     *
     * @return SearchRequest|null La recherche en cours ou null si la page en cours n'est pas une recherche docalist.
     */
    private function getSearchRequest(): ?SearchRequest
    {
        $searchEngine = docalist('docalist-search-engine'); /* @var SearchEngine $searchEngine */

        return $searchEngine->getSearchRequest();
    }

    /**
     * Affiche le widget.
     *
     * @param array $context Les paramètres d'affichage du widget. Il s'agit des paramètres définis par le thème
     * lors de l'appel à la fonction WordPress. Le tableau passé en paramètre inclut notamment les clés :
     * - before_widget : code html à afficher avant le widget.
     * - after_widget : texte html à affiche après le widget.
     * - before_title : code html à générer avant le titre (exemple : '<h2>')
     * - after_title  : code html à générer après le titre (exemple : '</h2>')
     *
     * @param array $settings Les paramètres du widget que l'administrateur a saisi dans le formulaire.
     */
    public function widget($context, $settings)
    {
        // Récupère la SearchRequest en cours
        $searchRequest = $this->getSearchRequest();

        // On ne fait rien si on n'a aucune searchRequest ou aucune agrégation
        if (is_null($searchRequest) || !$searchRequest->hasAggregations()) {
            return;
        }

        // Début du widget
        echo $context['before_widget'];

        // Titre du widget
        $title = $settings['widget-title'] ?? '';
        $title = apply_filters('widget_title', $title, $settings, $this->id_base);
        if ($title) {
            echo $context['before_title'], $title, $context['after_title'];
        }

//         echo '<pre>$context=', htmlspecialchars(var_export($context,true)), '</pre>';
//         echo '<pre>$settings=', htmlspecialchars(var_export($settings,true)), '</pre>';

        echo $settings['before-facets'] ?? '';

        // Affiche toutes les agrégations disponibles
        foreach($searchRequest->getAggregations() as $aggregation) {
            $aggregation->display();
        }

        echo $settings['after-facets'] ?? '';

        // Fin du widget
        echo $context['after_widget'];
    }

    /**
     * Affiche le formulaire qui permet de paramètrer le widget.
     *
     * @see WP_Widget::form()
     */
    public function form($instance)
    {
        // Récupère le formulaire à afficher
        $form = $this->getSettingsForm();

        // Lie le formulaire aux paramètres du widget
        $form->bind($instance ?: $this->getDefaultSettings());

        // Dans WordPress, les widget ont un ID et sont multi-instances. Le
        // formulaire doit donc avoir le même nom que le widget.
        // Par ailleurs, l'API Widgets de WordPress attend des noms
        // de champ de la forme "widget-id_base-[number][champ]". Pour générer
        // cela facilement, on donne directement le bon nom au formulaire.
        // Pour que les facettes soient orrectement clonées, le champ facets
        // définit explicitement repeatLevel=2 (cf. settingsForm)
        $name = 'widget-' . $this->id_base . '[' . $this->number . ']';
        $form->setName($name);

        // Affiche le formulaire
        $form->display('wordpress');

        // Remarque : comme le début de la page a déjà été envoyé, les assets sont
        // ajoutés en fin de pagemais on n'a pas de FOUC car le formulaire ne sera
        // affiché que lorsque l'utilisateur le demandera.
    }

    /**
     * Retourne les paramètres par défaut du widget.
     *
     * @return array
     */
    protected function getDefaultSettings(): array
    {
        return [
            'widget-title' => __('Affiner la recherche', 'docalist-search'),
            'before-facets' => '<ul class="facets">',
            'after-facets' => '</ul>',
        ];
    }

    /**
     * Crée le formulaire permettant de paramètrer le widget.
     *
     * @return Container
     */
    protected function getSettingsForm(): Container
    {
        $form = new Container();

        $form->input('widget-title')
            ->setAttribute('id', $this->get_field_id('title')) // pour que le widget affiche le bon titre en backoffice. cf widgets.dev.js, fonction appendTitle(), L250
            ->setLabel(__('Titre du widget', 'docalist-search'))
            ->addClass('widefat');

        $form->input('before-facets')
            ->setLabel(__('Avant la liste', 'docalist-search'))
            ->addClass('widefat');

        $form->input('after-facets')
            ->setLabel(__('Après la liste', 'docalist-search'))
            ->addClass('widefat');

        return $form;
    }

    /**
     * Enregistre les paramètres du widget.
     *
     * La méthode vérifie que les nouveaux paramètres sont valides et retourne
     * la version corrigée.
     *
     * @param array $new les nouveaux paramètres du widget.
     * @param array $old les anciens paramètres du widget
     *
     * @return array La version corrigée des paramètres.
     */
    public function update($new, $old)
    {
        $settings = $this->getSettingsForm()->bind($new)->getData();

//         echo '<pre>$new=', htmlspecialchars(var_export($new,true)), '</pre>';
//         echo '<pre>form data=', htmlspecialchars(var_export($settings,true)), '</pre>';
//         die();

        return $settings;
    }
}
