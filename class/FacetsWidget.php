<?php
namespace Docalist\Search;
use StdClass;
use WP_Widget;
use Docalist;
use Docalist\QueryString;
use Docalist\Forms\Fragment;
use Docalist\Forms\Themes;
use Docalist\Utils;

class FacetsWidget extends WP_Widget {
    /**
     * Le formulaire permettant de paramètrer le widget.
     *
     * @var Fragment
     */
    protected $settingsForm;

    /**
     *
     */
    public function __construct() {
        $id = 'docalist-search-facets';
        parent::__construct(

            // Base ID. Inutile de préfixer avec "widget", WordPress le fait
            $id,

            // Titre (nom) du widget affiche en back office
            __('Facettes docalist-search', 'docalist-search'), // Name

            // Args
            array(
                'description' => __('Affiche les facettes de la recherche en cours', 'docalist-search'),
                'classname' => $id, // par défaut, WordPress met 'widget_'.$id
            ),

            // control_options
            array(
                'width' => 800, // Largeur requise pour le formulaire de saisie des paramètres
                'height' => 800,
            )
        );
    }

    /**
     * Affiche le widget.
     *
     * @param array $context Les paramètres d'affichage du widget. Il s'agit
     * des paramètres définis par le thème lors de l'appel à la fonction
     * WordPress.
     *
     * Le tableau passé en paramètre inclut notamment les clés :
     * - before_widget : code html à afficher avant le widget.
     * - after_widget : texte html à affiche après le widget.
     * - before_title : code html à générer avant le titre (exemple : '<h2>')
     * - after_title  : code html à générer après le titre (exemple : '</h2>')
     *
     * @param array $settings Les paramètres du widget que l'administrateur
     * a saisi dans le formulaire de paramétrage (cf. {createSettingsForm()}).
     *
     * @see http://codex.wordpress.org/Function_Reference/register_sidebar
     */
    public function widget($context, $settings) {
        // Récupère la SearchRequest en cours
        /* @var $request SearchRequest */
        $request = apply_filters('docalist_search_get_request', null);
        if (! $request) {
            echo "<p>Aucune facette n'est disponible (no request)</p>";
            return;
        }

        $results = apply_filters('docalist_search_get_results', null);
        if (! $results) {
            echo "<p>Aucune facette n'est disponible (no results)</p>";
            return;
        }

        // Récupère la liste des facettes qui existent (qui sont définies)
        $definedFacets = apply_filters('docalist_search_get_facets', array());

        // Liste des facettes déjà calculées (qui figurent dans results)
        $availableFacets = $results->facets();

        // Liste des facettes qui nous manquent (pas encore calculées)
        $missing = array();

        // Détermine la liste des facettes à afficher
        $facets = array();

        // Phase 1 - Détermine les facettes à afficher et stocke celles qui existent déjà
        foreach($settings['facets'] as $setting) {
            $name = $setting['name'];

            // Vérifie que la facette demandée existe (plugin desactivé, etc.)
            if( ! isset($definedFacets[$name])) {
                printf(__("<p>La facette %s n'existe pas.</p>", 'docalist-search'), $name);
                continue;
            }

            // Vérifie que l'utilisateur en cours peut afficher cette facette
            if (isset($setting['role']) && ! current_user_can($setting['role'])) {
                echo "<p>Facette $name non affichée, réservée aux ", $setting['role'], '</p>';
                continue;
            }

            $facets[$name] = $facet = new StdClass;

            // Détermine le libellé de la facette
            if (isset($setting['label'])) {
                $facet->label = $setting['label'];
            } elseif (isset($definedFacets[$name]['label'])) {
                $facet->label = $definedFacets[$name]['label'];
            } else {
                $facet->label = $name;
            }

            // Teste si cette facette a déjà été calculée
            if ($results->hasFacet($name)) {
                $facet->terms = $results->facet($name)->terms;
            } else {
                $missing[$name] = $setting;
            }
        }

        // Phase 2 - Calcule et stocke les facettes qui nous manquent
        if ($missing) {
//            echo "<p>La requête doit être relancée pour calculer les facettes <code>", implode(', ', array_keys($missing)), '</code></p>';

            // Ajoute les facettes qui manquent à la requête
            foreach($missing as $name => $setting) {
                $size = isset($setting['size']) ? $setting['size'] : 10;
                $request->facet($name, $size);
            }

            // Ré-exécute la recherche
            $results = $request->execute('count');
            
            // Récupère les nouvelles facettes
            foreach($missing as $name => $setting) {
                if (! $results->hasFacet($name)) {
                    echo "<p>La facette $name n'est toujours pas dispo</p>";
                    unset($facets[$name]);
                    continue;
                }
                $facets[$name]->terms = $results->facet($name)->terms;

                // TODO : ajouter les nouvelles facettes calculées aux results d'origine
                // raison : si un autre widget facets a besoin des mêmes, ce n'est pas la
                // peine de recalculer. Autrement dit, ne pas perdre le travail qu'on
                // vient de faire.
            }
        }

        // Phase 3 - Affiche les facettes
        $html = $settings['html'];
        $currentUrl = QueryString::fromCurrent()->clear('page');
        $first = true;
        foreach ($facets as $name => $facet) {
            // Si la facette est vide, on n'affiche rien. Autrement dit : on
            // n'affiche que les facettes pertinentes par rapport à la recherche en cours
            if (empty($facet->terms)) {
                continue;
            }

            if ($first) {
                $first = false;

                // Début du widget
                echo $context['before_widget'];

                // Titre du widget
                $title = isset($settings['title']) ? $settings['title'] : '';
                $title = apply_filters('widget_title', $title, $settings, $this->id_base);
                if ($title) {
                    echo $context['before_title'], $settings['title'], $context['after_title'];
                }

                // Début de la liste des facettes
                echo $html['start-facet-list'];
            }

            // Génère une classe CSS (préfixée ensuite ) à partir du nom de la facette
            $class = strtr($name, '.', '-');

            // Début de la facette
            printf($html['start-facet'], 'facet-' . $class);

            // Titre de la facette
            printf($html['facet-title'], $facet->label);

            // Début de la liste des termes
            printf($html['start-term-list'], 'terms-' . $class);

            $field = $definedFacets[$name]['facet']['field'];
            foreach ($facet->terms as $term) {
                // Nombre de réponses
                $count = sprintf($html['count'], $term->count);

                // Terme actif
                if ($request->hasFilter($field, $term->term)) {
                    $url = $currentUrl->copy()->clear($field, $term->term)->encode();
                    $format = $html['term-active'];
                }

                // Terme normal (inactif)
                else {
                    $url = $currentUrl->copy()->add($field, $term->term)->encode();
                    $format = $html['term'];
                }

                // Génère l'entrée
                $label = apply_filters('docalist_search_get_facet_label', $term->term, $name);
                printf($format, htmlspecialchars($url), htmlspecialchars($label), $count);
            }

            // Fin de la liste des termes
            echo $html['end-term-list'];

            // Fin de la facette
            echo $html['end-facet'];
        }

        // Si on affiché quelque chose, ferme les containers ouverts
        if (! $first) {
            // Fin de la liste des facettes
            echo $html['end-facet-list'];

            // Fin du widget
            echo $context['after_widget'];
        }
    }

