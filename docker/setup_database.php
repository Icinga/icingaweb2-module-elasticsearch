#!/usr/bin/env php
<?php

/*
: ${DATABASE:=mysql}
: ${DATABASE_HOST:=db}
: ${DATABASE_PORT:=}
: ${DATABASE_NAME:=icingaweb2}
: ${DATABASE_USER:=icingaweb2}
: ${DATABASE_PASSWORD:=icingaweb2}
*/

$dbType = $_SERVER['DATABASE'] ?: 'mysql';
$dbHost = $_SERVER['DATABASE_HOST'] ?: 'db';
$dbPort = $_SERVER['DATABASE_PORT'] ?: '';
$dbName = $_SERVER['DATABASE_NAME'] ?: 'icingaweb2';
$dbUser = $_SERVER['DATABASE_USER'] ?: 'icingaweb2';
$dbPass = $_SERVER['DATABASE_PASSWORD'] ?: 'icingaweb2';

$adminUser = $_SERVER['ADMIN_USER'] ?: '';
$adminPassword = $_SERVER['ADMIN_PASSWORD'] ?: '';

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
    $tableExists = (bool) $dbh->query('SELECT 1 FROM icingaweb_user');
} catch (PDOException $e) {
    $tableExists = false;
}
if (! $tableExists) {
    $schemaFile = "/usr/share/icingaweb2/etc/schema/$dbType.schema.sql";

    if (! file_exists($schemaFile)) {
        printf("Could not find schema file at %s\n", $schemaFile);
        exit(1);
    }

    $schema = file_get_contents($schemaFile);

    try {
        $dbh->beginTransaction();
        $dbh->query($schema);
    } catch (PDOException $e) {
        printf("Could not create icingaweb2 schema: %s\n", $e->getMessage());
        $dbh->rollBack();
        exit(1);
    }

    printf("Imported icingaweb2 schema into the database.\n");
} else {
    printf("Icingaweb2 database schema seems to be present.\n");
}

if ($adminUser && $adminPassword) {
    $stm = $dbh->prepare('SELECT password_hash, active FROM icingaweb_user WHERE name = ?');
    $stm->execute(array($adminUser));

    $user = $stm->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        printf("Creating user %s in database\n", $adminUser);

        $hash = crypt($adminPassword, '$1$' . openssl_random_pseudo_bytes(12));

        try {
            $stm = $dbh->prepare(
                'INSERT INTO icingaweb_user (name, active, password_hash) VALUES (?, 1, ?)'
            );
            $stm->execute(array($adminUser, $hash));
        } catch (PDOException $e) {
            printf("Could not create user in database: %s\n", $e->getMessage());
            exit(1);
        }
    } else {
        if (preg_match('/^\$1\$(.{12}).+/', $user['password_hash'], $m)) {
            $salt = $m[1];
        } else {
            $salt = '$1$' . openssl_random_pseudo_bytes(12);
        }
        $hash = crypt($adminPassword, $salt);

        if ($user['password_hash'] !== $hash || $user['active'] !== '1') {
            printf("Updating password hash of user %s\n", $adminUser);
            try {
                $stm = $dbh->prepare('UPDATE icingaweb_user SET password_hash = ?, active = ? WHERE name = ?');
                $stm->execute(array($hash, 1, $adminUser));
            } catch (PDOException $e) {
                printf("Could not update user in database: %s\n", $e->getMessage());
                exit(1);
            }
        }
    }
}

exit(0);
