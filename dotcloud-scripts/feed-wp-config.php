#!/usr/bin/env php
<?php

/*
 * This file is part of the Dotcloud Wordpress Deployment package.
 *
 * (c) Quentin Pleplé <quentin.pleple@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define("ENVIRONMENT_FILE_NAME", dirname(__FILE__) . '/../../environment.json');
define("WP_CONFIG_FILE_NAME", dirname(__FILE__) . '/../wp-config.php');
define("WP_CONFIG_sample_FILE_NAME", dirname(__FILE__) . '/../wp-config-sample.php');
// the name of the database that will be created for wordpress
define("DB_NAME", "wordpress"); 

/**********************************
  Reading environment variables
 **********************************/
if (!file_exists(ENVIRONMENT_FILE_NAME)) {
    die("Error: File environment.json does not exists. Looking at: " . ENVIRONMENT_FILE_NAME);
}
$json = @file_get_contents(ENVIRONMENT_FILE_NAME);
if (empty($json)) {
    die("Error: Can't read environment.json file.");
}
echo "File environment.json found and read\n";

$environment = @json_decode($json);
if (empty($environment)) {
    die("Error: Content of environment.json is not valid json.");
}

$properties = array("DOTCLOUD_DB_MYSQL_LOGIN", "DOTCLOUD_DB_MYSQL_PASSWORD", "DOTCLOUD_DB_MYSQL_HOST", "DOTCLOUD_DB_MYSQL_PORT");
foreach ($properties as $property) {
    if (!property_exists($environment, $property)) {
        die("Error: Missing property $property in file environment.json");
    }
}
echo "File environment.json parsed\n";

/**********************************
  Opening wp-config.php 
 **********************************/
if (file_exists(WP_CONFIG_FILE_NAME)) {
    $content = @file_get_contents(WP_CONFIG_FILE_NAME);
    if (empty($content)) {
        echo "File wp-config.php not found (looking at: " . WP_CONFIG_FILE_NAME . "). Trying to find wp-config-sample.php\n";
    }
    echo "File wp-config.php found and read\n";
} else {
    echo "File wp-config.php empty (looking at: " . WP_CONFIG_FILE_NAME . "). Trying to find wp-config-sample.php\n";
}

if (empty($content)) {
    if (!file_exists(WP_CONFIG_sample_FILE_NAME)) {
        die("Error: File wp-config-sample.php not found. Looking at: " . WP_CONFIG_sample_FILE_NAME);
    }
    
    $content = @file_get_contents(WP_CONFIG_sample_FILE_NAME);
    if (empty($content)) {
        die("Error: Can't read wp-config-sample.php file.");
    }
    echo "File wp-config-sample.php found and read\n";
}

/**********************************
  Replacing config values
 **********************************/
$configValues = array(
    "DB_NAME" => DB_NAME,
    "DB_USER" => $environment->DOTCLOUD_DB_MYSQL_LOGIN,
    "DB_PASSWORD" => $environment->DOTCLOUD_DB_MYSQL_PASSWORD,
    "DB_HOST" => $environment->DOTCLOUD_DB_MYSQL_HOST . ":" . $environment->DOTCLOUD_DB_MYSQL_PORT
);

foreach ($configValues as $property => $value) {
    echo "Setting $property = $value\n";
    $count = 0;
    $content = preg_replace('/(define\(\'' . $property . '\', \')(.*)(\'\);)/', '${1}' . $value . '${3}', $content, -1, &$count);
    if ($count == 0) {
        die("Error: Property $property not found.");
    }
}

/**********************************
  Writing wp-config.php
 **********************************/
echo "Saving modifications\n";
$handler = fopen(WP_CONFIG_FILE_NAME, 'w') or die("Error: can't open file wp-config.php to save changes.");
fwrite($handler, $content);
fclose($handler);
echo "Modifications saved.\n";