    /**
     * Retourne la liste des rôles WordPress actuellement définis.
     *
     * @return array un tableau de la forme Code => libellé localisé.
     *
     * Remarque : le tableau obtenu est directement utilisable dans un select.
     */
    protected function roles() {
        // Ce code est basé sur les fonctions wordpress suivantes :
        // - get_editable_roles (dans user.php) : liste des rôles
        // - wp_dropdown_roles (template.php) : traduction des noms

        /**
         * @var WP_Roles;
         */
        global $wp_roles;

        // Récupère la liste de tous les rôles existants
        $roles = $wp_roles->get_names();

        // Traduit le "nom" de chaque rôel dans la langue en cours
        foreach ($roles as $key => &$name) {
            $name = translate_user_role($name);
        }

        return $roles;
    }

    /**
     * Crée le formulaire permettant de paramètrer le widget.
     *
     * @return Fragment
     */
    protected function createSettingsForm() {
        $form = new Fragment();

        $form->input('title')
        ->attribute('id', $this->get_field_id('title')) // pour que le widget affiche le bon titre en backoffice. cf widgets.dev.js, fonction appendTitle(), L250
        ->label(__('Titre du widget', 'docalist-search'))
        ->addClass('widefat');
        /*
         $html = $form->fieldset('Liste des facettes à afficher')
        ->description(__('Choisissez les facettes à afficher.', 'docalist-search'));
        */

        $facets = apply_filters('docalist_search_get_facets', array());
        foreach($facets as $name => & $facet) {
            $facet = isset($facet['label']) ? $facet['label'] : $name;
        }

        $form->table('facets')
        ->label(__('Facettes', 'docalist-search'))
        ->repeatable(true)
        ->select('name')
        ->label(__('Facette', 'docalist-search'))
        ->description(__('Choisissez dans la liste la facette que vous voulez utiliser', 'docalist-search'))
        ->options($facets)
        ->parent()
        ->input('size')
        ->label(__('Taille initiale', 'docalist-search'))
        ->description(__('Nombre d\'entrées à afficher initialement (laisser vide pour utiliser la valeur par défaut)', 'docalist-search'))
        ->parent()
        ->input('label')
        ->label(__('Titre à afficher', 'docalist-search'))
        ->description(__('Titre de la facette (laisser vide pour utiliser le titre par défaut)', 'docalist-search'))
        ->parent()
        ->select('role')
        ->options($this->roles())
        ->label(__('Droits requis', 'docalist-search'))
        ->description(__('La facette ne sera affichée que pour les utilisateurs ayant le rôle indiqué (vide = tous)', 'docalist-search'))
        ;

        $description  = __('Les zones suivantes vous permettent de personnaliser le code html généré par le widget. ', 'docalist-search');
        $description .= __('Par défaut, le widget génère une liste de facettes (ul). ', 'docalist-search');
        $description .= __('Chaque élément (li) de cette liste contient un titre (h4) et une liste (ul) de termes. ', 'docalist-search');
        $description .= __("Chaque terme (li) est un lien (a) avec le libellé du terme et son nombre d'occurences et permet de sélectionner ou de désélectionner le terme. ", 'docalist-search');

        $html = $form->fieldset('Code HTML généré<br />(options avancées)')
        ->description($description)
        ->name('html');

        $open = __(' Les tags html ouverts ici devront être fermés en %s', 'docalist-search');
        $close = __(' Vous devez fermer tous les tags ouverts en %s', 'docalist-search');

        $html->input('start-facet-list')
        ->label(__('1. Début de la liste des facettes :', 'docalist-search'))
        ->description(sprintf($open, '10.'))
        ->addClass('widefat');

        $html->input('start-facet')
        ->label(__("2. Début d'une facette :", 'docalist-search'))
        ->addClass('widefat')
        ->description(
            __('La chaine <code>%s</code> sera remplacée par un nom de classe de la forme "facet-xxx" construit à partir du nom de la facette (par exemple : facet-type-keyword pour la facette type.keyword).', 'docalist-search')
            . sprintf($open, '9.')
        );

        $html->input('facet-title')
        ->label(__("3. Titre de la facette :", 'docalist-search'))
        ->addClass('widefat')
        ->description(__('La chaine <code>%s</code> sera remplacée par le titre de la facette.', 'docalist-search'));

        $html->input('start-term-list')
        ->label(__("4. Début de la liste des termes :", 'docalist-search'))
        ->addClass('widefat')
        ->description(
            __('La chaine <code>%s</code> sera remplacée par un nom de classe CSS de la forme "terms-xxx" construit à partir du nom de la facette (exemple : terms-type-keyword pour la facette type.keyword).', 'docalist-search')
            . sprintf($open, '8.')
        );

        $html->input('count')
        ->label(__("5. Nombre d'occurences d'un terme :", 'docalist-search'))
        ->addClass('widefat')
        ->description(__("La chaine <code>%s</code> sera remplacée par le nombre d'occurences. Le résultat est ensuite injecté dans les libellés 6 et 7.", 'docalist-search'));

        $html->input('term')
        ->label(__("6. Terme normal :", 'docalist-search'))
        ->addClass('widefat')
        ->description(__("Premier <code>%s</code> = url, second <code>%s</code> = libellé du terme, troisème <code>%s</code> = nombre d'occurences (cf. 5)", 'docalist-search'));

        $html->input('term-active')
        ->label(__("7. Terme sélectionné :", 'docalist-search'))
        ->addClass('widefat')
        ->description(__("Premier <code>%s</code> = url, second <code>%s</code> = libellé du terme, troisème <code>%s</code> = nombre d\'occurences (cf. 5)", 'docalist-search'));

        $html->input('end-term-list')
        ->label(__("8. Fin de la liste des termes :", 'docalist-search'))
        ->description(sprintf($close, '4.'))
        ->addClass('widefat');

        $html->input('end-facet')
        ->label(__("9. Fin d'une facette :", 'docalist-search'))
        ->description(sprintf($close, '2.'))
        ->addClass('widefat');

        $html->input('end-facet-list')
        ->label(__('10. Fin de la liste des facettes :', 'docalist-search'))
        ->description(sprintf($close, '1.'))
        ->addClass('widefat');

        return $form;
    }

