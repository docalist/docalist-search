/**
 * This file is part of the "Docalist Forms" package.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Forms
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

jQuery(document).ready(function($) {

    /**
     * Génère un effet highlight sur l'élément passé en paramètre : l'élément
     * apparaît en jaune clair quelques instants puis s'estompe.
     *
     * On gère l'effet nous-même pour éviter d'avoir une dépendance envers
     * jquery-ui.
     *
     * @param DomElement el l'élément à highlighter
     */
    function highlight(el) {
        // Principe : on crée une div jaune avec exactment les mêmes dimensions
        // que l'élément et on la place au dessus en absolute puis on l'estompe
        // (fadeout) gentiment avant de la supprimer.
        // Adapté de : http://stackoverflow.com/a/13106698
        $("<div/>").width(el.outerWidth()).height(el.outerHeight()).css({
            'position' : 'absolute',
            'left' : el.offset().left,
            'top' : el.offset().top,
            'background-color' : '#ffff99',
            'opacity' : '0.5',
            'z-index' : '9999999'
        }).appendTo('body').fadeOut(500, function() {
            $(this).remove();
        });
    }

    /**
     * Gère les boutons de répétition des champs.
     *
     * Les boutons doivent avoir la classe "cloner" et peuvent avoir un attribut
     * "data-clone" qui indique l'élément à cloner (cf doc).
     */
    $('body').on('click', '.cloner', function() {
        // On va travailler en plusieurs étapes :
        //
        // 1. déterminer l'élément à cloner à partir du bouton qui a été cliqué
        // 2. cloner cet élément
        // 3. supprime du clone les éléments qu'il ne faut pas cloner (.do-not-clone)
        // 4. renommer les attributs name, id, for, etc.
        // 5. réinitialiser les champs du clone à vide
        // 6. insérer le clone au bon endroit
        // 7. highlighte le nouveau champ et lui donne le focus

        // 1. Détermine le bouton qui a été cliqué
        var node = $(this);

        // Récupère le sélecteur à appliquer (attribut data-clone, '<' par défaut)
        var selector = node.data('clone') || '<';

        // Exécute les commandes contenues dans le préfixe (< = prev, ^ = parent)
        for (var i = 0; i < selector.length; i++) {
            switch (selector.substr(i, 1)) {
                // parent
                case '^':
                    node = node.parent();
                    continue;

                // previous
                case '<':
                    node = node.prev();
                    continue;
            }

            // Autre caractère = début du sélecteur jquery
            break;
        }

        // On extrait ce qui reste du sélecteur et on l'applique (si non vide)
        selector = selector.substr(i);
        if (selector.length) {
            node = $(selector, node);
        }

        // node pointe maintenant sur le noeud à cloner
        
        // 2. Clone le noeud
        var clone = node.clone();
        
        // 3. Supprime du clone les éléments qu'il ne faut pas cloner (.do-not-clone)
        // (clones existants, scripts d'init des TableLookup, eléments créés par selectize, etc.)
        $('.do-not-clone', clone).remove();

        // 4. Renomme les champs

        // Récupère le repeatLevel() en cours
        var level = parseInt($(this).data('level')) || 1; // NaN ou 0 -> 1

        // Incrémente le level-ième nombre entre crochets trouvé dans les
        // attributs name, for et id de tous les champs et labels
        $(':input,label', clone).andSelf().each(function(){
            var input = $(this);

            // Renomme l'attribut name
            $.each(['name'], function(i, name){
                var value = input.attr(name); // valeur de l'attribut name, id ou for
                if (! value) return;
                var curLevel = 0;
                value = value.replace(/\[(\d+)\]/g, function(match, i) {
                    if (++curLevel !== level) return match;
                    return '[' + (parseInt(i)+1) + ']';
                });
                input.attr(name, value);
            });
            
            // Renomme les attributs id et for
            $.each(['id', 'for'], function(i, name){
                var value = input.attr(name); // valeur de l'attribut name, id ou for
                if (! value) return;
                var curLevel = 0;
                value = value.replace(/-(\d+)(-|$)/g, function(match, i, end) {
                    if (++curLevel !== level) return match;
                    return '-' + (parseInt(i)+1) + (end ? '-' : '');
                });
                input.attr(name, value);
            });
        });

        // 5. Fait un clear sur tous les champs présents dans le clone
        // Source : http://goo.gl/RE9f1
        clone.find('input:text, input:password, input:file, select, textarea').val('');
        clone.find('input:radio, input:checkbox').removeAttr('checked').removeAttr('selected');

        // Si on voulait faire un reset, j'ai trouvé la méthode suivante :
        // var form = $('<form>').append(clone).get(0).reset();
        // pb : si on édite une notice, on récupère dans le clone les valeurs
        // déjà saisies.

        // 6. Insère le clone juste après le noeud d'origine, avec un espace entre deux
        node.after(' ', clone);

        // 7. Fait flasher le clone pour que l'utilisateur voit l'élément inséré
        highlight(clone);

        // Installe selectize sur les éléments du clone
        // TODO
        $('.selectized', clone).tableLookup();

        // Donne le focus au premier champ trouvé dans le clone
        var first = clone.is(':input') ? clone : $(':input:first', clone);
        first.is('.selectized') ? first[0].selectize.focus() : first.focus();
        //clone.is(':input') ? clone.focus() : $(':input:first', clone).focus();
    });
});

