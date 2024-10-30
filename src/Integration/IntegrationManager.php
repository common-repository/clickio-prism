<?php
/**
 *  Integration manager
 */

namespace Clickio\Integration;

use Clickio\Utils\Plugins;

/**
 * Integration manager
 *
 * @package Integration
 */
final class IntegrationManager
{

    /**
     * Get integration status
     *
     * @return array
     */
    public function getIntegrationList(): array
    {
        $plugins = Plugins::getPlugins();
        $list = [];
        foreach ($plugins as $id => $plugin) {
            $service = $this->getServiceByPluginId($id);
            $struct = [
                "id" => $id,
                "name" => $plugin['Title'],
                "service" => $service,
                "integration" => !empty($service) && Plugins::pluginIsActive($id)? $service::integration() : false,
                "author" => $plugin['AuthorName'],
                "version" => $plugin['Version'],
                "author_url" => $plugin['AuthorURI'],
                "status" => Plugins::pluginIsActive($id)
            ];
            $list[] = $struct;
        }
        return $list;
    }

    /**
     * Get integration service for plugin
     *
     * @param string $id plugin id
     *
     * @return string
     */
    public function getServiceByPluginId(string $id): string
    {
        $services = IntegrationServiceFactory::getServices();
        $serv = array_filter(
            $services,
            function ($item) use ($id) {
                return $item::canIntegrateWith($id);
            }
        );

        if (empty($serv)) {
            $serv = [''];
        }
        return array_pop($serv);
    }
}