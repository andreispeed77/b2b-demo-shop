<?php

use SprykerFeature\Shared\System\SystemConfig;
/**
 * This is the global runtime configuration for Yves and Generated_Yves_Zed in a production environment.
 */

// TODO
$config[SystemConfig::ZED_MYSQL_USERNAME] = 'development';
$config[SystemConfig::ZED_MYSQL_PASSWORD] = 'mate20mg';
$config[SystemConfig::ZED_MYSQL_DATABASE] = 'DE_development_zed';
$config[SystemConfig::ZED_MYSQL_HOST] = 'localhost';
$config[SystemConfig::ZED_MYSQL_PORT] = 3306;

$config[SystemConfig::ELASTICA_PARAMETER__INDEX_NAME] = 'de_development_catalog';
$config[SystemConfig::ELASTICA_PARAMETER__DOCUMENT_TYPE] = 'page';
