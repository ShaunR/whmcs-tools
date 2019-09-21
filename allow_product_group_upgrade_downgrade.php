<?php

/**
 * WHMCS tool used to allow all products within a group to upgrade or downgrade between eachother.
 * @author Shaun Reitan
 * @see https://github.com/ShaunR/whmcs-tools
 */

use WHMCS\Database\Capsule;
use WHMCS\Product\Group;

$opts = getopt("p:g:h", [ 'path:', 'group:', 'help::'], $optind);

// Default
$path = rtrim(__DIR__, '/');

foreach ($opts as $opt => $value) {
    switch ($opt) {

        // WHMCS Path
        case 'p':
        case 'path':
            $path = rtrim($value, '/');
            break;

        // WHMCS Group
        case 'g':
        case 'group':
            $gid = $value;
            break;

        // Help Me!
        case 'h':
        case 'help':
            echo $argv[0] . " <options>\n\n";
            echo "OPTIONS\n";
            echo "  --path          Path to WHMCS installation (default: " . __DIR__ . " )\n";
            echo "  --group         WHMCS Product group id (Required)\n";
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

// Product Group
if (!isset($gid)) {
    echo "You must specify a WHMCS product group! use --help for more info\n";
    exit(1);
}

if (!is_numeric($gid)) {
    echo "group option must be the id of the WHMCS product group\n";
    exit(1);
}

// Init WHMCS
try {
    require_once $path . "/init.php";
} catch (\Exception $e) {
    echo "WHMCS init failed, " . $e->getMessage() . "\n";
    exit(1);
}

// Check for group
$group = Group::find($gid);
if (is_null($group)) {
    echo "Failed to find product group with id " . $gid . "\n";
    exit(1);
}

foreach ($group->products as $product1) {
    foreach ($group->products as $product2) {

        // Skip if product1 and product2 are the same product
        if ($product1->id == $product2->id) {
            continue;
        }

        // Check for existing upgrade relationship
        $row = Capsule::table('tblproduct_upgrade_products')
            ->where('product_id', '=', $product1->id)
            ->where('upgrade_product_id', '=', $product2->id)
            ->first();

        // Skip if row was found!
        if (!is_null($row)) {
            echo $product1->name . " is already allowed to upgrade to " . $product2->name . ", skipping\n";
            continue;
        }

        // Insert new row
        try {
            Capsule::table('tblproduct_upgrade_products')
                ->insert([
                    'product_id' => $product1->id,
                    'upgrade_product_id' => $product2->id
                ]);
        } catch (\Exception $e) {
            echo "Failed to add entry to allow " . $product1->name . " to upgrade to " . $product2->name . "\n";
            continue;
        }

        echo "Added entry to allow " . $product1->name . " to upgrade to " . $product2->name . "\n";
    }
}
