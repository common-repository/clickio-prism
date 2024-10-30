<?php

/**
 * PageWidgets model
 */

namespace Clickio\Db\Models;

use Clickio\Db\AbstractModel;
use Clickio\Db\Interfaces\IModel;

/**
 * PageWidgets model
 *
 * @package Db\Models
 */
class PageWidgets extends AbstractModel implements IModel
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'clickio_pagewidgets';

    /**
     * Upgrade table
     *
     * @return void
     */
    public function upgrade()
    {
        $tbl = $this->getTableName();
        $q = "SHOW TABLES LIKE '$tbl'";
        $exists = $this->db->get_row($q);
        if (!empty($exists)) {
            $this->_alterTable();
        } else {
            $this->_createTable();
        }
    }

    /**
     * Create table
     *
     * @return void
     */
    private function _createTable()
    {
        $tbl = $this->getTableName();
        $q = "CREATE TABLE $tbl(
            widget_list varchar(2048) null,
            term_id varchar(255) not null default '',
            post_type varchar(255) not null default '',
            location_type varchar(255) not null default '',
            KEY(location_type, post_type),
            PRIMARY KEY(location_type, term_id, post_type)
        ) ENGINE = MEMORY";
        $this->db->query($q);
    }

    /**
     * Alter table
     *
     * @return void
     */
    private function _alterTable()
    {
        $tbl = $this->getTableName();
        $q = "DROP TABLE IF EXISTS $tbl";
        $this->db->query($q);
        $this->_createTable();
    }

    /**
     * Create widgets record
     *
     * @param string $wl widgets
     * @param string $term taxonomy term id
     * @param string $post_type post type
     * @param string $location post/page/home etc.
     *
     * @return bool
     */
    public function replace(string $wl, string $term = null, string $post_type =  null, string $location = null): bool
    {
        $table = $this->getTableName();

        if (!$this->tableExists()) {
            $this->_createTable();
        }

        $data = [
            "widget_list" => $wl,
            "term_id" => $term,
            "post_type" => $post_type,
            "location_type" => $location
        ];

        $result = $this->db->replace($table, $data);
        return !empty($result);
    }

    /**
     * Select widgets
     *
     * @param string $where 'where' condition
     *
     * @return array
     */
    public function selectRow(array $where): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $table = $this->getTableName();
        $fields = array_map(
            function ($field) {
                return sprintf("%s = %%s", $field);
            },
            array_keys($where)
        );

        $select_q = "SELECT * FROM $table WHERE ".implode(" AND ", $fields);
        $prepared = $this->db->prepare($select_q, array_values($where));
        $row = $this->db->get_row($prepared, \ARRAY_A);

        if (empty($row)) {
            $row = [];
        }
        return $row;
    }

    /**
     * Delete widgets row
     *
     * @param string $field 'where' field
     * @param string $val 'where' value
     *
     * @return void
     */
    public function deleteRow(string $field, string $val)
    {
        if (!$this->tableExists()) {
            return ;
        }
        $table = $this->getTableName();
        $this->db->delete($table, [$field => $val]);
    }

    /**
     * Create table if it doesn't exists
     *
     * @return void
     */
    protected function tableExists(): bool
    {
        $table = $this->getTableName();
        $q = "SHOW TABLES LIKE '$table'";
        $exists = $this->db->get_row($q);
        return !empty($exists);
    }
}