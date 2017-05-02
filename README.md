# Yii2 component geolocation http://ipgeobase.ru/ service
## Install by composer
composer require sem-soft/yii2-ipgeobase
## Run migrations with local geo DB
```bash
$ ./yii migrate/up --migrationPath=@vendor/sem-soft/yii2-ipgeobase/migrations
```
## Or add this code into require section of your composer.json and then call composer update in console
"sem-soft/yii2-ipgeobase": "*"
## Usage
In configuration file do
```php
<?php
...
  'components'  =>  [
    ...
    'geo'	=>  [
        'class' => \sem\ipgeobase\IpGeoBase::className(),
        'serviceTimeout' => 2
    ],
    ...
  ],
...
 ?>
 ```
 Use as simple component
 ```php
<?php
if ($g = Yii::$app->geo->geo) {
    echo $g->city;
}

if ($g = Yii::$app->geo->getGeo('86.XXX.YYY.ZZZ')) {
    echo $g->city;
}

if ($g = Yii::$app->geo->getCityInfo('Москва')) {
    echo $g->city;
}
 ?>
 ```
