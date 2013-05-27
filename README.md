phpmyrrix
=========

PHP library for Myrrix REST API calls
does not need any special installation, nor any external framework like Guzzle or Composer

@require lib::curl

@version alfa 0.0 

@todo  implemented only few REST calls, others tbd

http://myrrix.com/rest-api

@example:
```php
include_once 'myrrixRESTlibrary.php';

$client=new myrrixRESTlibrary("http://localhost",80);
//$client->debug=true;
var_dump($client->mostSimilar(array(23964,24900)));
```
