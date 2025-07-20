<?php

namespace App\Service;

use App\Helpers\OS;
use App\Model\DockerData;
use App\Model\PluginsInfo;
use App\Model\Recipe;
use App\Traits\ExecTrait;
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Exception;

class Main extends AbstractService {

    use ExecTrait;

    /**
     * @var Recipe
     */
    private $recipe;
    /**
     * @var Recipe
     */
    private $dockerService;

    /**
     * @var Plugins plugins service
     */
    private $pluginsService;

    /**
     * @var PluginsInfo
     */
    private PluginsInfo $pluginInfo;

    /**
     * @var \Twig\Environment
     */
    private \Twig\Environment $twig;

    /**
     * @var string|null $chefPath
     */
    private ?string $chefPath = null;

    protected function __construct() {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__.'/../../templates');
        $loader->addPath(__DIR__.'/../../templates/moodle', 'moodle');
        $loader->addPath(__DIR__.'/../../templates/moodle/browser', 'moodle-browser');
        $loader->addPath(__DIR__.'/../../templates/docker', 'docker');
        $this->twig = new \Twig\Environment($loader);
    }

    final public static function instance(?CLI $cli): Main {
        return self::setup_instance($cli);
    }

    public function getChefPath($failOnNotFound = false): ?string {
        if ($this->chefPath) {
            return $this->chefPath;
        }
        $chefPath =  File::instance()->findFileInOrAboveDir('.mchef');
        if ($failOnNotFound && !is_dir($chefPath)) {
            $this->cli->alert('Your current working directory, or the directories above it, do not contain a .mchef directory');
            die;
        }
        $this->chefPath = $chefPath;
        return $this->chefPath;
    }

    public function getDockerPath() {
        return $this->getChefPath().'/docker';
    }

    public function getAssetsPath() {
        return $this->getDockerPath().'/assets';
    }

    private function startDocker($ymlPath) {
        $ymlPath=OS::path($ymlPath);
        $this->cli->notice('Starting docker containers');
        // @Todo - force-recreate and --build need to be flags that get passed in, not hard coded.
        $cmd = "docker compose -f \"$ymlPath\" up -d --force-recreate --build";
        $this->execPassthru($cmd, "Error starting docker containers - try pruning with 'docker builder prune' OR 'docker system prune' (note 'docker system prune' will destroy all non running container images)");

        // @Todo - Add code here to check docker ps for expected running containers.
        // For example, if one of the Apache virtual hosts has an error in it, it will bomb out.
        // So we need to spin here for about 10 seconds checking that the containers are running.

        $this->cli->success('Docker containers have successfully been started');
    }

    public function startContainers(): void {
        $this->cli->notice('Starting containers');
        $dockerService = Docker::instance($this->cli);
        $moodleContainer = $this->getDockerMoodleContainerName();
        $dockerService->startDockerContainer($moodleContainer);
        $dbContainer = $this->getDockerDatabaseContainerName();
        $dockerService->startDockerContainer($dbContainer);

        $this->cli->success('All containers have been started');
    }

    public function stopContainers(?Recipe $recipe = null): void {
        $this->cli->notice('Stopping containers');
        $recipe = $recipe ?? $this->getRecipe();
        $moodleContainer = $this->getDockerMoodleContainerName();
        $dbContainer = $this->getDockerDatabaseContainerName();
        $behatContainer = $recipe->containerPrefix.'-behat';
        $toStop = [
            $moodleContainer,
            $dbContainer
        ];

        $dockerService = Docker::instance($this->cli);
        $containers = $dockerService->getDockerContainers(false);
        $stoppedContainers = 0;
        foreach ($containers as $container) {
            $name = $container->names;
            $this->cli->notice('Stopping container: '.$name);
            if (in_array($name, $toStop) || strpos($behatContainer, $name) === 0) {
                $dockerService->stopDockerContainer($name);
                $stoppedContainers++;
            }
        }

        if ($stoppedContainers > 0) {
            $this->cli->success('All containers have been stopped');
        } else {
            $this->cli->success('No containers were running for this recipe');
        }
    }

    private function configureDockerNetwork(Recipe $recipe): void {
        $dockerService = Docker::instance($this->cli);
        // TODO LOW priority- default should be mc-network unless defined in recipe or main config.
        $networkName = 'mc-network';

        if ($dockerService->networkExists($networkName)) {
            $this->cli->info('Skipping creating network as it exists: '.$networkName);
        } else {
            $this->cli->info('Configuring network ' . $networkName);
            $cmd = "docker network create $networkName";
            $this->exec($cmd, "Error creating network $networkName");
        }

        $dbContainer = $this->getDockerDatabaseContainerName();
        $moodleContainer = $this->getDockerMoodleContainerName();

        $cmd = "docker network connect $networkName $dbContainer";
        $this->exec($cmd, "Failed to connect $dbContainer to $networkName");

        if ($recipe->includeBehat && $recipe->host && $recipe->host !== 'localhost') {
            // Note - the alias is essential here for behat tests to work.
            // The behat docker container needs to understand the host name when chrome driver tries
            // to operate on the host.
            $cmd = "docker network connect $networkName $moodleContainer --alias $recipe->host --alias $recipe->behatHost";
        } else {
            $cmd = "docker network connect $networkName $moodleContainer";
        }

        $this->exec($cmd, "Failed to connect $moodleContainer to $networkName");

        $this->cli->success('Network configuration successful');

        if ($recipe->installMoodledb) {
            // Try installing The DB
            $this->cli->notice('Try installing MoodleDB');

            $dbnotready = true;
            $dbCheckCmd = 'docker exec ' . escapeshellarg($dbContainer) . ' sh -c ' . escapeshellarg('psql -U ' . $recipe->dbUser . ' -d ' . $recipe->dbName . ' -c "SELECT 1" > /dev/null 2>&1');


            while ($dbnotready) {
                exec($dbCheckCmd, $output, $returnVar);
                if ($returnVar === 0) {
                    $dbnotready = false;
                }
                $this->cli->notice('Waiting for DB '.$dbContainer.' to be ready');
                sleep(1);
            }
            $this->cli->notice('DB '.$dbContainer.' ready!');

            $dockerDbExecBase = 'docker exec ' . escapeshellarg($dbContainer);

            if (OS::isWindows()) {
                // For Windows, `cmd` is used with `/c` to execute the command
                $dbSchemaInstalledCmd = $dockerDbExecBase . ' cmd /c "psql -U ' . $recipe->dbUser . ' -d ' . $recipe->dbName . ' -c \\"SELECT * FROM mdl_course\\" > nul 2>&1 || exit 1"';
            } else {
                // For Linux, use `sh` as the shell
                $dbSchemaInstalledCmd = $dockerDbExecBase . ' sh -c "psql -U ' . $recipe->dbUser . ' -d ' . $recipe->dbName . ' -c \\"SELECT * FROM mdl_course\\" > /dev/null 2>&1 || exit 1"';
            }

            // Execute the command
            exec($dbSchemaInstalledCmd, $output, $returnVar);
            $dbSchemaInstalled = $returnVar === 0;
            $doDbInstall = !$dbSchemaInstalled;

            if (!$doDbInstall) {
                $this->cli->notice('DB already installed. Skipping installation');
            } else {
                $this->cli->notice('Installing DB');
                
                // Get language from global config, default to 'en' if not set
                $globalConfig = Configurator::instance($this->cli)->getMainConfig();
                $lang = $globalConfig->lang ?? 'en';
                
                $installoptions =
                    '/var/www/html/moodle/admin/cli/install_database.php --lang=' . $lang . ' --adminpass=123456 --adminemail=admin@example.com --agree-license --fullname=mchef-MOODLE --shortname=mchefMOODLE';
                $cmdinstall = 'docker exec ' . $moodleContainer . ' php ' . $installoptions;

                // Try to install
                try {
                    $this->execPassthru($cmdinstall);
                } catch (\Exception $e) {
                    // Installation failed, ask if DB should be dropped?
                    $this->cli->error($e->getMessage());
                    $overwrite = readline("Do you want to delete the db and install fresh? (yes/no): ");

                    if (strtolower(trim($overwrite)) === 'yes') {
                        $this->cli->notice('Overwriting the existing Moodle database...');
                        // Drop all DB Tables in public
                        $dbdeletecmd = 'docker exec ' . $dbContainer . ' psql -U ' . $recipe->dbUser . ' -d ' . $recipe->dbName .
                            ' -c "DO \$\$ DECLARE row RECORD; BEGIN FOR row IN (SELECT tablename FROM pg_tables WHERE schemaname = \'public\') LOOP EXECUTE \'DROP TABLE IF EXISTS public.\' || quote_ident(row.tablename); END LOOP; END \$\$;"';

                        exec($dbdeletecmd, $outpup, $return);

                        // Do the installation again, should work now
                        $this->execPassthru($cmdinstall);

                    } else {
                        $this->cli->notice('Skipping Moodle database installation.');
                    }
                }
            }
            $this->cli->notice('Moodle database installed successfully.');
        }

        // Print out wwwroot
        $this->cli->notice('Installation finished. Your mchef-Moodle is now available at: ' . $recipe->wwwRoot );
    }

    private function checkPortBinding(Recipe $recipe): bool {
      $dockerService = Docker::instance($this->cli);
      return $dockerService->checkPortAvailable($recipe->port);
    }

    public function hostPath() : string {
        if (!OS::isWindows()) {
            return '/etc/hosts';
        } else {
            return 'C:\\Windows\\System32\\drivers\\etc\\hosts';
        }
    }

    private function updateHostHosts(Recipe $recipe): void {
        $destHostsFile = $this->hostPath();

        if ($recipe->updateHostHosts) {
            try {
                $hosts = file($this->hostPath());
            } catch (\Exception $e) {
                $this->cli->error('Failed to update host hosts file');
            }
        }
        $toAdd = [];
        if (!empty($recipe->host)) {
            $toAdd[] = $recipe->host;
        }
        if (!empty($recipe->behatHost)) {
            $toAdd[] = $recipe->behatHost;
        }
        $toAdd = array_filter($toAdd, function($new) use($hosts) {
            foreach ($hosts as $existing) {
                if (strpos($existing, $new) !== false && strpos($existing, '#') === false) {
                    // Already exists - no need to add.
                    return false;
                }
            }
            return true;
        });

        if (empty($toAdd)) {
            $this->cli->info("No hosts to add to host $destHostsFile file");
            return;
        }

        $toAddLines = [];
        foreach ($toAdd as $newHost) {
            $newHost = "\n".'127.0.0.1       '.$newHost;
            $toAddLines[] = $newHost;
        }

        array_unshift($toAddLines, "\n# Hosts added by mchef");
        array_push($toAddLines, "\n# End hosts added by mchef");

        $hosts = array_merge($hosts, $toAddLines);
        $hostsContent = implode("", $hosts);
        $tmpHostsFile = tempnam(sys_get_temp_dir(), "etc_hosts");
        file_put_contents($tmpHostsFile, $hostsContent);

        if (!OS::isWindows()) {
            $this->cli->notice("Updating $destHostsFile - may need root password.");
            $cmd = "sudo cp -f $tmpHostsFile /etc/hosts";
        } else {
            $this->cli->notice("Updating $destHostsFile - may need to be running as administrator.");
            $cmd = "copy /Y \"$tmpHostsFile\" \"$destHostsFile\"";
        }
        exec($cmd, $output, $returnVar);

        if ($returnVar != 0) {
            throw new Exception("Error updating $destHostsFile file");
        }

        $hostsContent = file_get_contents($destHostsFile);
        foreach ($toAdd as $toCheck) {
            if (stripos($hostsContent, $toCheck) === false) {
                throw new Exception("Failed to update $destHostsFile");
            }
        }

        $this->cli->success("Successfully updated $destHostsFile");
    }

    private function parseRecipe(string $recipeFilePath): Recipe {
        $parser = (RecipeParser::instance());
        $recipe = $parser->parse($recipeFilePath);
        $this->cli->success('Recipe successfully parsed.');
        return $recipe;
    }

    private function populateAssets(Recipe $recipe) {
        $assetsPath = $this->getAssetsPath();
        if (!file_exists($assetsPath)) {
            $this->cli->info('Creating docker assets path '.$assetsPath);
            mkdir($assetsPath, 0755, true);
        }

        // Create moodle config asset.
        try {
            $moodleConfigContents = $this->twig->render('@moodle/config.php.twig', (array) $recipe);
        } catch (\Exception $e) {
            throw new Exception('Failed to parse config.php template: '.$e->getMessage());
        }
        file_put_contents($assetsPath.'/config.php', $moodleConfigContents);

        if ($recipe->includeBehat || $recipe->developer) {
            try {
                // Create moodle-browser-config config.
                $browserConfigContents = $this->twig->render('@moodle-browser/config.php.twig', (array) $recipe);
            } catch (\Exception $e) {
                throw new Exception('Failed to parse moodle-browser config.php template: '.$e->getMessage());
            }
        }
        $browserConfigAssetsPath = $assetsPath.'/moodle-browser-config';
        if (!file_exists($browserConfigAssetsPath)) {
            mkdir($browserConfigAssetsPath, 0755, true);
        }
        file_put_contents($browserConfigAssetsPath.'/config.php', $browserConfigContents);

        if ($recipe->includeXdebug || $recipe->developer) {
            try {
                $xdebugContents = $this->twig->render('@docker/install-xdebug.sh.twig', ['mode' => $recipe->xdebugMode ?? 'debug']);
            } catch (\Exception $e) {
                throw new Exception('Failed to parse install-xdebug.sh template: '.$e->getMessage());
            }
        }
        $scriptsAssetsPath = $assetsPath.'/scripts';
        if (!file_exists($scriptsAssetsPath)) {
            mkdir($scriptsAssetsPath, 0755, true);
        }
        file_put_contents($scriptsAssetsPath.'/install-xdebug.sh', $xdebugContents);

    }

    public function getRegisteredUuid(string $chefPath): ?string {
        $path = OS::path($chefPath.'/registry_uuid.txt');
        if (file_exists($path)) {
            return trim(file_get_contents($path));
        }
        return null;
    }

    public function up(string $recipeFilePath): void {
        $recipeFilePath = OS::path($recipeFilePath);
        $this->cli->notice('Cooking up recipe '.$recipeFilePath);
        if (stripos(getcwd(), 'moodle-chef') !== false) {
            throw new Exception('You should not run mchef from within the moodle-chef folder.'.
                "\nYou should instead, create a link to mchef in your bin folder and then run it from a project folder.".
                "\n\nphp mchef.php -i will do this for you. You'll need to open a fresh terminal once it has completed.".
                "\nAt that point you should be able to call mchef.php without prefixing with the php command."
            );
        }
        $recipe = $this->getRecipe($recipeFilePath);

        $directory = OS::path(getcwd() . '/.mchef'); // Get the current working directory and append '.mchef'
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true); // Create the directory with appropriate permissions
        }
        // Define the path for the recipe.json file
        $recipeJsonFilePath = OS::path($directory . '/recipe.json');

        // Check if the recipe.json file exists
        if (!file_exists($recipeJsonFilePath)) {
           // If the file doesn't exist, copy the contents of $recipeFilePath to the new recipe.json file
           copy($recipeFilePath, $recipeJsonFilePath);
        }
        $this->stopContainers($recipe);
        $this->checkPortBinding($recipe) || die();

        if ($recipe->includeBehat) {
            $behatDumpPath = getcwd().'/_behat_dump';
            if (!file_exists($behatDumpPath)) {
                mkdir($behatDumpPath, 0755);
                file_put_contents($behatDumpPath.'/.htaccess', "Options +Indexes\nAllow from All");
            }
        }

        $this->pluginInfo = (Plugins::instance($this->cli))->getPluginsInfoFromRecipe($recipe);
        $volumes = $this->pluginInfo ? $this->pluginInfo->volumes : null;
        if ($volumes) {
            $this->cli->info('Volumes will be created for plugins: '.implode("\n", array_map(function($vol) {return $vol->path;}, $volumes)));
        }

        $dockerData = new DockerData($volumes, null, ...(array) $recipe);
        $dockerData->volumes = $volumes;

        if ($recipe->updateHostHosts) {
            $this->updateHostHosts($recipe);
        }

        try {
            $dockerFileContents = $this->twig->render('@docker/main.dockerfile.twig', (array) $dockerData);
        } catch (\Exception $e) {
            throw new Exception('Failed to parse main.dockerfile template: '.$e->getMessage());
        }

        // Copy docker files and recipe files over to .mchef hidden directory.
        $chefPath = $this->getChefPath();
        $dockerPath =$this->getDockerPath();
        if (!file_exists($dockerPath)) {
            mkdir($dockerPath, 0755, true);
        }

        copy($recipeFilePath, $chefPath.'/recipe.json');

        $regUuid = $this->getRegisteredUuid($chefPath);
        $this->cli->notice('Registering instance in main config');
        Configurator::instance($this->cli)->registerInstance(realPath($recipeFilePath), $regUuid, $recipe->containerPrefix);

        $dockerData->dockerFile = $dockerPath.'/Dockerfile';
        file_put_contents($dockerData->dockerFile, $dockerFileContents);

        $dockerComposeFileContents = $this->twig->render('@docker/main.compose.yml.twig', (array) $dockerData);
        $ymlPath = $dockerPath.'/main.compose.yml';
        file_put_contents($ymlPath, $dockerComposeFileContents);

        $this->populateAssets($recipe);

        // If containers are already running then we need to stop them to re-implement recipe.
        $this->stopContainers();
        $this->startDocker($ymlPath);

        $this->configureDockerNetwork($recipe);
    }

    public function getRecipe(?string $recipeFilePath = null): Recipe {
        if ($this->recipe) {
            return $this->recipe;
        }
        $mchefPath = $this->getChefPath();
        $recipeFilePath = $recipeFilePath ?? $mchefPath.'/recipe.json';
        if (!file_exists($recipeFilePath)) {
            $this->cli->error('Have you run mchef.php [recipefile]? Recipe not present at '.$recipeFilePath);
        }
        $this->recipe = $this->parseRecipe($recipeFilePath);
        return $this->recipe;
    }

    public function getPluginInfo(): PluginsInfo {
        return $this->pluginInfo;
    }

    public function getPluginService() {
        return $this->pluginsService;
    }

    private function getDockerContainerName(string $suffix, ?Recipe $recipe = null, ?string $recipeFilePath = null) {
        $recipe = $recipe ?? $this->recipe;
        if (empty($recipe)) {
            $this->getRecipe($recipeFilePath);
            $recipe = $this->recipe;
        }
        return $recipe->containerPrefix.'-'.$suffix;
    }

    public function getDockerMoodleContainerName(?Recipe $recipe = null) {
        return $this->getDockerContainerName('moodle', $recipe);
    }

    public function getDockerDatabaseContainerName(?Recipe $recipe = null) {
        return $this->getDockerContainerName('db', $recipe);
    }
}
