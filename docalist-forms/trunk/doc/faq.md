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

utiliser l'option comment de la méthode render() pour afficher (sous forme de commentaires dans le code html généré) le nom des templates qui ont été utilisés.

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

Le label et la description accepte du html. Le texte correspondant n'est donc pas "échappé", risque potentiel

Justification : 

- on a besoin de pouvoir générer autre chose que du texte (exemples : consulter le thesaurus avec lien vers le thesau, j'accepte les conditions légales avec lien, mise en gras d'un mot, etc.)
- ce sont des chaines maîtrisées par le programmeur (i.e. pas saisies par l'utilisateur) donc risque faible.

## Structure d'un champ (templates)

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
