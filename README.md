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
- 4 ajouter le fonction suivante
 ```php
  /*
   * INIT Update Message
   */
 private function init_update_message() {
    $this->update_message = new DC_Update_Message( $this->config );
 }
```
