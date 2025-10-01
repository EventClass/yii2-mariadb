<?php

declare(strict_types=1);


namespace SamIT\Yii2\MariaDb;

use yii\base\InvalidConfigException;
use yii\db\Connection;

/**
 * Class ColumnSchemaBuilder
 * @package SamIT\Yii2\MariaDb
 */
class ColumnSchemaBuilder extends \yii\db\mysql\ColumnSchemaBuilder
{
    /**
     * @var string pattern that is used for the check-clause
     *             token `{name}` will be replaced with the name of the column
     */
    public string $checkPattern = "json_valid([[{name}]])";

    /**
     * @var bool whether JSON-Columns should be always created as type `text` with `JSON_VALID()` - check
     *           This can be used that column-type matches between mysql and mariadb
     *           (Otherwise comparing dumps of both db-types would show different types)
     *           Default to `false`, meaning nativ json-type is used (which could be auto-converted in mariadb).
     */
    public $forceJsonToTextWithJsonCheck = false;

    public function __construct(string $type, $length = null, ?Connection $db = null, array $config = [])
    {
        parent::__construct($type, $length, $db, $config);
        if ($this->isJson()) {
            $this->check($this->checkPattern);
        }
    }

    public function isJson(): bool
    {
        return $this->type === \yii\db\Schema::TYPE_JSON;
    }

    public function toString(string $columnName)
    {
        switch ($this->getTypeCategory()) {
            case self::CATEGORY_PK:
                $format = '{type}{length}{comment}{append}{check}{pos}';
                break;
            case self::CATEGORY_NUMERIC:
                $format = '{type}{length}{unsigned}{notnull}{default}{unique}{comment}{append}{check}{pos}';
                break;
            default:
                $format = '{type}{length}{notnull}{default}{unique}{comment}{append}{check}{pos}';
        }

        // Overwrite json to text, to make db-dumps comparable between mysql and mariadb
        if ($this->forceJsonToTextWithJsonCheck && $this->isJson()) {
            $format = \strtr($format, ['{type}' => \yii\db\Schema::TYPE_TEXT . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_bin']);
        }
        return \strtr($this->buildCompleteString($format), ['{name}' => $columnName]);
    }
}
