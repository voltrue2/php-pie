# pie Library

A very light weight PHP library to handle routing, controller management, SQL + memcache, and logging.

## How To Use

### Require pie Library

```
require_once('pie/index.php');
```

### Create Bootstrap

In your `index.php` file, you will require something similar to the following:

```php
require_once('pie/index.php');

// set up loader
Loader::setRootPath('/path/to/my/application/');

// set up configurations
Config::set('console.filePath', '/path/to/my/application/logs/server.' . date('Ymd') . '.log');
Config::set('console.noClient', false);
Config::set('console.verbose', false);
Config::set('controllerPath', '/path/to/my/application/controller/');

// set up logger
Console::setup(Config::get('console.filePath'), Config::get('console.noClient'), Config::get('console.verbose'));

// set up and run router
$router = new Router();
$router->setTrailingSlash(true);
$router->setControllerPath(Config::get('controllerPath'));

// set URI prefix: The URI parser will assume that every URI starts with the given prefix
$router->setUriPrefix('mobile');

// add reroute
$router->addReroute('/', '/test/index');

// error handling reroute
$router->addErrorReroute(404, '/error/notfound');

// start the app
$router->run();
```

## Classes

These classes will be included when you you `pie` library.

### Loader

A static class to handle `include`.

#### Static Methods

##### ::setRootPath($path [string])

Set a root path for Loader to use as the root path.

##### ::get($path)

Static method to load a PHP source code.

Example:

```php
Loader::get('my/awesome/php/lib.php');
```

##### ::getRootPath()

Static method to return the root path for Loader.

### Config

A static class to handle setting and getting configuration values across the application.

#### Static Methods

##### ::set($name [string], $value [mixed])

##### ::get($name [string])

Returns the value of configuration by its name set by `.set()`.

### Router

A router class to handle routing.

#### Methods

##### ::redirect($uri [string], $statusCode [*number])

A static method to redirect.

Example:

```php
Router::redirect('/redirect/here/', 301);
```

##### ->setTrailingSlash($enable [boolean])

Enable/Disable enforced trailing slash in URL.

##### ->setControllerPath($path [string])

Set controller directory path where all controllers are.

##### ->addReroute($from [string], $to [$to])

Adds a rerouting path.

Exmaple:

```php
$router->addReroute('/', '/reroute/here/');
```

The above example will reroute `/` to `/reroute/here/`.

##### ->addErrorReroute($code [number], $controllerName)

Assign a URI to a specific error code such as `404`.

```php
$router->addErrorReroute(404, '/error/notfound/');
```

The above example will execute `/error/notfound/` on every `404` error.

##### ->addRequestHook($uri [string], $funcName [string], $class [*mixed])

Registers a `callback` function on specified URI given by `$uri`.

The registered `callback` will be called on every matching request.

**NOTE 1:** The `callback` **MUST** return HTTP status code for an error.

If there is no error in the hook, you may return `200` or execute some functions.

Example:

```php
$router->addReuqestHook('/test/index/', 'myMethod', 'myRequestHookHandlerClass');

class myRequestHookHandlerClass {
	
	public static function myMethod($request, $response) {
		// check session
		if (/* no session */) {
			return 403;
		}
		// there is a session
		$response->redirect('/mypage/');
	}

}
```

**NOTE 2:** The hooks that are added to `controller` **ONLY** will be executed for all requests with the same `controller`.

Example:

```php
$router->addRequestHook('/example/', 'myHook');
// the above will be exected on:
/*
	/example/
	/example/index/
	/example/boo/
	/example/foo/ 
	etc...
*/
```

### Console

A static class for logging both on server and client (browser).

#### Static Methods

##### ::setup($filePath [*string], $noClient [*boolean], $verbose [*boolean])

Set up Console class.

###### $filePath

If given, Console will be logging to the path given on the server.

###### $noClient

If `false`, Console will not be logging in console of browser.

Default value is `false`.

###### $verbose

If `false` Console will not output `log`, but `warn` and `error` only.

Default value is `true`.

##### ::create($name [*string])

Returns an instance of ConsoleCore object for logging.

Example:

```php
Console::init('/logging/path/', true);
$console = Console::create();

$console->log('boo');
$console->warn('foo');
$console->error('too');
```

**NOTE:** Console will catch uncaught exceptions and log the error automatically

## Controller

`pie` library handles each request by a cooresponding `controller`.

For example a URL `/example/index` will be executing a controller class in `controller/example/index.class.php`.

### How To Create A Controller Class

First, you must set a controller path as shown below:

```php
$router = new Router();
$router->setControllerPath('path/to/my/controller/');
```

You will then define a URI by creating a controller directory and a method file in it:

```
# Define controller 'example'
mkdir path/to/my/controller/example
# A controller method called index
path/to/my/controller/example/index.class.php
```

#### Controller Class

A controller must be a valid PHP class such as:

