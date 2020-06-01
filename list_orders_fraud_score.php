<?php

/**
 * WHMCS tool used to list the fraud score rating of your most recent orders
 * @author Shaun Reitan
 * @see https://github.com/ShaunR/whmcs-tools
 */

use WHMCS\Database\Capsule;

// Opts
$opts = getopt("p:l:h", [ 'path:', 'limit:', 'help::'], $optind);

// Default
$path = rtrim(__DIR__, '/');

foreach ($opts as $opt => $value) {
    switch ($opt) {

        // WHMCS Path
        case 'p':
        case 'path':
            $path = rtrim($value, '/');
            break;

        // Limit
        case 'l':
        case 'limit':
            $limit = $value;
            break;

        // Help Me!
        case 'h':
        case 'help':
            echo $argv[0] . " <options>\n\n";
            echo "OPTIONS\n";
            echo "  --path          Path to WHMCS installation (default: " . __DIR__ . " )\n";
            echo "  --dryrun        Only show what changes would be made (optional)\n";
            echo "  --help          Your looking at it!\n";
            exit(1);

        default:
            echo $opt . " is an unknown option, use --help for available options\n";
            exit(1);
    }
}

// Path exist?
if (!is_dir($path)) {
    echo $path . " does not exist or is not accessible\n";
    exit(1);
}

// WHMCS init.php exist in path?
if (!is_file($path . "/init.php")) {
    echo "init.php was not found in " . $path . " are you sure thats where your installed WHMCS?\n";
    exit(1);
}

// Limit Default
if (!isset($limit)) {
    $limit = 100;
}

// Limit validate
if (!is_numeric($limit)) {
    echo "limit option must be numeric\n";
    exit(1);
}


// Init WHMCS
try {
    require_once $path . "/init.php";
} catch (\Exception $e) {
    echo "WHMCS init failed, " . $e->getMessage() . "\n";
    exit(1);
}

// Lookup Orders
$orders = Capsule::table('tblorders')
    ->orderBy('date', 'desc')
    ->limit($limit)
    ->get();


// Header
echo str_pad('Date', 20) . " | ";
echo str_pad('ID', 12 ) . " | ";
echo str_pad('Order Number', 12) . " | ";
echo str_pad('Client ID', 12) . " | ";
echo str_pad('Fraud Module', 12) . " | ";
echo str_pad('Fraud Score', 10);
echo "\n";

foreach ($orders as $order) {

    // Fraud Labs
    if ($order->fraudmodule == 'fraudlabs') {
        $fraudModule = 'FraudLabs';

        // Fraud check data
        $data = json_decode($order->fraudoutput);
        if (!is_object($data)) {
            $fraudScore = '';
        } else {
            $fraudScore = $data->fraudlabspro_score;
        }
    }

    // MaxMind
    if ($order->fraudmodule == 'maxmind') {
        $fraudModule = 'MaxMind';

        // Fraud check data
        $data = explode("\n", $order->fraudoutput);
        foreach ($data as $line) {
            $parts = explode('=>', $line);
            if ($parts[0] == 'riskScore') {
                $fraudScore = $parts[1];
                break;
            }
            continue;
        }
    }

    // Skipped
    if ($order->fraudmodule == 'SKIPPED') {
        $fraudModule = 'None';
        $fraudScore = '';
    }

    // Row
    echo str_pad($order->date, 20) . " | ";
    echo str_pad($order->id, 12) . " | ";
    echo str_pad($order->ordernum, 12) . " | ";
    echo str_pad($order->userid, 12) . " | ";
    echo str_pad($fraudModule, 12) . " | ";
    echo str_pad($fraudScore, 10);
    echo "\n";
}
