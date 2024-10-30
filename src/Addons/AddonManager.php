<?php

/**
 * Addon manager
 */

namespace Clickio\Addons;

use Clickio\Logger\LoggerAccess;
use Clickio\Options;
use Clickio\Request\Request;
use Clickio\Utils\FileSystem;
use Clickio\Utils\SafeAccess;
use Exception;

/**
 * Addon manager
 *
 * @package Addons
 */
final class AddonManager
{
    use LoggerAccess;

    /**
     * Addons store
     *
     * @var string
     */
    const ENDPOINT = "https://platform.clickio.com/PublicRestApi/downloadAddon";

    /**
     * Install addon
     *
     * @param string $name addon name
     *
     * @return void
     */
    public function install(string $name)
    {
        if ($this->addonAlreadyInstalled($name)) {
            throw new \Exception("Already installed");
        }

        $request = Request::create(['signed' => false]);
        $domain = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'default');
        try {
            $resp = $request->get(static::ENDPOINT, ["name" => $name, "domain" => $domain]);
        } catch (Exception $err) {
            $msg = sprintf("Trying to install Clickio addon - %s: %s", $name, $err->getMessage());
            static::logError($msg);
            return ;
        }
        $body = trim($resp->body['addon']);
        $cls = trim($resp->body['name']);
        if (empty($cls) || empty($body)) {
            throw new Exception("Class or body is empty");
        }
        $addon = base64_decode($body);
        $dir = wp_upload_dir()['basedir'];
        $upload_dir = sprintf("%s/clickio", $dir);
        $file_name = sprintf("%s/%s.php", $upload_dir, explode("\\", $cls)[1]);
        if (is_writeable($dir) && FileSystem::makeDir($upload_dir)) {
            @file_put_contents($file_name, $addon);
            $qa_result = $this->testAddon($file_name, $cls);
            if (empty($qa_result)) {
                $addons = Options::get("addons");
                $addons[] = $cls;
                Options::set("addons", array_unique($addons));
                Options::save();
                static::logInfo("Addon $cls successfully installed");
            } else {
                $msg = sprintf("Test fails after installation: %s", $qa_result);
                static::logError($msg);
            }
        } else {
            $msg = sprintf("Unable to save addon: %s", $file_name);
            static::logError($msg);
        }
    }

    /**
     * Make sure the addon is safe
     *
     * @param string $addon full file path to file
     * @param string $cls class name
     *
     * @return string
     */
    protected function testAddon(string $addon, string $cls): string
    {
        $out = [];
        exec("php -l $addon", $out, $return_code);
        if (!empty($return_code)) {
            return implode("\n", $out);
        }

        if (is_readable($addon)) {
            include $addon;
            $addon_obj = new $cls;
            try {
                $addon_obj->run();
            } catch (Exception $err) {
                return $err->getMessage();
            }
        } else {
            return "Addon isn't readable";
        }
        return "";
    }

    /**
     * Load addons
     *
     * @return void
     */
    public function loadAddons()
    {
        $active = Options::get('addons');
        if (empty($active)) {
            return ;
        }

        foreach ($active as $addon) {
            try {
                $addon_obj = AddonFactory::createAddon($addon, [static::$log]);
                $addon_obj->run();
            } catch (Exception $err) {
                $this->logError($err->getMessage());
                continue ;
            }
        }
    }

    /**
     * Uninstall addon
     *
     * @param string $name addon name
     *
     * @return void
     */
    public function uninstall(string $name)
    {
        $addons = Options::get('addons');
        $addon_info = AddonFactory::getAddonInfo($name);
        if (!empty($addon_info)) {
            $file = $addon_info['file'];
            $cls = $addon_info['class'];
            @unlink($file);
            $addons = array_filter(
                $addons,
                function ($el) use ($cls) {
                    if ($el == $cls) {
                        return false;
                    }
                    return true;
                }
            );
            Options::set('addons', $addons);
            Options::save();
        }
    }

    /**
     * List addons
     *
     * @return array
     */
    public function listAddons(): array
    {
        return AddonFactory::getAddons();
    }

    /**
     * Check for addon already installed
     *
     * @param string $name addon name
     *
     * @return bool
     */
    public function addonAlreadyInstalled(string $name): bool
    {
        foreach ($this->listAddons() as $addon) {
            if ($addon['name'] == $name) {
                return true;
            }
        }
        return false;
    }
}
