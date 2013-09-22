PhpTus
======

Library for [tus server](http://www.tus.io/) (tus protocol 0.2.1)

Installation
------------

use [composer](http://getcomposer.org/)

Client Usage
------------

```php
$client = new PhpTus\Client();
$client->setFilename('/path/of/the/file/to/upload');
$client->setEndPoint('http://example.com/files/');

// Upload 1024 bytes of your file
$client->upload(1024);

// Get the fingerprint to upload the remaining later
$fingerprint = $client->getFingerprint();

// New session
$client = new PhpTus\Client();
$client->setFilename('/path/of/the/file/to/upload');
$client->setEndPoint('http://example.com/files/');
// Indicate the old fingerprint, to resume the upload
$client->setFingerprint($fingerprint);

// Upload the next 2048 bytes of your file 
// (1024 bytes have been sent in the first request)
$client->upload(2048);

// Upload the remaining of your file
$client->upload();
```

Server Usage
------------

*Require* : redis-server (on Debian/Ubuntu, apt-get install redis-server)

```php
// Create and configure server
$server = new \PhpTus\Server(__DIR__.'/path-to-save-file', 
                            '/url-to-call/', 
                            array('prefix' => 'php-tus')
);

// Run server
$server->process(true);
```

If you are with an Apache server, add an .htaccess file to redirect all request in the php page (without that, your PATCH call failed), like :

```bash
RewriteEngine on

RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [QSA,L]
```


Author
------

Simon Leblanc <contact@leblanc-simon.eu>


License
-------

[MIT](http://opensource.org/licenses/MIT)