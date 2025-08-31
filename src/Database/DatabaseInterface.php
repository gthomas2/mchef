<?php

namespace App\Database;

interface DatabaseInterface {

    /**
     * Wipe a database.
     * @return void
     */
    public function wipe(): void;

    /**
     * Return a dbeaver connection string.
     * @return string
     */
    public function dbeaverConnectionString(): string;
}
