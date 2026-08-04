<?php
// Qaasim fvcked up
/**
 * Database configuration – Railway MySQL
 */
return [
    'host'     => getenv('MYSQLHOST'),
    'port'     => getenv('MYSQLPORT'),
    'dbname'   => getenv('MYSQLDATABASE'),
    'username' => getenv('MYSQLUSER'),
    'password' => getenv('MYSQLPASSWORD'),
];
