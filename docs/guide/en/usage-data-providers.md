Working with data providers
===========================

You can use [[\yii\data\ActiveDataProvider]] with the [[\Yiisoft\Db\ElasticSearch\Query]] and [[\Yiisoft\Db\ElasticSearch\ActiveQuery]]:

```php
use yii\data\ActiveDataProvider;
use Yiisoft\Db\ElasticSearch\Query;

$query = new Query();
$query->from('yiitest', 'user');
$provider = new ActiveDataProvider([
    'query' => $query,
    'pagination' => [
        'pageSize' => 10,
    ]
]);
$models = $provider->getModels();
```

```php
use yii\data\ActiveDataProvider;
use app\models\User;

$provider = new ActiveDataProvider([
    'query' => User::find(),
    'pagination' => [
        'pageSize' => 10,
    ]
]);
$models = $provider->getModels();
```

However, usage of [[\yii\data\ActiveDataProvider]] with enabled pagination is not efficient, since it require
performing unnecessary extra query for the total item count fetching. Also it will be unable to give you access
for the query aggregations results. You can use `Yiisoft\Db\ElasticSearch\ActiveDataProvider` instead. It allows preparing
total item count using query 'meta' information and fetching of the aggregations results:

```php
use Yiisoft\Db\ElasticSearch\ActiveDataProvider;
use Yiisoft\Db\ElasticSearch\Query;

$query = new Query();
$query->from('yiitest', 'user')
    ->addAggregation('foo', 'terms', []);
$provider = new ActiveDataProvider([
    'query' => $query,
    'pagination' => [
        'pageSize' => 10,
    ]
]);
$models = $provider->getModels();
$aggregations = $provider->getAggregations();
$fooAggregation = $provider->getAggregation('foo');
```