    /**
     * Retourne le formulaire permettant de paramètrer le widget.
     *
     * Si le formulaire n'a pas encore été, la méthode appelle
     * {@link createSettingsForm()} et stocke le résultat dans la propriété
     * {@link $settingsForm}.
     *
     * @return Fragment
     */
    protected function settingsForm() {
        if (! $this->settingsForm) {
            $this->settingsForm = $this->createSettingsForm();
        }

        return $this->settingsForm;
    }

    /**
     * Retourne les paramètres par défaut du widget.
     *
     * @return array
     */
    protected function defaultSettings() {
        return array(
            'title' => __('Affiner la recherche', 'docalist-search'),
            'facets' => array(),
            'html' => array(
                'start-facet-list' => '<ul class="facets">',
                'start-facet'      =>     '<li class="%s">',
                'facet-title'      =>         '<h4>%s</h4>',
                'start-term-list'  =>         '<ul class="%s">',
                'count'            =>             '<small> (%s)</small>',
                'term'             =>             '<li><a href="%s">%s</a>%s</li>',
                'term-active'      =>             '<li class="active"><a href="%s">%s</a>%s</li>',
                'end-term-list'    =>         '</ul>',
                'end-facet'        =>     '</li>',
                'end-facet-list'   => '</ul>',
            ),
        );
    }

