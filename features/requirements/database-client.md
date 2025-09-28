# Database Command Client Feature

## Status - ✅ Completed

## Description

The database command currently has a --dbeaver option. This needs to be replaced with --client option.

The --client option will use the dbClient global config property, if configured, to open a connection to a database (see existing dbeaver option). If not, if a dbClientMysql global config property is configured and the current db type is mysql, it will open an appropriate connection to a database using the dbClientMysql value to determine what client it should use. If not, if a dbClientPgsql global config property is configured and the current db type is pgsql, it will open an appropriate connection to a database using the dbClientPgsql value to determine what client it should use.

## Implementation

### Changes Made:

1. **Database Command Registration**: Replaced `--dbeaver` option with `--client` option in `Database::register()` method
2. **Client Resolution Logic**: Added `resolveDbClient()` method that implements the priority system:
   - First priority: `dbClient` global config property
   - Second priority: Database-specific clients (`dbClientMysql` for MySQL, `dbClientPgsql` for PostgreSQL)
   - Returns `null` if no client is configured
3. **Client Support**: Implemented support for all configured client options:
   - **DBeaver**: Works with both MySQL and PostgreSQL
   - **pgAdmin**: PostgreSQL only
   - **MySQL Workbench**: MySQL only  
   - **psql (cli)**: PostgreSQL command line client
   - **mysql (cli)**: MySQL command line client
4. **Error Handling**: Added validation to ensure clients are only used with compatible database types
5. **MySQL Database Implementation**: Added `dbeaverConnectionString()` method to `Mysql` class to support DBeaver connections

### Supported Clients:

| Client | MySQL | PostgreSQL | Connection Method |
|--------|--------|------------|-------------------|
| dbeaver | ✅ | ✅ | Application launch with connection string |
| pgadmin | ❌ | ✅ | Application launch with PostgreSQL URL |
| mysql workbench | ✅ | ❌ | Application launch with MySQL URL |
| psql (cli) | ❌ | ✅ | Command line with connection parameters |
| mysql (cli) | ✅ | ❌ | Command line with connection parameters |

## Testing

### Test Coverage:
- ✅ Client resolution priority logic (global → database-specific → null)
- ✅ MySQL-specific client resolution
- ✅ PostgreSQL-specific client resolution  
- ✅ Command execution with configured client
- ✅ Error handling when no client is configured

### Test File:
- `src/Tests/DatabaseCommandTest.php` - Comprehensive test suite covering all client resolution scenarios

### Manual Testing:
1. Configure a global database client: `mchef config --dbclient`
2. Configure database-specific clients: `mchef config --dbclient-mysql` or `mchef config --dbclient-pgsql`
3. Test client opening: `mchef database --client`
4. Verify error handling with no client configured
