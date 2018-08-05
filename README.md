<p align="center">
    <a href="https://www.elastic.co/products/elasticsearch" target="_blank" rel="external">
        <img src="https://static-www.elastic.co/assets/blt45b0886c90beceee/logo-elastic.svg" height="80px">
    </a>
    <h1 align="center">Yii Framework Elasticsearch Query and ActiveRecord</h1>
    <br>
</p>

This extension provides the [elasticsearch](https://www.elastic.co/products/elasticsearch) integration for the [Yii framework](http://www.yiiframework.com).
It includes basic querying/search support and also implements the `ActiveRecord` pattern that allows you to store active
records in elasticsearch.

For license information check the [LICENSE](LICENSE.md)-file.

Documentation is at [docs/guide/README.md](docs/guide/README.md).

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii-elasticsearch/v/stable.png)](https://packagist.org/packages/yiisoft/yii-elasticsearch)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii-elasticsearch/downloads.png)](https://packagist.org/packages/yiisoft/yii-elasticsearch)
[![Build Status](https://travis-ci.org/yiisoft/yii-elasticsearch.svg?branch=master)](https://travis-ci.org/yiisoft/yii-elasticsearch)

Requirements
------------

Extension requires at least elasticsearch version 5.0.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

```
composer require --prefer-dist yiisoft/yii-elasticsearch
```

Configuration
-------------

To use this extension, you have to configure the Connection class in your application configuration:

```php
return [
    //....
    'components' => [
        'elasticsearch' => [
            'class' => 'yii\elasticsearch\Connection',
            'nodes' => [
                ['http_address' => '127.0.0.1:9200'],
                // configure more hosts if you have a cluster
            ],
        ],
    ]
];
```
