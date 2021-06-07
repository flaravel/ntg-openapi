<?php


require "vendor/autoload.php";

use Client\Request;

$testKey    = "";
$testSecret = "";
$testAppName = 'qpm';
$testEndpoint = 'api/qiniu/tokens';

$r = (new Request($testKey, $testSecret, $testAppName, $testEndpoint))->post('http://qpm-api.songzhaopian.cn/entrance', '{"n":1,"b":2}');
//$r = (new Request($testKey, $testSecret, $testAppName, $testEndpoint))->get('http://qpm-api.songzhaopian.cn/entrance');

var_dump($r->array(), $r['code'], $r['data']);
