[Accueil](..)

# FAQ

## Code html généré
Par défaut, le code généré est compacté :

```html
<div><label for="surname">Nom : </label><input name="surname" type="text" id="surname"/></div>
```

Utiliser l'option `indent` de la méthode `Field::render()` pour générer un code lisible et indenté :

```html
<div>
    <label for="surname">Nom : </label>
    <input name="surname" type="text" id="surname"/>
</div>
```

Par défaut, l'option `indent` utilise quatre espaces pour indenter le code, mais vous pouvez également spécifier votre propre chaine d'intenation (une tabulation, deux espaces, etc.)

## Aide au débogage des thèmes

Lorsqu'on développe un thème, il est parfois difficile de savoir quel est le template qui a généré telle ou telle partie du code.

La méthode `Field::render()` dispose d'une option `comment` qui permet d'afficher (sous forme de commentaires dans le code html généré) le nom de chacun des templates qui ont été utilisés lors du rendu :

```php
$form->render($theme, 'container', array('options' => array('comment' => true)));
```

Exemple de code généré lorsque l'option `comment` est activée :
 
```html
<!-- start default-field.container.php-->
    <div>
        <!-- start default-field.label.php-->
            <label for="surname">Nom : </label>
        <!-- end  default-field.label.php-->

        <!-- start default-field.errors.php-->
        <!-- end  default-field.errors.php-->

        <!-- start default-field.values.php-->
            <!-- start default-input.widget.php-->
            <input name="surname" type="text" id="surname"/>
            <!-- end  default-input.widget.php-->
        <!-- end  default-field.values.php-->
    </div>
<!-- end  default-field.container.php-->
```

## Label et description

Le label et la description acceptent du html qui sera écrit tel quel (non echappé) dans la sortie html générée. Cela peut être vu comme un risque potentiel d'attaque XSS.

Le raisonnement ayant conduit à autoriser du html brut dans les libellés et les description est le suivant : 

