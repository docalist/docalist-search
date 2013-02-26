<?php
use Docalist\Forms\Form;

$form = new Form();
$form->label('Saisie/modification d\'une notice documentaire');
$form->addClass('form-horizontal');

// -----------------------------------------------------------------------------

$box = $form->fieldset('Nature du document');

$box->select('type')
    ->label('Type de document')
    ->options(array('article','livre','rapport'));

$box->checklist('genre')
    ->label('Genre de document')
    ->options(array('communication','decret','didacticiel','etat de l\'art'));

$box->checklist('media')
    ->label('Support de document')
    ->options(array('cd-rom','internet','papier','dvd','vhs'));

// -----------------------------------------------------------------------------

$box = $form->fieldset('Titre du document');

$box->input('title')
    ->addClass('span12')
    ->label('Titre principal');

$box->table('othertitle')
    ->label('Autres titres')
    ->repeatable(true)
        ->select('type')
        ->label('Type de titre')
        ->options(array('serie','dossier','special'))
        ->addClass('span4')
    ->parent()
        ->input('title')
        ->label('Autre titre')
        ->description('En minuscules, svp')
        ->addClass('span8')
        ;

$box->table('translation')
    ->label('Traduction du titre')
    ->repeatable(true)
        ->select('language')
        ->label('Langue')
        ->options(array('fre','eng','ita','spa','deu'))
        ->addClass('span4')
    ->parent()
        ->input('title')
        ->label('Titre traduit')
        ->addClass('span8')
        ;

// -----------------------------------------------------------------------------

$box = $form->fieldset('Auteurs');

$box->table('author')
    ->label('Personnes')
    ->repeatable(true)
        ->input('name')
        ->label('Nom')
        ->addClass('span5')
    ->parent()
        ->input('firstname')
        ->label('Prénom')
        ->addClass('span4')
    ->parent()
        ->select('role')
        ->label('Rôle')
        ->options(array('pref','trad','ill','dir','coord','postf','intro'))
        ->addClass('span3')
        ;

$box->table('organisation')
    ->label('Organismes')
    ->repeatable(true)
        ->input('name')
        ->label('Nom')
        ->addClass('span5')
    ->parent()
        ->input('city')
        ->label('Ville')
        ->addClass('span3')
    ->parent()
        ->select('country')
        ->label('Pays')
        ->options(array('france', 'usa', 'espagne', 'italie'))
        ->addClass('span2')
    ->parent()
        ->select('role')
        ->label('Rôle')
        ->options(array('com','financ'))
        ->addClass('span2')
        ;

// -----------------------------------------------------------------------------

$box = $form->fieldset('Journal');

$box->input('journal')
    ->label('Titre de périodique')
    ->addClass('span12')
    ->description('Nom du journal dans lequel a été publié le document.');

$box->input('issn')
    ->label('ISSN')
    ->addClass('span6')
    ->description('International Standard Serial Number : identifiant unique du journal.');

$box->input('volume')
    ->label('Numéro de volume')
    ->addClass('span4')
    ->description('Numéro de volume pour les périodiques, tome pour les monographies.');

$box->input('issue')
    ->label('Numéro de fascicule')
    ->addClass('span4')
    ->description('Numéro de la revue dans lequel le document a été publié.');

// -----------------------------------------------------------------------------

$box = $form->fieldset('Informations bibliographiques');

$box->input('date')
    ->label('Date de publication')
    ->addClass('span6')
    ->description('Date d\'édition ou de diffusion du document.');

$box->select('language')
    ->label('Langue du document')
    ->repeatable(true)
    ->description('Langue(s) dans laquelle est écrit le document.')
    ->addClass('span6')
    ->options(array('fre','eng','ita','spa','deu'));

$box->input('pagination')
    ->label('Pagination')
    ->addClass('span6')
    ->description('Pages de début et de fin (ex. 15-20) ou nombre de pages (ex. 10p.) du document.');

$box->input('format')
    ->label('Format du document')
    ->addClass('span12')
    ->description('Caractéristiques matérielles du document : étiquettes de collation (tabl, ann, fig...), références bibliographiques, etc.');

// -----------------------------------------------------------------------------

$box = $form->fieldset('Informations éditeur');

$box->table('editor')
    ->label('Editeur')
    ->description('Editeur et lieu d\'édition.')
    ->repeatable(true)
        ->input('name')
        ->label('Nom')
        ->addClass('span5')
    ->parent()
        ->input('city')
        ->label('Ville')
        ->addClass('span5')
    ->parent()
        ->select('country')
        ->label('Pays')
        ->addClass('span2')
        ->options(array('france', 'usa', 'espagne', 'italie'));

