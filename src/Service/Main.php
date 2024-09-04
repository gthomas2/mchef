<?php

namespace App\Service;

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
        $chefPath =  File::instance()->findFileInOrAboveDir('.mchef');
        if ($failOnNotFound && !is_dir($chefPath)) {
            $this->cli->alert('Your current working directory, or the directories above it, do not contain a .mchef directory');
            die;
        }
        return $chefPath;
    }

    public function getDockerPath() {
        return $this->getChefPath().'/docker';
    }

    public function getAssetsPath() {
        return $this->getDockerPath().'/assets';
    }

    private function startDocker($ymlPath) {
        $this->cli->notice('Starting docker containers');
        // @Todo - force-recreate and --build need to be flags that get passed in, not hard coded.
        $cmd = "docker compose -f $ymlPath up -d --force-recreate --build";
        $this->execPassthru($cmd, "Error starting docker containers - try pruning with 'docker builder prune' OR 'docker system prune' (note 'docker system prune' will destroy all non running container images)");

        // @Todo - Add code here to check docker ps for expected running containers.
        // For example, if one of the Apache virtual hosts has an error in it, it will bomb out.
        // So we need to spin here for about 10 seconds checking that the containers are running.

        $this->cli->success('Docker containers have successfully been started');
    }

    public function startContainers(): void {
        $this->cli->notice('Starting containers');
        $dockerService = Docker::instance($this->cli);
        $recipe = $this->getRecipe();
        $moodleContainer = $this->getDockerMoodleContainerName();
        $dockerService->startDockerContainer($moodleContainer);
        $dbContainer = $this->getDockerDatabaseContainerName();
        $dockerService->startDockerContainer($dbContainer);


        $this->cli->success('All containers have been started');
    }

    public function stopContainers(): void {
        $this->cli->notice('Stopping containers');
        $recipe = $this->getRecipe();
        $moodleContainer = $this->getDockerMoodleContainerName();
        $dbContainer = $this->getDockerDatabaseContainerName();
        $behatContainer = $recipe->containerPrefix.'-behat';
        $toStop = [
            $moodleContainer,
            $dbContainer
        ];

        $dockerService = Docker::instance($this->cli);
        $containers = $dockerService->getDockerContainers(false);
        foreach ($containers as $container) {
            $name = $container->names;
            $this->cli->notice('Stopping container: '.$name);
            if (in_array($name, $toStop) || strpos($behatContainer, $name) === 0) {
                $dockerService->stopDockerContainer($name);
            }
        }

        $this->cli->success('All containers have been stopped');
    }

    private function configureDockerNetwork(Recipe $recipe): void {
        $dockerService = Docker::instance($this->cli);
        //$networkName = $recipe->containerPrefix.'-network';
        // TODO - default should be mc-network unless defined in recipe.
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
    }

    private function checkPortBinding(Recipe $recipe): bool {
      $dockerService = Docker::instance($this->cli);
      return $dockerService->checkPortAvailable($recipe->port);
    }
    
    private function updateHostHosts(Recipe $recipe): void {
        if ($recipe->updateHostHosts) {
            try {
                $hosts = file('/etc/hosts');
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
            $this->cli->info('No hosts to add to host /etc/hosts file');
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

        $this->cli->notice('Updating /etc/hosts - may need root password.');
        $cmd = "sudo cp -f $tmpHostsFile /etc/hosts";
        exec($cmd, $output, $returnVar);

        if ($returnVar != 0) {
            throw new Exception("Error updating /etc/hosts file");
        }

        $hostsContent = file_get_contents('/etc/hosts');
        foreach ($toAdd as $toCheck) {
            if (stripos($hostsContent, $toCheck) === false) {
                throw new Exception('Failed to update /etc/hosts');
            }
        }

        $this->cli->success('Successfully updated /etc/hosts');
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

    public function create(string $recipeFilePath) {
        $this->cli->notice('Cooking up recipe '.$recipeFilePath);
        if (stripos(getcwd(), 'moodle-chef') !== false) {
            throw new Exception('You should not run mchef from within the moodle-chef folder.'.
                "\nYou should instead, create a link to mchef in your bin folder and then run it from a project folder.".
                "\n\nphp mchef.php -i will do this for you. You'll need to open a fresh terminal once it has completed.".
                "\nAt that point you should be able to call mchef.php without prefixing with the php command."
            );
        }
        $recipe = $this->parseRecipe($recipeFilePath);
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

        $dockerData->dockerFile = $dockerPath.'/Dockerfile';
        file_put_contents($dockerData->dockerFile, $dockerFileContents);

        $dockerComposeFileContents = $this->twig->render('@docker/main.compose.yml.twig', (array) $dockerData);
        $ymlPath = $dockerPath.'/main.compose.yml';
        file_put_contents($ymlPath, $dockerComposeFileContents);

        $this->populateAssets($recipe);

        $this->startDocker($ymlPath);

        $this->configureDockerNetwork($recipe);
    }

    public function getRecipe(): Recipe {
        if ($this->recipe) {
            return $this->recipe;
        }
        $mchefPath = $this->getChefPath();
        $recipeFilePath = $mchefPath.'/recipe.json';
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

    private function getDockerContainerName(string $suffix) {
        if (empty($this->recipe)) {
            $this->getRecipe();
        }
        return $this->recipe->containerPrefix.'-'.$suffix;
    }

    public function getDockerMoodleContainerName() {
        return $this->getDockerContainerName('moodle');
    }

    public function getDockerDatabaseContainerName() {
        return $this->getDockerContainerName('db');
    }
}
