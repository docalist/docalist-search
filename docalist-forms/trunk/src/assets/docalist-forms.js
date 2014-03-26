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
     * @param DomElement note l'élément à highlighter
     */
    function highlight(node) {
        // Principe : on crée une div jaune avec exactment les mêmes dimensions
        // que l'élément et on la place au dessus en absolute puis on l'estompe
        // (fadeout) gentiment avant de la supprimer.
        // Adapté de : http://stackoverflow.com/a/13106698
        $('<div/>').width(node.outerWidth()).height(node.outerHeight()).css({
            'position' : 'absolute',
            'left' : node.offset().left,
            'top' : node.offset().top,
            'background-color' : '#ffff44',
            'opacity' : '0.5',
            'z-index' : '9999999'
        }).appendTo('body').fadeOut(1000, function() {
            $(this).remove();
        });
    }

    /**
     * Détermine l'élément à cloner en fonction du bouton "+" qui a été cliqué.
     * 
     * Principe : les boutons "+" ont un attribut "data-clone" qui contient un
     * pseudo sélecteur utilisé pour indiquer où se trouve l'élément à cloner.
     * Ce sélecteur est un sélecteur jQuery auquel on peut ajouter (en préfixe)
     * les caractères :
     * - '<' pour dire qu'on veut se déplacer sur le noeud précédent
     * - '^' pour dire qu'on veut aller au noeud parent.
     * Exemple pour "<^^div.aa" 
     * - on a cliqué sur le "+"
     * - aller au noeud qui précède
     * - remonter au grand-parent 
     * - sélectionner la div.aa qui figure dans le noeud obtenu.
     * 
     * Le sélecteur par défaut est "<" ce qui signifie que si l'attribut 
     * data-clone est absent, c'est le noeud qui précède le bouton qui sera
     * cloné.
     * 
     * @param DomElement button le bouton "+" qui a été cliqué
     * @return DomElement le noeud à cloner
     */
    function nodeToClone(button) {
        var node = $(button);

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
        return node;
    }
    
    /**
     * Fait un clonage sélectif du noeud passé en paramètre, en prenant en
     * compte les éléments sur lesquels selectize() a été appliqué.
     * 
     * @param DomElement noeud à cloner
     * @return DomElement noeud cloné
     */
    function createClone(node) {
        // Si on a des éléments input ou select sur lesquels selectize() a été 
        // appliqué, cela pose un problème pour le clonage : le noeud d'origine
        // et le noeud cloné restent plus ou moins liés (on ne peut pas 
        // sélectionner certaines options, le contrôle n'obtient pas le focus, 
        // etc.)
        // La seule solution que j'ai trouvée consiste à supprimer selectize()
        // avant de faire le clonage puis à réinstaller selectize.
        // Il faut également faire une sauvegarde de la valeur actuelle de 
        // l'élément avant de supprimer selectize puis restaurer cette 
        // sauvegarde.

        // Détermine les éléments du noeud qui sont des selectize()
        var selectized = $('input.selectized,select.selectized', node);
        
        // Pour chacun des selectize, fait une sauvegarde et appelle destroy
        selectized.each(function() {
            // Récupère la valeur de l'élément
            var value = this.selectize.getValue();
            
            // S'il n'est pas vide, sauvegarde dans l'attribut data-save
            if (value) {
                $(this).data('save', this.selectize.options[value]);
            }
            
            // Quand on var faire "destroy", selectize va réinitialiser le
            // select avec les options d'origine, telles qu'elles figuraient 
            // dans le code html initial. 
            // Celles-ci sont sauvegardées dans revertSettings.$children.
            // Dans notre cas, cela ne sert à rien, car :
            // - on va restaurer notre propre sauvegarde ensuite
            // - de toute façon, on ne veut pas récupérer ces options dans le 
            //   clone
            // Donc on efface simplement la sauvegarde faite par selectize.
            // Du coup :
            // - le noeud d'origine se retrouve avec une valeur à vide (mais
            //   on va restaurer notre sauvegarde data-save)
            // - le noeud cloné sera vide également (et là c'est ce qu'on veut)
            this.selectize.revertSettings.$children = [];
            
            // Supprime selectize du select
            this.selectize.destroy();
        });
        
        // Clone le noeud
        var clone = node.clone();
        
        // Supprime du clone les éléments qu'il ne faut pas cloner (.do-not-clone)
        // (clones existants, scripts d'init des TableLookup, eléments créés par selectize, etc.)
        $('.do-not-clone', clone).remove();
        if (clone.is(':input')) {
            clone.addClass('do-not-clone');
            // exemple : si une zone mots-clés contient plusieurs mots-clés,
            // on ne veut pas cloner tous les mots-clés, juste le premier
        }
        
        // Réinstalle selectize sur les éléments du noeud à cloner
        selectized.tableLookup();
        
        // Restaure la sauvegarde qu'on a faite dans le noeud d'origine
        selectized.each(function() {
            var save = $(this).data('save');
            // this.selectize.clearOptions();
            if (save) {
                this.selectize.addOption(save);
                this.selectize.addItem(save[this.selectize.settings.valueField]);
            }
            // else : data-save vide, rien à faire car le noeud est déjà vide
        });

        // 5. Fait un clear sur tous les champs présents dans le clone
        // Source : http://goo.gl/RE9f1
        clone.find('input:text, input:password, input:file, select, textarea').val('');
        clone.find('input:radio, input:checkbox').removeAttr('checked').removeAttr('selected');
        
        // Si on voulait faire un reset, j'ai trouvé la méthode suivante :
        // var form = $('<form>').append(clone).get(0).reset();
        // pb : si on édite une notice, on récupère dans le clone les valeurs
        // déjà saisies.

        // Installe selectize sur les éléments du clone
        $('.selectized', clone).tableLookup();
        
        // Ok
        return clone;
    }
    
    /**
     * Renumérote les attributs name, id et for du noeud passé en paramètre (un
     * clone) et des noeuds input et label qu'il contient.
     * 
     * Les attributs id et for sont de la forme "group-i-champ-j-zone-k".
     * Les attributs name sont de la forme "group[i][champ][j][zone][k]".
     * La méthode va incrémenter soit i, soit j, soit k en fonction de la valeur 
     * du level passé en paramètre (1 pour i, 2 pour j, etc.)
     * 
     * Par exemple pour "topic[0][term][1]" avec level=2 on obtiendra
     * "topic[0][term][2]".
     * 
     * @param DomElement le noeud à renuméroter.
     * @param integer level le niveau de renumérotation.
     * @return DomElement le noeud final.
     */
    function renumber(clone, level) {
        $(':input,label', clone).addBack().each(function(){
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
        
        return clone;
    }
    
    /**
     * Gère les boutons de répétition des champs.
     *
     * Les boutons doivent avoir la classe "cloner" et peuvent avoir un attribut
     * "data-clone" qui indique l'élément à cloner.
     */
    $('body').on('click', '.cloner', function() {
        // this = le bouton "+" qui a été cliqué.
        
        // On va travailler en plusieurs étapes :
        //
        // 1. déterminer l'élément à cloner à partir du bouton qui a été cliqué
        // 2. cloner cet élément
        // 3. supprime du clone les éléments qu'il ne faut pas cloner (.do-not-clone)
        // 4. renommer les attributs name, id, for, etc.
        // 5. réinitialiser les champs du clone à vide
        // 6. insérer le clone au bon endroit
        // 7. highlighte le nouveau champ et lui donne le focus

        // 1. Détermine le noeud qu'on doit cloner
        var node = nodeToClone(this);
        
        // 2. Clone le noeud
        var clone = createClone(node);
        
        // 4. Renomme les champs
        // Le niveau de clonage auquel on est figure dans l'attribut data-level
        // du bouton de clonage.
        var level = parseInt($(this).data('level')) || 1; // NaN ou 0 -> 1
        renumber(clone, level);

        // 6. Insère le clone juste après le noeud d'origine, avec un espace entre deux
        node.after(' ', clone);

        // Donne le focus au premier champ trouvé dans le clone
        var first = clone.is(':input') ? clone : $(':input:first', clone);
        first.is('.selectized') ? first[0].selectize.focus() : first.focus();
        
        // 7. Fait flasher le clone pour que l'utilisateur voit l'élément inséré
        highlight(clone);
        
    });
});

