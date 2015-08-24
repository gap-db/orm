Gap ORM
===============
ORM for connect database and use PDO driver

REQUIREMENTS
------------
PHP > 5.3.0

INSTALLATION
------------
If you're using [Composer](http://getcomposer.org/) for your project's dependencies, add the following to your "composer.json":
```
"require": {
    "gap-db/orm": "1.*"
}
```

Update Modules Config List - safan-framework-standard/application/Settings/modules.config.php
```
<?php
return [
    // Safan Framework default modules route
    'Safan'         => 'vendor/safan-lab/safan/Safan',
    'SafanResponse' => 'vendor/safan-lab/safan/SafanResponse',
    // Write created or installed modules route here ... e.g. 'FirstModule' => 'application/Modules/FirstModule'
    'GapOrm' => 'vendor/gap-db/orm/GapOrm'
];
```

Add Configuration - safan-framework-standard/application/Settings/main.config.php
```
<?php
'init' => [
    'gap' => [
        'class'  => 'GapOrm\GapOrm',
        'method' => 'init',
        'params' => [
            'driver'   => 'pdo',
            'host'     => 'localhost',
            'user'     => '',
            'password' => '',
            'db'       => '',
            'charset'  => 'utf-8',
            'debug'    => false,
        ]
    ]
]
```

