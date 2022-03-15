<?php

require_once __DIR__ . '/../vendor/autoload.php';

const VALIDATOR_URL = 'https://validator.prestashop.com/api/modules';

$inputFile  = $argv[1];
$apiKey = getenv('VALIDATOR_API_KEY');

if (empty($apiKey)) {
    throw new Exception('No API Key is set to authenticate the request to the validator. Please set the env var VALIDATOR_API_KEY');
}

if (empty($inputFile) || !file_exists($inputFile) || !is_readable($inputFile)) {
    throw new Exception(sprintf('File %s was not found, or cannot be read', $inputFile));
}

$multipart = [
    [
        'name'     => 'key',
        'contents' => $apiKey
    ],
    [
        'name'     => 'compatibility_1_7', // validate for PrestaShop 1.7
        'contents' => 'on'
    ],
    [
        'name'     => 'compatibility', // validate for PrestaShop 1.4
        'contents' => 'on'
    ],
];

// Calling the Validator API
try {

    $multipart[] = [
        'name'     => 'archive',
        'contents' => fopen($inputFile, 'r'),
    ];

	$client = new \GuzzleHttp\Client([
		'base_uri' => VALIDATOR_URL
	]);

	$response = $client->post('/api/modules', [
		'multipart' => $multipart
	]);

} catch (\Throwable $th) {
    // Maybe the Validator is not online, and we can't hold the pipeline
    print_r('Couldn\'t reach the Prestashop Validator API');
	print_r($th->getTraceAsString());
    return;
}

$stdResponse = json_decode($response->getBody()->getContents(), true);

$warningCount = $stdResponse['Details']['results']['warnings'];
$errorCount = $stdResponse['Details']['results']['errors'];

print_r("Found $warningCount warnings and $errorCount errors\n");

if ($errorCount === 0) {
    print_r(" -> ZIP Validation is OK\n");
    return;
}

foreach ($stdResponse as $category => $reports) {
    switch ($category) {
        case 'Details':
        case 'Structure':
            // do nothing
        break;
      
        default:
            if (is_array($reports)) {
                foreach ($reports as $key => $item) {
                    foreach ($item as $rule) {
                        if (is_array($rule)) {
                            foreach ($rule as $errors) {
                                foreach ($errors['content'] as $error) {
                                    if ($error['type'] === 'error') {
                                        print(" -> $category: $error[file]:$error[line]: $error[column] found error: $error[message]\n");
                                        $isValid = false;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        break;
    }
}

throw new Exception('-> ZIP Validation contains errors.');