// Libellé des relations.
// A localizer plus tard
var docalistLookupRelNames = {
    USE: 'em',
    MT: 'mt',
    BT: 'tg',
    NT: 'ts',
    RT: 'ta',
    UF: 'ep',
    description: 'df',
    SN: 'na'
};

/**
 * Initialise un contrôle de type TableLookup.
 */
jQuery.fn.tableLookup = function() {
    $=jQuery;
    return this.each(function() { 
        
        var createId = function(label) {
            return 'ND' + label;
        }
        
        // Les paramètres figurent en attributs "data-" du select
        var settings = $.extend({
            table: 'countries',      // Nom de la table à utiliser
            valueField: 'code',      // Nom du champ qui contient le code
            labelField: 'label',     // Nom du champ qui contient le libellé
            
            zzz:''
        }, $(this).data());
        
        $(this).selectize({
            plugins: ['subnavigate'], // définit un peu plus bas
            
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
            
            highlight: false,
            hideSelected: true,
            openOnFocus: true,
            
            // Lance une requête ajax pour charger les entrées qui commencent par query
            load: function(query, callback) {
                // Dans le back-office, la variable ajaxurl est définie par WP et
                // pointe vers la page "wordpress/wp-admin/admin-ajax.php"
                // @see http://codex.wordpress.org/AJAX_in_Plugins
                var url = ajaxurl + '?action=docalist-lookup';
                
                // Paramètres de la requête : cf. Docalist-core\Plugin\tableLookup()
                url += '&source=' + settings.table;
                if (query.length) {
                    url += '&search=' + encodeURIComponent(query);
                }

                // TODO : plutôt $.getJSON
                $.ajax({
                    url : url,
                    type: 'GET',
                    error: function() {
                        callback();
                    },
                    success: function(res) {
                        for (var i = 0, n = res.length; i < n; i++) {
                            if (! res[i].code) {
                                res[i].code = createId(res[i].label);
                            }
                        }
                        
                        callback(res);
                    }
                });
            },
            
            render: {
                option: function(item, escape) {
                    
                    /**
                     * Retourne le libellé à utiliser pour une relation donnée.
                     * Par exemple relName('BT') -> 'TG'.
                     * Les libellés utilisés sont définis dans la variable globale
                     * javascript 'docalistLookupRelNames' qui est internationalisée
                     * et injectée dans la page. 
                     */
                    var relName = function(field) {
                        if (docalistLookupRelNames && docalistLookupRelNames[field]) {
                            return docalistLookupRelNames[field];
                        }
                        return field;
                    };
                    
                    /**
                     * Crée une liste de liens.
                     */
                    /*
                     * on nous passe un objet qui est une collection de relations
                     * de la forme code => valeur 
                     * RT: Object
                     *     ABSENTEISME PROFESSIONNEL: "Absentéisme professionnel"
                     *     ABSENTEISME SCOLAIRE: "Absentéisme scolaire"
                     */
                    var listRelations = function(name, relations) {
                        var all=[];
                        
                        $.each(relations, function(code, label) {
                            if (typeof code === 'number') {
                                code = createId(label);
                            }
                            all.push('<b rel="' + escape(code) + '">' + escape(label) + '</b>');
                        });
                        
                        return  '<div class="' + name + '">' +
                                    '<i>' + relName(name) + '</i> ' +
                                    all.join(', ') +
                                '</div>';
                    };
                    
                    var html;
                    
                    // Cas d'un non-descripteur
                    if (item.USE) {
                        html = '<div class="nondes" rel="' + escape(item.USE) + '">';
                    } 
                    
                    // Cas d'un descripteur
                    else {
                        html = '<div class="des">';
                    }
                    
                    // Libellé du terme
                    html += '<span class="term">' + escape(item.label) + '</span>';
/*                    
                    // Description
                    if (item.description) {
                        html += '<span class="title" title="' + escape(item.description) + '">?</span>';
                    }
                    */
                    
                    // Description
                    if (item.description) {
                        html += '<span class="description" title="' + escape(item.description) + '">?</span>';
                    }
                    
                    // Scope Note
                    if (item.SN) {
                        html += '<span class="SN" title="' + escape(item.SN) + '">!</span>';
                    }
                    
                    // Relations
                    $.each(['USE','MT','BT','NT', 'RT','UF'], function(index, field) {
                        if (item[field]) {
                            html += listRelations(field, item[field]);
                        }
                    });
                    
                    // +SN
                    // Fin du terme
                    html += '</div>';
                    
                    return html;
                }
            }
        });
    });
};

