#!/usr/bin/env php
<?php
// Take a copy of an input file and store it into Amazon S3
//
//Copyright  2016 SparkPost

//Licensed under the Apache License, Version 2.0 (the "License");
//you may not use this file except in compliance with the License.
//You may obtain a copy of the License at
//
//    http://www.apache.org/licenses/LICENSE-2.0
//
//Unless required by applicable law or agreed to in writing, software
//distributed under the License is distributed on an "AS IS" BASIS,
//WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//See the License for the specific language governing permissions and
//limitations under the License.

//
// Author: Steve Tuck (September 2016)
//
// Third-party library dependencies:
//  http://php.net/manual/en/ref.mailparse.php
//      on Mac OSX: brew install homebrew/php/php55-mailparse
//
//  SparkPost PHP library - for more info see https://developers.sparkpost.com
//      installation instructions on https://github.com/SparkPost/php-sparkpost
//
require 'vendor/autoload.php';
use Aws\Ec2\Ec2Client;

// Attribution: https://gist.github.com/muffycompo/a378dcfa73c3cf354eb8
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// This unique identifier is set up in the Amazon AWS account, under S3
$storageBucket = 'stevet-test';
$urlPrefix = 'https://s3-eu-west-1.amazonaws.com/' . $storageBucket . "/";

// $uniqUrn = bin2hex(openssl_random_pseudo_bytes(128, $cstrong) ) . ".html";
$uniqUrn = base64url_encode( openssl_random_pseudo_bytes(128, $cstrong) ) . ".html";

if(!$cstrong) {
    echo "Error: crypto algorithm used by php openssl functions is not considered strong enough - stopping\n";
    exit(1);
}

// Parse the input file.  Look for, and replace any occurrences of {{view_in_browser}} with actual, specific URL
$htmlFile = file_get_contents('test.html');
$count = 0;
$uniqUrl = $urlPrefix . $uniqUrn;
$htmlFileRep = str_replace('{{view_in_browser}}', $uniqUrl , $htmlFile, $count);

if($count <1) {
    echo "No {{view_in_browser}} found in source html - stopping.\n";
    exit(1);
}

// Use the us-west-2 region and latest version of each client.
$sharedConfig = [
    'region'  => 'eu-west-1',
    'version' => 'latest',
    'profile' => 'default'
];

// Create an SDK class used to share configuration across clients.
$sdk = new Aws\Sdk($sharedConfig);
// Use an Aws\Sdk class to create the S3Client object.
$s3Client = $sdk->createS3();

// Send a PutObject request
$result = $s3Client->putObject([
    'Bucket' => $storageBucket,
    'Key'    => $uniqUrn,
    'Body'   => $htmlFileRep,
    'ContentType' => 'text/html'
]);

echo "html uploaded to " . $result['ObjectURL'] . "\n";
exit(0);