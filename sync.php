#!/usr/bin/php
<?php
require_once("bootstrap.php");

$syncedKeys = [];

$remote = $redis['TO'];
$local = $redis['FROM'];

foreach($local->keys("*") as $key){
    $type = $local->type($key);
    switch($type){

        case 'string':
            $remote->set($key, $local->get($key));
            break;

        case 'list':
            $remote->lpush($key, $local->lpop($key));
            break;

        case 'set':
            foreach($local->smembers($key) as $member) {
                $remote->sadd($key, $member);
            }
            break;

        case 'hash':
            $remote->hmset($key, $local->hgetall($key));
            break;

        default:
            die("Unsupported type: {$type}\n\n\n");
    }

    //$remote->execute();
    $syncedKeys[] = $key;
}

if(isset($environment['DELETE_ON_COPY']) && $environment['DELETE_ON_COPY'] == true){
    echo "Synced and cleared " . count($syncedKeys) . " keys\n";
    if(count($syncedKeys) > 0){
        $local->del($syncedKeys);
    }
}else{
    echo "Synced " . count($syncedKeys) . " keys\n";
}

sleep(30);