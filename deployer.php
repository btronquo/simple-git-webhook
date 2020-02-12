<?php


#
# ===========================SIMPLE WEBHOOK ========================================
# Purpose:            >_I mixed some code from internet to provide a simple and reusable git webhook script
#                     >_The main goal is to have only one entry point to syncronise many differents repositories
# URL Parameters:     `name` ==> (example: https://gitwebhook.domain.com?name=myrepository)
# Called From:        gitwebhook in github repository settings OR you can use gitlab
# Author:             Boris TRONQUOY
# Notes:              Additional information on README.md
# Prerequisites:      - Git command line available
#                     - Key ssh set on the repository settings
#                     - The repository is cloned and the branch name is set correctly
# Revision:           Last changes:
#                       10/02/2020 - Initial Commit
# ?HELP?              Open a ticket on the source repository
# =================================================================================
#

// default config
define("LOGFILE", "deploy.log");
define("GIT", "/usr/bin/git");
define("MAX_EXECUTION_TIME", 180);
define("TOKEN", getenv('REDIRECT_WEBHOOK_KEY'));

$content   = file_get_contents("php://input");
$json      = json_decode($content, true);
$file      = fopen(LOGFILE, "a");
$time      = time();
$token     = false;
$sha       = false;
$repoName  = htmlspecialchars($_GET["name"]);

/**
 * enter the root path of your web server example: $rootPath = "/home/clients/51b8dd0e2ba128921bcdd3f0f45eb9f09e" or "/"
 * you can use the command `cd && pwd` on your server to know the root path
 */
$rootPath = "/home/clients/51b80e2ba128921bcdd3f0f45eb9f09e";

/**
 * I asume you have a workflow with 2 branch: master & develop
 * you can change as you need the names of the folders
 */
$pathPreproduction    = $rootPath . '/preproduction/preproduction.' . $repoName;
$pathProduction       = $rootPath . '/production/production.' . $repoName;


// retrieve the token
if (!$token && isset($_SERVER["HTTP_X_HUB_SIGNATURE"])) {
    list($algo, $token) = explode("=", $_SERVER["HTTP_X_HUB_SIGNATURE"], 2) + array("", "");
} elseif (isset($_SERVER["HTTP_X_GITLAB_TOKEN"])) {
    $token = $_SERVER["HTTP_X_GITLAB_TOKEN"];
} elseif (isset($_GET["token"])) {
    $token = $_GET["token"];
}


// write the time to the log
date_default_timezone_set("UTC");
fputs($file, date("d-m-Y (H:i:s)", $time) . "\n");
// specify that the response does not contain HTML
header("Content-Type: text/plain");
// use user-defined max_execution_time
if (!empty(MAX_EXECUTION_TIME)) {
    ini_set("max_execution_time", MAX_EXECUTION_TIME);
}

function forbid($file, $reason) {
    $error = "=== ERROR: " . $reason . " ===\n*** ACCESS DENIED ***\n";
    http_response_code(403);
    echo $error;
    exit;
}
// Check for a GitHub signature
if (!empty(TOKEN) && isset($_SERVER["HTTP_X_HUB_SIGNATURE"]) && $token !== hash_hmac($algo, $content, TOKEN)) {
    forbid($file, "X-Hub-Signature does not match TOKEN");

// Check for a GitLab token
} elseif (!empty(TOKEN) && isset($_SERVER["HTTP_X_GITLAB_TOKEN"]) && $token !== TOKEN) {
    forbid($file, "X-GitLab-Token does not match TOKEN");

// Check for a $_GET token
} elseif (!empty(TOKEN) && isset($_GET["token"]) && $token !== TOKEN) {
    forbid($file, "\$_GET[\"token\"] does not match TOKEN");

// if none of the above match, but a token exists, exit
} elseif (!empty(TOKEN) && !isset($_SERVER["HTTP_X_HUB_SIGNATURE"]) && !isset($_SERVER["HTTP_X_GITLAB_TOKEN"]) && !isset($_GET["token"])) {
    forbid($file, "No token detected");

} elseif (!$token){
    forbid($file, "No token detected");
} else {

    // check if pushed branch matches branch specified in config
    if ($json["ref"] === "refs/heads/develop") {

        fputs($file, $content . PHP_EOL);
        chdir($pathPreproduction);
        fputs($file, "*** WEBHOOK - (DEVELOP) INIT FOR: " . $pathPreproduction . "\n");
        exec(GIT . " pull 2>&1", $output, $exit);

        $output = (!empty($output) ? implode("\n", $output) : "[no output]") . "\n";

        if ($exit !== 0) {
            http_response_code(500);
            $output = "=== ERROR: FAILLED TO PULL `" . GIT . "` FOR `" . $pathPreproduction . "` ===\n" . $output;
        }
        // uncomment here if you want to have the full output in you log file
        //fputs($file, $output);
        echo $output;

        fputs($file, "*** WEBHOOK - FINISHED ***" . "\n");

    } elseif ($json["ref"] === "refs/heads/master") {

        fputs($file, $content . PHP_EOL);
        chdir($pathProduction);

        fputs($file, "*** WEBHOOK - (MASTER) INIT FOR: " . $pathProduction . "\n");

        exec(GIT . " pull 2>&1", $output, $exit);

        $output = (!empty($output) ? implode("\n", $output) : "[no output]") . "\n";

        if ($exit !== 0) {
            http_response_code(500);
            $output = "=== ERROR: PULL FAILLED `" . GIT . "` for the directory `" . $pathProduction . "` ===\n" . $output;
        }
        fputs($file, $output);
        echo $output;

        fputs($file, "*** WEBHOOK - FINISHED ***" . "\n");

    } else {
        $error = "=== ERROR: BRANCH `" . $json["ref"] . "`";
        http_response_code(400);
        fputs($file, $error);
        echo $error;
    }
}


// fermer le log
fputs($file, "\n\n" . PHP_EOL);
fclose($file);