$box->table('collection')
    ->label('Collection')
    ->description('Collection et numéro au sein de cette collection du document catalogué.')
    ->repeatable(true)
        ->input('name')
        ->label('Nom')
        ->addClass('span9')
    ->parent()
        ->input('number')
        ->addClass('span3')
        ->label('Numéro dans la collection');

$box->table('edition')
    ->label('Mentions d\'édition')
    ->description('Mentions d\'éditions (hors série, 2nde édition, etc.) et autres numéros du document (n° de rapport, de loi, etc.)')
    ->repeatable(true)
        ->input('type')
        ->label('Mention')
        ->addClass('span9')
    ->parent()
        ->input('value')
        ->label('Numéro')
        ->addClass('span3')
        ;

$box->input('isbn')
    ->label('ISBN')
    ->addClass('span6')
    ->description('International Standard Book Number : identifiant unique pour les livres publiés.');

// -----------------------------------------------------------------------------

$box = $form->fieldset('Congrès et diplômes');

$box->table('event')
    ->label('Informations sur l\'événement')
    ->description('Congrès, colloques, manifestations, soutenances de thèse, etc.')
        ->input('title')
        ->label('Titre')
        ->addClass('span5')
    ->parent()
        ->input('date')
        ->label('Date')
        ->addClass('span2')
    ->parent()
        ->input('place')
        ->label('Lieu')
        ->addClass('span3')
    ->parent()
        ->input('number')
        ->label('N°')
        ->addClass('span2')
        ;

$box->table('degree')
    ->label('Diplôme')
    ->description('Description des titres universitaires et professionnels.')
        ->select('level')
        ->label('Niveau')
        ->addClass('span3')
        ->options(array('licence','master','doctorat'))
    ->parent()
        ->input('title')
        ->label('Intitulé')
        ->addClass('span9');

// -----------------------------------------------------------------------------

$box = $form->fieldset('Indexation et résumé');

$box->table('topic')
    ->label('Mots-clés')
    ->description('Indexation du document : mots-clés matières, mots outils, noms propres, description géographique, période historique, candidats descripteurs, etc.', false)
    ->repeatable(true)
        ->select('type')
        ->label('Thesaurus')
        ->addClass('span2')
        ->options(array('theso un', 'theso deux', 'theso trois'))
    ->parent()
        ->Div()
        ->attribute('style', 'border: 1px solid red')
        ->label('Termes')
        ->addClass('span12')
            ->input('terms')
            ->addClass('span2')
            ->repeatable(true);

$box->table('abstract')
    ->label('Résumé')
    ->description('Résumé du document et langue du résumé.')
    ->repeatable(true)
        ->select('language')
        ->label('Langue du résumé')
        ->addClass('span2')
        ->options(array('fre','eng','ita','spa','deu'))
    ->parent()
        ->textarea('content')
        ->label('Résumé')
        ->addClass('span10')
        ;

$box->table('note')
    ->label('Notes')
    ->description('Remarques, notes et informations complémentaires sur le document.')
    ->repeatable(true)
        ->select('type')
        ->label('Type de note')
        ->addClass('span2')
        ->options(array('note visible','note interne','avertissement','objectifs pédagogiques','publics concernés','pré-requis', 'modalités d\'accès', 'copyright'))
    ->parent()
        ->textarea('content')
        ->label('Contenu de la note')
        ->addClass('span10');

// -----------------------------------------------------------------------------

$box = $form->fieldset('Informations de gestion');

$box->input('ref')
    ->label('Numéro de référence')
    ->addClass('span2')
    ->description('Numéro unique identifiant la notice.');

$box->input('owner')
    ->label('Propriétaire de la notice')
    ->addClass('span2')
    ->description('Personne ou centre de documentation qui a produit la notice.')
    ->repeatable(true);

$box->table('creation')
    ->label('Date de création')
    ->description('Date de création de la notice.')
        ->input('date')
        ->label('Le')
        ->addClass('span2')
    ->parent()
        ->input('by')
        ->label('Par')
        ->addClass('span2')
        ;

$box->table('lastupdate')
    ->label('Dernière modification')
    ->description('Date de dernière mise à jour de la notice.')
        ->input('date')
        ->label('Le')
        ->addClass('span2')
    ->parent()
        ->input('by')
        ->label('Par')
        ->addClass('span2')
        ;

// -----------------------------------------------------------------------------
$form->submit('Go !');

return $form;
