#!/usr/bin/env php
<?php

$dbType = $_SERVER['DATABASE'] ?: 'mysql';
$dbHost = $_SERVER['DATABASE_HOST'] ?: 'db';
$dbPort = $_SERVER['DATABASE_PORT'] ?: '';
$dbName = $_SERVER['DATABASE_NAME'] ?: 'icingaweb2';
$dbUser = $_SERVER['DATABASE_USER'] ?: 'icingaweb2';
$dbPass = $_SERVER['DATABASE_PASSWORD'] ?: 'icingaweb2';

$dsn = "$dbType:host=$dbHost";
if ($dbPort !== '') {
    $dsn .= ";port=$dbPort";
}
$dsn .= ";dbname=$dbName";

try {
    $dbh = new PDO($dsn, $dbUser, $dbPass);
} catch (PDOException $e) {
    printf("Could not connect to database: %s\n", $e->getMessage());
    exit(1);
}

try {
    $tableExists = (bool) $dbh->query('SELECT 1 FROM icinga_dbversion');
} catch (PDOException $e) {
    $tableExists = false;
}
if (! $tableExists) {
    $schemaFile = "https://github.com/Icinga/icinga2/raw/master/lib/db_ido_${dbType}/schema/${dbType}.sql";

    printf("Downloading SQL schema from: %s", $schemaFile);

    $schema = file_get_contents($schemaFile);

    try {
        $dbh->beginTransaction();
        $dbh->query($schema);
    } catch (PDOException $e) {
        printf("Could not create icingaweb2 schema: %s\n", $e->getMessage());
        $dbh->rollBack();
        exit(1);
    }

    printf("Imported IDO schema into the database.\n");
} else {
    printf("IDO database schema seems to be present.\n");
}

exit(0);
