# Installation

## Requirements

Extension requires at least elasticsearch version 5.0.

## Getting Composer package

The preferred way to install this extension is through [composer](https://getcomposer.org/download/).

```shell
composer require --prefer-dist yiisoft/yii-elasticsearch
```

## Configuring application

To use this extension, you have to configure the Connection class in your application configuration:

```php
return [
    //....
    'components' => [
        'elasticsearch' => [
            'class' => 'Yiisoft\Db\ElasticSearch\Connection',
            'nodes' => [
                ['http_address' => '127.0.0.1:9200'],
                // configure more hosts if you have a cluster
            ],
        ],
    ]
];
```
