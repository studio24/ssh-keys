<?php
/**
 * Merge public keys script
 *
 * Ensures SSH public keys are safely merged with the correct line return in-between each key
 */

error_reporting(-1);
ini_set('display_errors', true);

if (strtoupper(PHP_SAPI) !== 'CLI') {
   throw new Exception('You can only run this PHP file via CLI');
}

$help = <<<EOD

Merge Public Keys script
------------------------

Used to merge public SSH keys in a folder into one file. Ensures SSH public keys are safely merged with the correct line return in-between each key 
 
Please ensure all keys are named *.pub, this script expects each key to start with ssh-rsa

Automatically saves to tmp/[key-folder-name]-public-keys.conf

Usage:
> php merge-public-keys.php  <folder to read keys from> <output file>

Merge all keys in 'staff' folder to tmp/staff-public-keys.conf 
> php merge-public-keys.php staff tmp/staff-public-keys.conf

(c) 2017-18 Studio 24 Ltd

EOD;

if ($argc < 3) {
    throw new Exception('You must pass the folder to read keys from and the output file' . PHP_EOL . $help);
}

// Read in public keys
$keyFolder = filter_var($argv[1], FILTER_SANITIZE_STRING);
$keyPath = realpath($argv[1]);
$keyFolder = basename($keyFolder);
if ($keyPath === false) {
    throw new Exception('Key path not found at ' . $keyFolder);
}

// List of directories to include for key lookup
$indexList = [$keyPath];

// Look up permitted freelancers from pre-existing file on server.
$freelancers_file = $_SERVER['HOME'].'/freelancers.json'; 

if (file_exists($freelancers_file) && $file = file_get_contents($freelancers_file)) {

    $freelancers = json_decode($file,true);

    if (is_array($freelancers)) {

        foreach ($freelancers as $name) {     
            if (file_exists('freelancers/'.$name)) {
                $indexList[] = 'freelancers/'.$name;
            }
        }

    }
}

// Build list of keys from indexList directories.
$data = [];

foreach ($indexList as $directory) {
    $dir = new DirectoryIterator($directory);
    foreach ($dir as $fileinfo) {
        if (!$fileinfo->isDot()) {
            if ($fileinfo->getExtension() == 'pub') {
                echo 'Adding '.$fileinfo->getPathname()."\n";
                $data[] = trim(file_get_contents($fileinfo->getPathname()));
            }
        }
    }
}

// Write data to file
$file = filter_var($argv[2], FILTER_SANITIZE_STRING);
if (!is_writable(dirname($file))) {
    throw new Exception("Cannot write to parent folder " . dirname($file));
}
if (file_exists($file) && !is_writable($file)) {
    throw new Exception("Cannot write to file " . $outputFile);
}

// Check if we need to update the file (skip it if identical)
$contents = implode("\n", $data);
if (empty($contents)) {
    throw new Exception("No SSH keys to copy");
}
if (file_exists($file)) {
    $existing = file_get_contents($file);
    if ($contents == $existing) {
        echo "SKIPPING";
        exit(0);
    }
}

$result = file_put_contents($file, $contents);
if ($result === false) {
    throw new Exception("Failed to write merged public keys to file $file");
}

echo "DONE";
exit(0);
