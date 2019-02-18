<?php

$vendorDir = dirname(__DIR__);

return array (
  'yiisoft/yii2-solr' => 
  array (
    'name' => 'yiisoft/yii2-solr',
    'version' => '*',
    'alias' => 
    array (
      '@sammaye/solr' => $vendorDir . '/yiisoft/yii2-solr',
    ),
  ),
  'yiisoft/yii2-notice' => 
  array (
    'name' => 'yiisoft/yii2-notice',
    'version' => '*',
    'alias' => 
    array (
      '@yii/notice' => $vendorDir . '/yiisoft/yii2-notice',
    ),
  ),
  'yiisoft/yii2-gii' => 
  array (
    'name' => 'yiisoft/yii2-gii',
    'version' => '2.0.5.0',
    'alias' => 
    array (
      '@yii/gii' => $vendorDir . '/yiisoft/yii2-gii',
    ),
  ),
  'omnilight/yii2-scheduling' => 
  array (
    'name' => 'omnilight/yii2-scheduling',
    'version' => '1.0.7.0',
    'alias' => 
    array (
      '@omnilight/scheduling' => $vendorDir . '/omnilight/yii2-scheduling',
    ),
    'bootstrap' => 'omnilight\\scheduling\\Bootstrap',
  ),
  'yiisoft/yii2-swiftmailer' => 
  array (
    'name' => 'yiisoft/yii2-swiftmailer',
    'version' => '2.0.7.0',
    'alias' => 
    array (
      '@yii/swiftmailer' => $vendorDir . '/yiisoft/yii2-swiftmailer',
    ),
  ),
  'yiisoft/yii2-redis' => 
  array (
    'name' => 'yiisoft/yii2-redis',
    'version' => '2.0.6.0',
    'alias' => 
    array (
      '@yii/redis' => $vendorDir . '/yiisoft/yii2-redis',
    ),
  ),
  'yiisoft/yii2-mongodb' => 
  array (
    'name' => 'yiisoft/yii2-mongodb',
    'version' => '2.1.5.0',
    'alias' => 
    array (
      '@yii/mongodb' => $vendorDir . '/yiisoft/yii2-mongodb',
    ),
  ),
  'yiisoft/yii2-bootstrap' => 
  array (
    'name' => 'yiisoft/yii2-bootstrap',
    'version' => '2.0.7.0',
    'alias' => 
    array (
      '@yii/bootstrap' => $vendorDir . '/yiisoft/yii2-bootstrap',
    ),
  ),
  'yiisoft/yii2-debug' => 
  array (
    'name' => 'yiisoft/yii2-debug',
    'version' => '2.0.12.0',
    'alias' => 
    array (
      '@yii/debug' => $vendorDir . '/yiisoft/yii2-debug',
    ),
  ),
);
