<?php

namespace App\Service;

class PHPVersions extends AbstractService {
    final public static function instance(): PHPVersions {
        return self::get_instance();
    }

    public function listVersions(): array {
        $repoUrl = "https://api.github.com/repos/moodlehq/moodle-php-apache/branches";

        // Set up a CURL session to retrieve the branch information from GitHub
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $repoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'User-Agent: PHP'
        ));
        $response = curl_exec($ch);

        // Parse the JSON response into a PHP array
        $branches = json_decode($response, true);

        // Loop through the branches and extract the PHP version from the branch name
        $phpVersions = [];
        foreach ($branches as $branch) {
            $branchName = $branch['name'];
            preg_match('/(\d+\.\d+)(?:-)/', $branchName, $matches);
            if (!empty($matches[1])) {
                $phpVersion = $matches[1];
                $phpVersions[] = $phpVersion;
            }
        }

        return array_unique($phpVersions);
    }
}