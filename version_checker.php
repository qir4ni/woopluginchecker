<?php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Get latest Woo plugins
function getLatestWooVersion() {
    // POST request on WooCommerce json helper
    // https://woocommerce.com/wp-json/helper/1.0/update-check-public

    // Define the URL and data
    $url = 'https://woocommerce.com/wp-json/helper/1.0/update-check-public';
    $data = [
        'products' => [
            '27147' => [
                'product_id' => '27147', // WooCommerce Subscription
                'file_id' => '6115e6d7e297b623a169fdcf5728b224'
            ],
            '527886' => [
                'product_id' => '527886', // WooCommerce One Page Checkout
                'file_id' => 'c9ba8f8352cd71b5508af5161268619a'
            ]
        ]
    ];

    // Prepare POST data
    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-type: application/json',
            'content' => json_encode($data),
        ],
    ];

    // Create stream context
    $context  = stream_context_create($options);

    // Perform POST request
    return file_get_contents($url, false, $context);
}

function writeLogFile($response) {
    // Write into file
    $log_file = fopen("version_log.txt", "w");
    fwrite($log_file, $response);
    fclose($log_file);

    return $response;

}

function readLogFile($file) {
    // Read file content
    $log_file = fopen($file, "r");
    $file_content =  fread($log_file,filesize($file));
    fclose($log_file);
    return $file_content;
}

// Function to send a message to Telegram
function sendTelegramMessage($message) {
    $telegramBotToken = $_ENV['TELEGRAM_BOT_TOKEN'];
    // echo getenv('TELEGRAM_BOT_TOKEN'];
    $chatId = $_ENV['CHAT_ID'];
    $url = "https://api.telegram.org/bot$telegramBotToken/sendMessage?chat_id=$chatId&text=" . urlencode($message);
    // echo $url . PHP_EOL;
    file_get_contents($url);
}

// Main

// Declare filename
$file = './version_log.txt';

// Try to get the file size
$fileSize = @filesize($file);

if ($fileSize === false || filesize('version_log.txt') == 0) {
    // Write version_log.txt and if empty write using response
    writeLogFile(getLatestWooVersion());
}

// Read version_log.txt and if empty write using response
$file_content = readLogFile('version_log.txt');

// Get latest version
$response = json_decode(getLatestWooVersion(), true);


// Proceed with checking
$woo_products = json_decode($file_content, true);

$changed = false;

// Compare both latest and log
foreach ($woo_products as $product_id => $product) {
    foreach ($response as $id => $details) {
        if ($product_id == $id) {
            if ($woo_products[$product_id]['version'] != $response[$id]['version']) {
                $message = 'New version of ' . ucwords(str_replace('-', ' ', $response[$id]['slug'])) . ' ( ' . $woo_products[$product_id]['version'] . ' -> ' . $response[$id]['version'] . ' ) is release.';
                sendTelegramMessage($message);

                // Version changed
                $changed = true;
            }
        }
    }
}

// If changed, replace stored version log
if ($changed) {
    writeLogFile(getLatestWooVersion());
}

?>