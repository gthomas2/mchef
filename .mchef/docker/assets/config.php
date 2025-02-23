<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dblibrary = 'native';
$CFG->dbtype    = getenv('DB_TYPE');
$CFG->dbhost    = getenv('DB_HOST');
$CFG->dbname    = getenv('DB_NAME');
$CFG->dbuser    = getenv('DB_USER');
$CFG->dbpass    = getenv('DB_PASS');
$CFG->prefix    = 'mdl_';

if ($CFG->dbtype === 'mysqli') {
    $CFG->dblibrary = 'native';
    $CFG->dboptions = [
        'dbpersist' => 0,
        'dbcollation' => 'utf8mb4_unicode_ci',
    ];
} else {
    $CFG->dboptions = [
        'dbpersist' => false
    ];
}

$CFG->wwwroot   = getenv('WWWROOT');
$CFG->dataroot  = getenv('MOODLE_DATA');
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;


// Force a debugging mode regardless the settings in the site administration
$debug = getenv('DEBUG');

if ($debug) {
    @error_reporting(E_ALL | E_STRICT);
    @ini_set('display_errors', '1');
    $CFG->debug = (E_ALL | E_STRICT);
    $CFG->debugdisplay = 1;

    // Show performance data in footer.
    define('MDL_PERF', true);
    define('MDL_PERFDB', true);
    define('MDL_PERFTOLOG', true);
    define('MDL_PERFTOFOOT', true);
}


require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
