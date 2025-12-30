# setting-plugin
For update, image plugins

# Installation
- 1 ajouter la version : 
```php
private $config = ['version'       => ST_VERSION, ...
```
- 2 ajouter juste après private $config
```php
private $update_message;
```
- 3 ajouter dans initialize()
```php
$this->init_update_message();
```
- 4 ajouter  dans include_files()
```php
require_once ST_DIR_PATH . 'inc/class-update-message.php';
```
- 5 ajouter le fonction suivante
 ```php
  /*
   * INIT Update Message
   */
 private function init_update_message() {
    $this->update_message = new DC_Update_Message( $this->config );
 }
```

---

# Mise à jour de la bannière du widget Support Technique

La bannière s'affiche dans un widget du back-office WordPress grâce au plugin **DC Support Technique**.

## 1 - Modifier l'image de la bannière

Pour remplacer le GIF de la bannière :
1. Aller dans le dossier `img` du repository
2. Cliquer sur **Add file** en haut à droite
3. Sélectionner **Upload files**
4. Glisser-déposer le nouveau fichier `banner.gif` pour remplacer l'existant
5. Valider le commit

> ⚠️ Le fichier doit impérativement s'appeler `banner.gif`

## 2 - Modifier le lien de la bannière

Pour modifier l'URL de destination de la bannière :
1. Ouvrir le fichier `update-dc-support-technique.json`
2. Cliquer sur l'icône **crayon** ✏️ pour éditer le fichier
3. Modifier la valeur du champ `lien` dans la section `banniere` :
```json
"banniere":[
  {
    "lien": "https://votre-nouveau-lien.com"
  }
]
```
4. Valider le commit

---

# Structure des fichiers

| Fichier | Description |
|---------|-------------|
| `banner.gif` | Image animée de la bannière |
| `update-dc-support-technique.json` | Configuration du lien et messages de mise à jour |
| `class-update-message.php` | Classe PHP pour gérer les messages de mise à jour |
