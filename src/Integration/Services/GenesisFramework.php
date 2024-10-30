<?php
/**
 * Genesis Integration service
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Genesis integration
 *
 * @package Integration\Services
 */
final class GenesisFramework extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'genesis';

    /**
     * Tets integration is available
     *
     * @return bool
     */
    public static function integration(): bool
    {
        return defined('PARENT_THEME_NAME') && \PARENT_THEME_NAME == 'Genesis';
    }

    /**
     * Get list of plugins with which the service can integrate
     *
     * @return array
     */
    protected static function getIntegrationList(): array
    {
        return [];
    }


    /**
     * Get term meta by field
     *
     * @param string $taxonomy term field
     * @param int $term_id term id
     *
     * @return string
     */
    public static function getTermMeta(string $taxonomy, int $term_id): string
    {
        if (!static::integration()) {
            return '';
        }

        switch ($taxonomy) {
            case 'description':
                return static::getTermDescription($term_id);
        }
        return '';
    }

    /**
     * Get term description
     *
     * @param int $term_id term id
     *
     * @return string
     */
    public static function getTermDescription(int $term_id): string
    {
        $term = '';

        if (!static::integration()) {
            return $term;
        }

        try {
            $term = get_term_meta($term_id, 'intro_text', true);
            $term = trim(str_replace('&nbsp;', '', $term));
        } catch (\Exception $e) {
            //do nothing
        }

        return $term;
    }

    /**
     * Get term meta by field
     *
     * @param string $field user field
     * @param int $uid user id
     *
     * @return string
     */
    public static function getUserMeta(string $field, int $uid): string
    {
        if (!static::integration()) {
            return '';
        }

        switch ($field) {
            case 'description':
                return static::getUserDescription($uid);
        }
        return '';
    }

    /**
     * Get term description
     *
     * @param int $id user id
     *
     * @return string
     */
    public static function getUserDescription(int $id): string
    {
        $desc = '';

        if (!static::integration()) {
            return $desc;
        }

        try {
            $desc = get_user_meta($id, 'intro_text', true);
            $desc = trim(str_replace('&nbsp;', ' ', $desc));
        } catch (\Exception $e){
            // do nothing
        }
        return $desc;
    }
}
