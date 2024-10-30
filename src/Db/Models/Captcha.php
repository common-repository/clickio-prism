<?php

/**
 * Captcha model
 */

namespace Clickio\Db\Models;

use Clickio\Db\AbstractModel;
use Clickio\Db\Interfaces\IModel;

/**
 * Captcha model
 *
 * @package Db\Models
 */
class Captcha extends AbstractModel implements IModel
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'clickio_captcha';

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
            $dt = current_time("Y-m-d H:i:s");
            $old_data_q = "SELECT
                                id,
                                captcha_hash,
                                captcha_val,
                                create_date
                            FROM $tbl
                            WHERE TIME_TO_SEC(TIMEDIFF(\"$dt\", create_date)) < 1200";
            $old_data = $this->db->get_results($old_data_q, \ARRAY_A);
        } else {
            $old_data = [];
        }

        $drop_table = "DROP TABLE IF EXISTS $tbl";
        $create_table = "CREATE TABLE $tbl(
            id int(10) PRIMARY KEY AUTO_INCREMENT,
            captcha_hash varchar(255),
            captcha_val varchar(255),
            create_date datetime,
            KEY(captcha_hash)
        )";

        $this->db->query($drop_table);
        $this->db->query($create_table);
        foreach ($old_data as $data) {
            $this->db->insert($tbl, $data);
        }
    }

    /**
     * Create captcha record
     *
     * @param string $captcha captch text
     * @param string $sign captcha hash
     *
     * @return bool
     */
    public function insert(string $captcha, string $sign): bool
    {
        $table = $this->getTableName();
        $data = [
            "captcha_hash" => $sign,
            "captcha_val" => $captcha,
            "create_date" => current_time("Y-m-d H:i:s")
        ];

        $result = $this->db->insert($table, $data);
        return !empty($result);
    }

    /**
     * Select captcha
     *
     * @param string $field 'where' field
     * @param string $val 'where' value
     *
     * @return array
     */
    public function selectRow(string $field, string $val): array
    {
        $table = $this->getTableName();
        $select_q = "SELECT * FROM $table WHERE $field = %s";
        $prepared = $this->db->prepare($select_q, [$val]);
        $row = $this->db->get_row($prepared, \ARRAY_A);

        if (empty($row)) {
            $row = [];
        }
        return $row;
    }

    /**
     * Delete captcha row
     *
     * @param string $field 'where' field
     * @param string $val 'where' value
     *
     * @return void
     */
    public function deleteRow(string $field, string $val)
    {
        $table = $this->getTableName();
        $this->db->delete($table, [$field => $val]);
    }

    /**
     * Cleaning up unsolved chalenges
     *
     * @return void
     */
    public function cleanUpUnsolved()
    {
        $table = $this->getTableName();
        $dt = current_time("Y-m-d H:i:s");
        $q="DELETE FROM $table WHERE TIME_TO_SEC(TIMEDIFF(\"$dt\", create_date)) > 1200";
        $this->db->query($q);
    }
}