1. On a souvent besoin de pouvoir générer autre chose que du texte brut : un bloc description qui contient un lien pour obtenir plus d'informations, une case à cocher "j'accepte les mentions légales" dont le libellé contient un lien pour pouvoir les consulter, un label dont certains mots sont en gras ou en italique, etc.
2. La description et le label sont des chaines maîtrisées par le programmeur (elles ne sont pas saisies par l'utilisateur) et donc le risque est assez faible.

## Structure d'un champ (templates)

Chaque champ est composés des blocs suivants : 

- `container` : bloc principal du champ et template d'entrée pour le rendu d'un champ,
- `label` : libellé du champ,
- `description` : un bloc description
- `errors` : affiche les erreurs présentes dans le champ
- `values` : un bloc values qui va contenir toutes les occurences du champ (si le champ n'est pas répétable, on aura une seule occurence)

Le bloc `description` peut s'afficher soit avant soit après le bloc values, selon la valeur de la propriété `descriptionAfter` du champ (cf. la doc de la méthode `Field::description()`).

Dans le bloc `values`, on va trouver :

- `widget` c'est le champ à proprement parler. Il sera répété pour chacune des occurences du champ. 
- `add` : un bouton "ajouter" (bouton de clonage) si le champ est répétable.


| Type de champ   | Container | Label                 | Description   | values       | widget        | bouton ajouter |
| --------------- | --------- | --------------------- | ------------- | ------------ | ------------- | -------------- |
| `Field`         | div.field | label for="widget-id" | p.description | widget+, add | erreur        |                |
| `··Button`      |           | aucun (2)             |               |              | button.button |                |
| `····Reset`     |           |                       |               |              | button.reset  |                |
| `····Submit`    |           |                       |               |              | button.submit |                |
| `··Choice`      |           |                       |               |              | ul>li*        |                |
| `····Checklist` |           | label (3)             |               |              |               |                |
| `····Radiolist` |           |                       |               |              |               |                |
| `····Select`    |           |                       |               |              | select        |                |
| `··Fields`      |           |                       |               |              | aucun         |                |
| `····Fieldset`  |           |                       |               |              | fieldset      |                |
| `····Form`      | aucun (1) | h2                    |               |              | form          |                |
| `····Table`     |           |                       |               | table>tbody> | tr>td*        |                |
| `····Tag`       | aucun (1) |                       |               |              | tag           |                |
| `··Input`       |           |                       |               |              | input         |                |
| `····Checkbox`  |           |                       |               |              |               |                |
| `····Hidden`    | aucun (1) |                       |               |              |               |                |
| `····Password`  |           |                       |               |              |               |                |
| `····Radio`     |           |                       |               |              |               |                |
| `··Textarea`    |           |                       |               |              | textarea      |                |
| --------------- | --------- | --------------------- | ------------- | ------------ | ------------- | -------------- |

Remarques :

- Lorsque la case est vide, cela signifie que le champ hérite du template de sa classe parent.
- (1) Aucun container n'est généré.
- (2) le label du bouton est utilisé comme texte du bouton, donc rien n'est généré dans le template label.
- (3) le label d'une checklist ne contient pas d'attribut for (on ne saurait pas à chquel checkbox le rattacher)

La partie `widget` correspond à la partie du champ qui permet de faire la saisie. Une autre manière d'y penser, c'est d'imaginer le champ comme étant répétable et de voir qu'elle est la partie qui sera clonée lorsqu'on clique sur le bouton ajouter. Pour la majorité des types de champ, c'est simple à imaginer. Pour le contrôle `Table`, un peu moins : le widget d'une table, c'est le tr qui se trouve dans la partie vody de la table. Le rendu du tag table est fait par le tempalte table.values.

```xml
<!-- field.container.php -->
<container-du-champ>

    <!-- field.label.php (si le champ à un label) -->
    <label for="id du champ">label-du-champ</label>

    <!-- field.description.php (si description et descriptionAfter === false) -->
    <description />

    <!-- field.errors.php -->
    <errors />

    <!-- field.values.php -->
    <values>
        <foreach value>
            <!-- field.widget.php -->
            <widget>

            <!-- field.add.php (si le champ est repeatable) -->
            <button class="btn-add-field">+ ajouter</button>
        </foreach value>
    </values>

    <!-- field.description.php (si description et descriptionAfter === true) -->
    <description />

</container-du-champ>

```

- field.label.php : affiche le bloc libellé du champ.

  Ce template n'est PAS appellé si le champ n'a pas de label.

- field.description.php : affiche le bloc description du champ

  Ce template n'est PAS appellé si le champ n'a pas de description.
  Il est appellé soit avant le widget (si `descriptionAfter===false`), soit après (si `descriptionAfter===true`, ce qui est la valeur par défaut), mais pas les deux.
  Le template field.description peut tester descriptionAfter s'il a besoin de générer du code différent selon le cas.

## Stylage / code html des checklist

### Code généré pour une checklist simple
```html
<div class="field checklist">
    <label>Libellé de la checklist</label>
    <ul>
        <li>
            <label><input name="simple[]" type="checkbox" value="value1"/>option 1</label>
        </li>
        <li>
            <label><input name="simple[]" type="checkbox" value="value2"/>option 2</label>
        </li>
    </ul>
    <p class="description">Description de la checklist.</p>
</div>
```

[Tester](../../tests/?file=doc-checklist-simple&options[]=indent#html)

### Code généré pour une checklist hiérarchique

```html
<div class="field checklist">
    <label>Libellé de la checklist</label>
    <ul>
        <li>
            <p>Libellé du groupe 1</p>
            <ul>
                <li>
                    <label><input name="hier[]" type="checkbox" value="value1"/>option 1</label>
                </li>
                <li>
                    <label><input name="hier[]" type="checkbox" value="value2"/>option 2</label>
                </li>
            </ul>
        </li>
        <li>
            <p>Libellé du groupe 2</p>
            <ul>
                <li>
                    <label><input name="hier[]" type="checkbox" value="value3"/>option 3</label>
                </li>
                <li>
                    <label><input name="hier[]" type="checkbox" value="value4"/>option 4</label>
                </li>
            </ul>
        </li>
    </ul>
    <p class="description">Description de la checklist.</p>
</div>
```

[Tester](../../tests/?file=doc-checklist-hierarchical&options[]=indent#html)

### Stylage

- Supprimer les listes à puces : `.checklist ul {list-style: none}`

- Styler l'ensemble du bloc qui contient les cases à cocher : `.checklist>ul {}`

- Styler le libellé de la checklist : `.checklist>label {}`

- Styler le libellé des groupes d'options : `.checklist li p {}`

- Styler le libellé des options : `.checklist li label {}`

- Afficher les cases horizontalement (inline) : `.checklist li {display: inline-block}`

- Ajouter de l'espace entre la case à cocher et son libellé : `.checklist input {margin-right: 5px}`