```php
<?php

class Controller {

	public function __construct() {
		
	}

	public function GET($request, $response, $params) {

	}

}
```

The public method `GET` will handle `GET /example/index`.

The class name **MUST** be `Controller`.

Each controller method will have 3 arguments:

##### $request

`$request` is an instance of a Request class.

###### ->getData($dataName [string])

Returns a matching request data of GET/POST/PUT/DELETE

###### ->getAllData()

Returns a map of all request data of GET/POST/PUT/DELETE

###### ->getHeader($name [string])

Returns a matching request header.

###### ->getAllHeaders()

Returns all request headers.

##### $response

###### ->assign($name [string], $value [mixed])

Assigns variables to be used in response output.

###### ->html($source [string], $statusCode [*number])

If `$source` is a path to a template file, it will load it and output its content.

If `$source` is a string value, it will output as it is.

###### ->json($statusCode [*number])

Outputs assigned variables (by .assign()) as a JSON string.

###### ->redirect($uri [string], $statusCode [*number])

Redirects to the given `$uri` with given `status code`.

If `status code` is not given, it will default to '301'.

##### $params

An array of URL parameters.

Examaple:

```
/example/index/one/two/three

$params = array(
	'one',
	'two',
	'three'
);
```

## Data

A static class to output "assigned" values on controllers to templates.

#### Static Methods

#### ::get($assignedName [string])

Returns a value assigned by `$response->assign()`.

Example:

```php
// in your controller
$response->assign('boo', 'Boo');
```

```html
<!-- in your HTML template -->
<?= Data::get('boo') ?>
```

## DataSource

`pie` library comes with a very simple SQL + Memcache class.

### How To Setup DataSource

We need to properly setup `DataSource`.

**NOTE:** `DataSource` supports both `mysql` and `pgsql`.

```php
// this creates a data model
DataSource::create('myModel');
// this is how you access the created data model anywhere in your application
$model = DataSource::get('myModel');
// ttl of cache to be 60000ms
$model->setupCache('localhost', 11211, 60000);
$model->setupMaster('mysql', 'localhost', 'myDBName', 'myUser', 'myPassword');
// typically slave would have different configurations than master
$model->setupSlave('mysql', 'localhost', 'myDBName', 'myUser', 'myPassword');
```

### Handling SQL

With `DataSource`, we have data `model`.

```php
$myModel = DataSource::get('myModel');
```

### DataSource Class

#### Static Methods

##### ::create(dataModelName [string])

Create a new instance of a data model to access SQL.

```php
DataSource::create('myDataModel');
```

##### ::get(dataModelName [string])

Return an instance of a data model created by `::create()`.

```php
DataSource::create('myDataModel');
$model = DataSource::get('myDataModel');
```

### Model Class

This class is to handle SQL queries and memchache.

#### Methods

##### ->setupCache($host [string], $port [number], $ttl [number])

Setup memcache connection and TTL (it is in seconds).

##### ->ignoreCache()

Disable memcache.

##### ->setupMaster($type [string], $host [string], $dbName [string], $user [string], $password [string])

Setup SQL master connection.

###### $type = 'mysql' or 'pqsql'

##### ->setupSlave($type [string], $host [string], $dbName [string], $user [string], $password [string])

Setup SQL slave connection.

###### $type = 'mysql' or 'pqsql'

##### ->read($sql [string], $params [*array])

Returns the results of the query.

This method reads from `slave`.

Memcache is used.

##### ->readForWrite($sql [string], $params [*array]);

Returns the results of the query.

This method reads from `master`.

Memcache is **NOT** used.

##### ->write($sql [string], $params [*array])

Executes the query on `master` with auto-rollback on an exception error if in `transaction`.

Updates memcache time.

##### ->transaction()

Starts a transaction.

##### ->commit()

Commits transactioned queries.

##### ->rollback()

Rolls back transactioned queries.

### Uid Class

A static class to create a unique ID.

The ID is a string.

```php
$uid = Uid::create();
``` 

#### Static Methods

##### ::create()

Returns a unique ID string.

### Encrypt Class

A static class to create a hash and validates.

Useful for secure password validation.

```php
$pass = 'secret';
$hash = Encrypt::createHash($pass);
$validated = Encrypt::validateHash($pass, $hash);
```

#### Static Methods

##### ::createHash($password [string])

Returns a hash of a given argument `$password`.

##### ::validateHash($password [string], $hash [string])

Validates hash and a given argument `$password` and returns a boolean.

### Session Class

A static class to handle session

#### Static Methods

##### ::setup($domain [string], $prefix [string], $host [string], $port [number], $ttl [number])

`$ttl` in seconds.

##### ::get()

##### ::set($value [mixed])

##### ::delete()

### ExceptionHandler Class

A static class to resiter and handle uncaught exceptions.

#### Static Methods

##### ::add($func [string], $class [*mixed])


