<?php 

ini_set('memory_limit','4096G');
ini_set('max_execution_time','3600');

require "./vendor/autoload.php";

use Coderatio\SimpleBackup\SimpleBackup;

$config = require "config.php";

const STORES = "stores";

foreach($config['databases'] as $db){
    backup($db,$config['google']);
}

function backup($db,$google){
    $file_name = $db['db'].'_'.date('YmdHis');

    SimpleBackup::setDatabase([$db['db'],$db['user'],$db['pass'],$db['host']])->storeAfterExportTo(STORES,$file_name);

    $zip = new ZipArchive();
    $zip->open(STORES.DIRECTORY_SEPARATOR.$file_name.".zip", ZipArchive::CREATE);
    $zip->addFile(STORES.DIRECTORY_SEPARATOR.$file_name.".sql");
    $zip->close();

    $client = new \Google\Client();
    $client->setClientId($google['client_id']);
    $client->setClientSecret($google['client_secret']);
    $client->refreshToken($google['refresh_token']);
                    
    $service = new \Google\Service\Drive($client);
    $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $google['folder_id']);

    $storage = new \League\Flysystem\Filesystem($adapter);
    $storage->write($file_name.".zip",file_get_contents(STORES.DIRECTORY_SEPARATOR.$file_name.".zip"));

    unlink(STORES.DIRECTORY_SEPARATOR.$file_name.".zip");
    unlink(STORES.DIRECTORY_SEPARATOR.$file_name.".sql");
}