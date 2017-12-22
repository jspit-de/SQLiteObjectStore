# SQLiteObjectStore 

A simple object storage php class uses SQLite

### Features

- Easily store scalar, arrays and objects
- Set and update livetime of data

### Usage

```php
require '/yourpath/class.sqliteobjectstore.php';  //or autoload

$data = [
  'id'   => 1234567,
  'time' => new DateTime(),
];

$store = new SQLiteObjectStore('objstore.sqlite');

//save with live time 1 Day
$store->set('mydata1', $data,'1 Day');

$store = null;

//later or other script
$store = new SQLiteObjectStore('objstore.sqlite');
$dataCopy = $store->get('mydata1');

//Output
echo '<pre>';
var_dump($dataCopy); 
echo '</pre>';
```

### Limitations

Not usable for resources, closures and objects with closures

### Requirements

- PHP 5.3.8+
