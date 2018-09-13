<?php
namespace Arillo\MultiDB;

use SilverStripe\View\ArrayData;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBDatetime;

use Medoo\Medoo;

/**
 * Proxy for a DataObject that is represented in a alternative database.
 *
 * @package Arillo
 * @subpackage MultiDB
 * @author <bumbus sf@arillo.net>
 */
class DataObjectProxy extends ArrayData
{
    const FIELD_ID = 'ID';
    const FIELD_CREATED = 'Created';
    const FIELD_LASTEDITED = 'LastEdited';

    const PRIMARY_KEY = self::FIELD_ID;

    /**
     * database connection
     * @var Medoo
     */
    protected static $db_conn = null;

    /**
     * data object proxy instance (self)
     * @var DataObjectProxy
     */
    protected static $inst = null;

    /**
     * singleton of the proxied DataObject
     * @var DataObject
     */
    protected static $proxied = null;

    /**
     * Dataobject class we want to proxy.
     * @var string
     */
    private static $dataobject_class = null;

    /**
     * Medoo configurations (@see https://medoo.in/api/new)
     * @var array
     */
    private static $db_config = null;

    /**
     * Instance of DataObjectProxy subclass
     * @return DataObjectProxy
     */
    public static function inst()
    {
        if (self::$inst) return self::$inst;

        self::$inst = singleton(get_called_class());
        return self::$inst;
    }

    /**
     * db fields including 'ID', 'Created', 'LastEdited'
     * @return array
     */
    public static function all_fields()
    {
        $fields = array_keys(self::fields());
        array_push(
            $fields,
            self::FIELD_ID,
            self::FIELD_CREATED,
            self::FIELD_LASTEDITED
        );

        return $fields;
    }

    /**
     * DB fields from proxied DO
     * @return array
     */
    public static function fields()
    {
        return self::proxied()->config()->db;
    }

    /**
     * Instance of proxied DataObject
     * @return DataObject
     */
    public static function proxied()
    {
        if (self::$proxied) return self::$proxied;

        if (empty(static::inst()->config()->dataobject_class))
        {
            user_error(get_called_class() . " is missing dataobject_class setting.");
        }

        self::$proxied = singleton(static::inst()->config()->dataobject_class);
        return self::$proxied;
    }

    /**
     * Database connection.
     * @return Medoo
     */
    public static function db_conn()
    {
        if (self::$db_conn) return self::$db_conn;

        self::$db_conn = new Medoo(static::db_config());

        return self::$db_conn;
    }

    /**
     * Database config derived from Config system, can be overidden in subclass.
     * @return array
     */
    public static function db_config()
    {
        return static::inst()->config()->db_config;
    }

    public static function table_name()
    {
        return self::proxied()->config()->table_name;
    }

    /**
     * Get all db records
     * @return ProxyDataList
     */
    public static function get()
    {
        return ProxyDataList::create(get_called_class());
    }

    /**
     * @param  array  $data
     * @return DataOjectProxy
     */
    public function update(array $data)
    {
        foreach ($data as $field => $value)
        {
            if ($this->exists() && $field === self::PRIMARY_KEY) continue;
            $this->setField($field, $value);
        }

        return $this;
    }

    /**
     * Upsert this record
     * @return DataObjectProxy
     */
    public function write()
    {
        $data = $this->toMap();
        $fields = array_keys(self::fields());
        $preparedData = [];
        $now = DBDatetime::now()->format(DBDatetime::ISO_DATETIME);
        $id = null;

        foreach ($data as $field => $value)
        {
            if (!in_array($field, $fields)) continue;

            $preparedData[$field] = $value;
        }

        $preparedData[self::FIELD_LASTEDITED] = $now;

        switch (true)
        {
            case isset($data[self::PRIMARY_KEY]):
                self::db_conn()->update(
                    static::table_name(),
                    $preparedData,
                    [self::PRIMARY_KEY => $data[self::PRIMARY_KEY]]
                );

                $id = $data[self::PRIMARY_KEY];
                break;

            default:
                $preparedData[self::FIELD_CREATED] = $now;
                self::db_conn()->insert(
                    static::table_name(),
                    $preparedData
                );

                $id = self::db_conn()->id();
                break;
        }

        if ($id) {
            $record = self::db_conn()->get(
                static::table_name(),
                self::all_fields(),
                [ self::PRIMARY_KEY => $id ]
            );

            $this->update($record);
        }

        return $this;
    }

    public function delete()
    {
        if (empty($this->array[self::PRIMARY_KEY])) return;

        self::db_conn()->delete(
            static::table_name(),
            [ self::PRIMARY_KEY => $this->array[self::PRIMARY_KEY] ]
        );
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return (isset($this->array[self::PRIMARY_KEY]) && $this->array[self::PRIMARY_KEY] > 0);
    }

    /**
     * Shadow i18n_plural_name
     * @return string
     */
    public function i18n_plural_name()
    {
        return self::proxied()->i18n_plural_name();
    }

    /**
     * Shadow getDefaultSearchContext
     */
    public function getDefaultSearchContext()
    {
        return self::proxied()->getDefaultSearchContext();
    }

    /**
     * Shadow summaryFields
     */
    public function summaryFields()
    {
        return self::proxied()->summaryFields();
    }

    public function canView($member = null)
    {
        return self::proxied()->canView($member);
    }
}
