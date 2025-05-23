<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

abstract class MPAbstractDB
{
    protected $table;
    protected $where;
    protected $columns;
    protected $orderBy;
    protected $andWhere;

    public function __construct()
    {
        $this->columns = "*";
    }

    /**
     * Execute query for database without return
     *
     * @param  string $query
     * @return bool
     */
    public function executeQuery($query)
    {
        if (Db::getInstance()->execute($query) == false) {
            MPLog::generate('Failed to execute query: ' . Db::getInstance()->getMsgError(), 'error');
            return false;
        }
        return true;
    }

    /**
     * Execute query for database returning one row
     *
     * @param  string $query
     * @return array|bool|object|null
     */
    public function selectQuery($query)
    {
        $sql = Db::getInstance()->getRow($query);
        return $sql;
    }

    /**
     * Execute query for database returning many rows
     *
     * @param  string $query
     * @return array|bool|object|null
     */
    public function selectMany($query)
    {
        $sql = Db::getInstance()->executeS($query);
        return $sql;
    }

    /**
     * Get method
     */
    public function get()
    {
        $query = "SELECT $this->columns FROM $this->table $this->where $this->orderBy";
        $result = $this->selectQuery($query);
        return $result;
    }

    /**
     * Get all method
     */
    public function getAll()
    {
        $query = "SELECT $this->columns FROM $this->table $this->where $this->orderBy";
        $result = $this->selectMany($query);
        return $result;
    }

    /**
     * Count method, needs where() method
     *
     * @return mixed
     */
    public function count()
    {
        $query = "SELECT COUNT(*) AS count FROM $this->table $this->where $this->andWhere";
        $result = $this->selectQuery($query);
        return $result['count'];
    }

    /**
     * Set columns method, needs be called with select()
     *
     * @param  array $columns
     * @return MPAbstractDB
     */
    public function columns($columns)
    {
        if (gettype($columns) == "array") {
            $this->columns = implode(",", array_map('bqSQL', $columns));
        }

        return $this;
    }

    /**
     * Where method, needs be called with count() or get()
     *
     * @param  string $column
     * @param  string $operator
     * @param  mixed  $value
     * @return MPAbstractDB
     */
    public function where($column, $operator, $value)
    {
        $this->where = 'WHERE ' . bqSQL($column) . ' ' . $operator . ' "' . pSQL($value) . '"';
        return $this;
    }

    /**
     * And where method, needs be called with count() or get()
     *
     * @param  string $column
     * @param  string $operator
     * @param  mixed  $value
     * @return MPAbstractDB
     */
    public function andWhere($column, $operator, $value)
    {
        $this->andWhere = 'AND ' . bqSQL($column) . ' ' . $operator . ' "' . pSQL($value) . '"';
        return $this;
    }

    /**
     * orderBy method, needs be called with get()
     *
     * @param  string $column
     * @param  string $operator
     * @return MPAbstractDB
     */
    public function orderBy($column, $operator = 'desc')
    {
        if (!in_array(Tools::strtolower($operator), ['desc', 'asc'])) {
            $operator = 'desc';
        }
        $this->orderBy = 'ORDER BY ' . bqSQL($column) . ' ' . $operator;
        return $this;
    }

    /**
     * Insert data in database
     *
     * @param  array $array
     * @return bool|void
     */
    public function create($array)
    {
        if (gettype($array) == "array") {
            $attrs  = "";
            $params = "";

            foreach ($array as $attr => $param) {
                $attrs  .= bqSQL($attr) . ",";
                $params .= "'" . pSQL($param) . "',";
            }

            $attrs .= "created_at";
            $params .= "'" . date("Y-m-d H:i:s") . "'";

            $query = "INSERT INTO $this->table ($attrs) VALUES ($params)";
            $result = $this->executeQuery($query);

            return $result;
        }

        return false;
    }

    /**
     * Update data in database
     *
     * @param  array $array
     * @return bool|void
     */
    public function update($array)
    {
        if (gettype($array) == "array") {
            $update = "";

            foreach ($array as $attr => $param) {
                $update .= bqSQL($attr) . " = '" . pSQL($param) . "',";
            }

            $update .= "updated_at = '" . date("Y-m-d H:i:s") . "'";
            $query  = "UPDATE $this->table SET $update $this->where";
            $result = $this->executeQuery($query);

            return $result;
        }

        return false;
    }

    /**
     * Delete data from database
     *
     * @return bool|void
     */
    public function destroy()
    {
        $query = "DELETE FROM $this->table $this->where";
        $result = $this->executeQuery($query);
        return $result;
    }
}
