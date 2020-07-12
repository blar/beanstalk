<?php

use Blar\Beanstalk\Beanstalk;
use Blar\Beanstalk\Job;
use Blar\Beanstalk\Tube;
use Blar\Curl\MultiCurl;
use Blar\Http\Request;
use Blar\Http\Url;
use Blar\Iterators\CallbackMapIterator;
use Blar\Sockets\NetworkSocket;
use Blar\Http\Client;

require './vendor/autoload.php';

$socket = new NetworkSocket('127.0.0.1', 11300);
$beanstalk = new Beanstalk($socket);
# $response = $beanstalk->getStatistics();
# var_dump($response);
$tube = $beanstalk->useTube('test');

$urls = [
    'https://www.heise.de/',
    'https://www.golem.de/',
    'https://www.blar.de/',
    'https://winfuture.de/',
    'https://www.welt.de/',
    'https://www.giga.de/',
    'https://www.mactechnews.de/',
    'https://www.percona.com/',
    'https://www.raspberrypi.org/',
    'https://www.vaultproject.io/',
    'https://wiki.selfhtml.org/'
];

if($_SERVER['argv'][1] === 'job') {
    foreach($urls as $url) {
        $tube->addJob(new Job([
            'url' => $url
        ]));
    }
}

if($_SERVER['argv'][1] === 'tube') {

    $client = new Client();
    $multi = $client->getMultiCurl();

    while(true) {

        echo '.';

        $tube->map(function(Job $job, $id, Tube $tube) use($client, $multi) {
            echo 'j';

            $url = $job->getData()->url;

            $request = new Request();
            $request = $request->setUrl(new Url($url));
            $client->sendAsyncRequest($request);

            while($multi->pendingRequests) {
                foreach($multi as $response) {
                    echo 'r';
                    var_dump($response);
                }
            }
        });
    }

}
