<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <a href="https://www.elastic.co/products/elasticsearch" target="_blank">
        <img src="https://avatars.githubusercontent.com/u/6764390?s=200&v=4" height="80px" alt="Elasticsearch">
    </a>
    <h1 align="center">Yii Database Elasticsearch Query and ActiveRecord</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db-elasticsearch/v/stable.png)](https://packagist.org/packages/yiisoft/db-elasticsearch)
[![Total Downloads](https://poser.pugx.org/yiisoft/db-elasticsearch/downloads.png)](https://packagist.org/packages/yiisoft/db-elasticsearch)
[![Build status](https://github.com/yiisoft/db-elasticsearch/workflows/build/badge.svg)](https://github.com/yiisoft/db-elasticsearch/actions?query=workflow%3Abuild)
[![Code Coverage](https://codecov.io/gh/yiisoft/db-elasticsearch/branch/master/graph/badge.svg)](https://codecov.io/gh/yiisoft/db-elasticsearch)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb-elasticsearch%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db-elasticsearch/master)
[![static analysis](https://github.com/yiisoft/db-elasticsearch/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/db-elasticsearch/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/db-elasticsearch/coverage.svg)](https://shepherd.dev/github/yiisoft/db-elasticsearch)
[![psalm-level](https://shepherd.dev/github/yiisoft/db-elasticsearch/level.svg)](https://shepherd.dev/github/yiisoft/db-elasticsearch)

This extension provides the [elasticsearch](https://www.elastic.co/products/elasticsearch) integration for the [Yii framework](https://www.yiiframework.com).
It includes basic querying/search support and also implements the `ActiveRecord` pattern that allows you to store active
records in elasticsearch.

## Requirements

- Extension requires at least elasticsearch version 5.0.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/db-elasticsearch
```

## General usage

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

## Documentation

- Guide: [English](docs/guide/en/README.md), [Français](docs/guide/fr/README.md), [Português - Brasil](docs/guide/pt-BR/README.md), [日本語](docs/guide/ja/README.md)
- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Database Elasticsearch Query and ActiveRecord is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