/**
 * Initialise un contrôle de type TableLookup.
 */
jQuery.fn.tableLookup = function() {
    $=jQuery;
    
    // Les paramètres figurent en attributs "data-" du select
    var settings = $.extend({
        table: 'countries',      // Nom de la table à utiliser
        valueField: 'code',      // Nom du champ qui contient le code
        labelField: 'label',     // Nom du champ qui contient le libellé

        zzz:''
    }, $(this).data());
    
    $(this).selectize({
        // Le JSON retourné par "docalist-table-lookup" est de la forme :
        // [ { "code": "xx", "label": "aa" }, { "code": "yy", "label": "bb" } ]
        
        valueField: settings.valueField, // Nom du champ qui contient la valeur
        labelField: settings.labelField, // Nom du champ qui contient le libellé

        // Table d'autorité = liste fermé, on ne peut pas créer de nouvelles valeurs
        create: false,
        
        // Charge les options dispo en tâche de fond dès l'initialisation
        preload: true,
        
        // Crée le popup dans le body plutôt que dans le contrôle 
        dropdownParent: 'body',
        
        // Par défaut, selectize trie par score. On veut un tri alpha.
        sortField: settings.labelField, 

        // Ajoute la classe "do-not-clone" aux containers créés par selectize
        wrapperClass: 'selectize-control do-not-clone',
        
        // La recherche porte à la fois sur le libellé et sur le code
        // Cela permet par exemple de recherche "ENG" et de trouver "Anglais"
        searchField: [settings.valueField, settings.labelField],
    
        // Lance une requête ajax pour charger les entrées qui commencent par query
        load: function(query, callback) {
            // Dans le back-office, la variable ajaxurl est définie par WP et
            // pointe vers la page "wordpress/wp-admin/admin-ajax.php"
            // @see http://codex.wordpress.org/AJAX_in_Plugins
            var url = ajaxurl + '?action=docalist-table-lookup';
            
            // Paramètres de la requête : cf. Docalist-core\Plugin\tableLookup()
            url += '&table=' + settings.table;
            url += '&order=' + '_' + settings.labelField; // _ = champ caché = insensible à la casse
            if (query.length) {
                url += '&where=' + encodeURIComponent('code like "' + query + '%"');
            }

            $.ajax({
                url : url,
                type: 'GET',
                error: function() {
                    callback();
                },
                success: function(res) {
                    callback(res);
                }
            });
        }
    });
};