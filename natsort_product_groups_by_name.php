<?php

/**
 * WHMCS tool used to allow all products within a group to upgrade or downgrade between eachother.
 * @author Shaun Reitan
 * @see https://github.com/ShaunR/whmcs-tools
 */

use WHMCS\Database\Capsule;
use WHMCS\Product\Group;

// Opts
$opts = getopt("p:d:h", [ 'path:', 'dryrun:', 'help::'], $optind);

// Default
$path = rtrim(__DIR__, '/');

foreach ($opts as $opt => $value) {
    switch ($opt) {

        // WHMCS Path
        case 'p':
        case 'path':
            $path = rtrim($value, '/');
            break;

        // Dry Run
        case 'd':
        case 'dryrun':
            $dryrun = true;
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


// Init WHMCS
try {
    require_once $path . "/init.php";
} catch (\Exception $e) {
    echo "WHMCS init failed, " . $e->getMessage() . "\n";
    exit(1);
}


// Lookup groups
$groups = Group::all()->sortBy('name', SORT_NATURAL, true);

$displayOrder = 0;
foreach ($groups as $group) {

    // Increment display order counter
    $displayOrder++;

    // Only commit change if dryrun option was not passed.
    if (!isset($dryrun)) {
        echo "DRYRUN: ";

        // Upate display order
        $group->displayOrder = $displayOrder;
        $group->save();
    }

    echo "Group: " . $group->name . " display order is now " . $displayOrder . "\n";
    
}
