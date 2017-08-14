#!/usr/bin/php
<?php
require_once("bootstrap.php");

$syncedKeys = [];

$redisTo = $redis['TO'];
$redisFrom = $redis['FROM'];

echo "Checking for keys to sync...\n";
$keysToSync = $redisFrom->keys("*");
echo "Found " . count($keysToSync) . " keys to sync.\n";
$time = microtime(true);
$processed = 0;
shuffle($keysToSync);
foreach($keysToSync as $key){
    $processed++;
    $type = $redisFrom->type($key);
    switch($type){

        case 'string':
            $redisTo->set($key, $redisFrom->get($key));
            break;

        case 'list':
            $redisTo->lpush($key, $redisFrom->lpop($key));
            break;

        case 'set':
            foreach($redisFrom->smembers($key) as $member) {
                $redisTo->sadd($key, $member);
            }
            break;

        case 'hash':
            echo "{$key} is a hashmap. Syncing in parts.\n";
            foreach($redisFrom->hkeys($key) as $field){
                echo " > Setting {$key} -> {$field}\n";
                $redisTo->hset($key, $field, $redisFrom->hget($key, $field));
                $redisFrom->hdel($key, [$field]);
            }
            break;

        default:
            die("Unsupported type: {$type}\n\n\n");
    }
    if($time < microtime(true) - 1){
        echo "Processed: {$processed} of " . count($keysToSync) . "\n";
        $time = microtime(true);
    }

    $syncedKeys[] = $key;
}

if(isset($environment['DELETE_ON_COPY']) && $environment['DELETE_ON_COPY'] == true){
    echo "Synced and cleared " . count($syncedKeys) . " keys\n";
    if(count($syncedKeys) > 0){
        $redisFrom->del($syncedKeys);
    }
}else{
    echo "Synced " . count($syncedKeys) . " keys\n";
}

sleep(30);