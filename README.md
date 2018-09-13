# Arillo\MultiDB

*[Experimental]* Simple DataObject proxy helping to persist records in multiple databases.

[![Latest Stable Version](https://poser.pugx.org/arillo/silverstripe-multidb/v/stable?format=flat)](https://packagist.org/packages/arillo/silverstripe-multidb)
&nbsp;
[![Total Downloads](https://poser.pugx.org/arillo/silverstripe-multidb/downloads?format=flat)](https://packagist.org/packages/arillo/silverstripe-multidb)

### Requirements

SilverStripe CMS ^4.0

### Installation

```bash
composer require arillo/silverstripe-multidb
```

### Usage

```php
<?php
use SilverStripe\ORM\DataObject;

class MyDataObject extends DataObject
{
    private static
        $table_name = 'MyDataObject',

        $db = [
            'Email' => 'Varchar(255)',
            'FirstName' => 'Varchar(255)',
            'Surname' => 'Varchar(255)',
        ],

        $summary_fields = [
            'Email' => 'Email',
            'FirstName' => 'FirstName',
            'Surname' => 'Surname',
        ]
    ;
}

use Arillo\MultiDB\DataObjectProxy;

class MyDataObjectProxy extends DataObjectProxy
{
    private static
        $dataobject_class = MyDataObject::class
    ;

    public static function db_config()
    {
        // Medoo configurations (@see https://medoo.in/api/new)
        return [
            'database_type' => 'mysql',
            'database_name' => <db_name>,
            'server' => <server>,
            'username' => <username>,
            'password' => <pw>,
        ];
    }
}

// get all
$records = MyDataObjectProxy::get();

// create
$item = MyDataObjectProxy::create([
    'Email' => 'some@email.com',
]);

// save it
$item->write();

$item->exists(); // true

// update record
$item->update([
    'Email' => 'updated@email.com',
])->write();

\SilverStripe\Dev\Debug::dump($item);

// delete record
$item->delete();

```

### @TODO

* tests
* migrations
* ...