/**
 * Plugin pour Selectize permettant de naviguer dans un thesaurus.
 */
Selectize.define('subnavigate', function(options) {
    var self = this;

    /**
     * Surcharge la méthode onOptionSelect
     * 
     * On regarde 
     * - si le tag qui a été cliqué dans l'option en cours a un attribut href
     * - si l'option en cours a un attribut href
     * 
     * Si c'est le cas, on relance une recherche par valeur sur le contenu de
     * l'attribut href et on affiche les résultats obtenus, sinon, on appelle 
     * la méthode onOptionSelect d'origine.
     */
    self.onOptionSelect = (function() {
        var original = self.onOptionSelect;
        
        var show = function(value) {
            if (!self.options.hasOwnProperty(value)) {
                return;
            }
            
            var option      = self.options[value];
            var html = self.render('option', option);
            self.$dropdown_content.html(html);

        };
        
        var loaded = function(value, results) {
            if (results && results.length) {
                self.addOption(results);
            }
            show(value);
        };
        
        return function(e) {
            // soit selectize nous a passé un event mouse ($dropdown.on('mousedown'))
            // soit un objet contenant juste currentTarget (onKeyDown:KEY_RETURN)
            
            // Teste si c'est un lien
            var target = e.target || e.currentTarget;
            var value = $(target).attr('rel');
            
            // On a trouvé un lien
            if (value) {
                // Empêche la fermeture du dropdown
                e.preventDefault && e.preventDefault();
                e.stopPropagation && e.stopPropagation();
                
                if (self.options.hasOwnProperty(value)) {
                    show(value);
                } else {
                    var load = self.settings.load;
                    if (!load) return;
                    load.apply(self, ['[' + value + ']', function(results){
                        loaded(value, results);
                    }]);
                }
                return false;
            } 
            
            // Ce n'est pas un lien, laisse la méthode d'origine s'exécuter
            else {
                return original.apply(this, arguments);
            }
        };
    })();
});