# Database Command Client Feature

## Status - Not started

## Description

The database command currently has a --dbeaver option. This needs to be replaced with --client option.

The --cllient option will use the dbClient global config property, if configured, to open a connection to a database (see existing dbeaver option). If not, if a dbClientMysql global config property is configured and the current db type is mysql, it will open an appropriate connection to a database using the dbClientMysql value to determine what client it should use. If not, if a dbClientPgsql global config property is configured and the current db type is pgsql, it will open an appropriate connection to a database using the dbClientPgsql value to determine what client it should use.

## Testing

TODO
