<?php

/**
 * Disable plugin
 */

if (is_array($_GET) && !empty($_GET) && array_key_exists("token", $_GET) && $_GET["token"] == "ag6Iey0oquiaQuu") {
    if (isset($_GET['enable'])) {
        rename("clickioprism.php.backup", "clickioprism.php");
    } else {
        rename("clickioprism.php", "clickioprism.php.backup");
        unlink('../../advanced-cache.php');
    }
}