    /**
     * Affiche le formulaire qui permet de paramètrer le widget.
     *
     * @see WP_Widget::form()
     */
    public function form($instance) {
        $theme = 'wordpress';

        // Récupère le formulaire à afficher
        $form = $this->settingsForm();

        // Envoie les assets requis par ce formulaire
        // Comme le début de la page a déjà été envoyé, les assets vont
        // être ajoutés en fin de page. On n'a pas de FOUC car le formulaire
        // ne sera affiché que lorsque l'utilisateur le demandera.
        Utils::enqueueAssets(Themes::assets($theme)->add($form->assets()));

        // Dans WordPress, les widget ont un ID et sont multi-instances. Le
        // formulaire doit donc être répétable et doit avoir le même nom que
        // le widget. Par ailleurs, l'API Widgets de WordPress attend des noms
        // de champ de la forme "widget-id_base-[number][champ]". Pour générer
        // cela facilement, on positionne le formulaire sur la number-ième occurence.
        $name = 'widget-' . $this->id_base;
        $form->name($name)->repeatable(true)->occurence($this->number);

        // Lie le formulaire aux paramètres du widget
        $form->bind(array($name => $instance ?: $this->defaultSettings()));

        // Affiche le formulaire
        $form->render($theme);
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
    public function update($new, $old) {
        $settings= $this->settingsForm()->bind($new)->data();
        foreach($settings['facets'] as $i => & $facet) {
            if (empty($facet['name'])) {
                unset($settings['facets'][$i]);
                continue;
            }

            if (ctype_digit($facet['size'])) {
                $facet['size'] = (int) $facet['size'];
            } else {
                unset($facet['size']);
            }

            if (empty($facet['label'])) {
                unset($facet['label']);
            }

            if (empty($facet['role'])) {
                unset($facet['role']);
            }
        }

        // renumérote si on en a supprimé
        $settings['facets'] = array_values($settings['facets']);

        //echo '<pre>$new=', htmlspecialchars(var_export($new,true)), '</pre>';
        //echo '<pre>form data=', htmlspecialchars(var_export($data,true)), '</pre>';
        //die();
        return $settings;
    }
}