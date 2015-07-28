<?php

require_once "../vendor/autoload.php";


$math = BitWasp\Bitcoin\Bitcoin::getMath();
$loop = React\EventLoop\Factory::create();
$factory = new \BitWasp\Bitcoin\Networking\Factory($loop);
$dns = $factory->getDns();

$peerFactory = $factory->getPeerFactory($dns);
$locator = $peerFactory->getLocator();
$manager = $peerFactory->getManager($locator);

$redis = new Redis();
$redis->connect('127.0.0.1');

$mkCache = function ($namespace) use ($redis) {
    $cache = new \Doctrine\Common\Cache\RedisCache();
    $cache->setRedis($redis);
    $cache->setNamespace($namespace);
    return $cache;
};

$headerFS = $mkCache('headers');
$heightFS = $mkCache('height');
$hashFS = $mkCache('hash');
$peerRecorderFS = $mkCache('peer.recorder');

$headerchain = new \BitWasp\Bitcoin\Chain\Headerchain(
    $math,
    new \BitWasp\Bitcoin\Block\BlockHeader(
        '1',
        '0000000000000000000000000000000000000000000000000000000000000000',
        '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b',
        1231006505,
        \BitWasp\Buffertools\Buffer::hex('1d00ffff'),
        2083236893
    ),
    new \BitWasp\Bitcoin\Chain\HeaderStorage($headerFS),
    new \BitWasp\Bitcoin\Chain\BlockIndex(
        new \BitWasp\Bitcoin\Chain\BlockHashIndex($hashFS),
        new \BitWasp\Bitcoin\Chain\BlockHeightIndex($heightFS)
    )
);
$local = $peerFactory->getAddress('192.168.192.39', 32391);
//93.150.138.149
$manager->registerRecorder($peerFactory->getRecorder($peerRecorderFS));

$node = new \BitWasp\Bitcoin\Networking\Node\Node($local, $headerchain, $manager);
$node->start();

$loop->run();

