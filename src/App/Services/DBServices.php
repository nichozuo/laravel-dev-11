<?php

namespace LaravelDev\App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Models\Database\DBModel;
use LaravelDev\App\Models\Database\DBTableColumnModel;
use LaravelDev\App\Models\Database\DBTableModel;

class DBServices
{
    const PROPERTY_TYPES = [
        'tinyint' => 'integer',
        'smallint' => 'integer',
        'int' => 'integer',
        'bigint' => 'integer',
        'varchar' => 'string',
        'text' => 'string',
        'mediumtext' => 'string',
        'longtext' => 'string',
        'enum' => 'string',
        'date' => 'string',
        'datetime' => 'string',
        'decimal' => 'float',
        'double' => 'float',
        'json' => 'array',
        'timestamp' => 'mixed',
    ];

    const VALIDATE_TYPES = [
        'tinyint' => 'integer',
        'smallint' => 'integer',
        'int' => 'integer',
        'bigint' => 'integer',
        'varchar' => 'string',
        'text' => 'string',
        'mediumtext' => 'string',
        'longtext' => 'string',
        'enum' => 'string',
        'date' => 'date',
        'datetime' => 'date',
        'decimal' => 'float',
        'double' => 'float',
        'json' => 'array',
        'timestamp' => 'mixed',
    ];

    /**
     * @return void
     */
    public static function Cache(): void
    {
        Cache::store('file')->put('_dev_db', self::ReflectDBToModel());
    }

    /**
     * @return DBModel
     */
    public static function GetFromCache(): DBModel
    {
        return Cache::store('file')->rememberForever('_dev_db', function () {
            logger()->debug('DBServices::GetFromCache... cache missed');
            return self::ReflectDBToModel();
        });
    }

    /**
     * @param string $tableName
     * @return DBTableModel
     * @throws Err
     */
    public static function GetTable(string $tableName): DBTableModel
    {
        $dbModel = self::GetFromCache();
        $index = array_search($tableName, $dbModel->tableKeys);
        if ($index === false)
            ee("表不存在：$tableName");
        return $dbModel->tables[$index];
    }

    /**
     * @return DBModel
     */
    private static function ReflectDBToModel(): DBModel
    {
        $tables = Schema::getTables();
        $dbModel = new DBModel();

        $hasRoles = config('project.hasRoles') ?? [];
        $hasNodeTrait = config('project.hasNodeTrait') ?? [];
        $hasTags = config('project.hasTags') ?? [];
        $dbSkipGenModel = config('project.dbSkipGenModel') ?? [];
        $guards = config('project.guards') ?? [];

        // 第一次处理
        foreach ($tables as $table) {
            $dbTableModel = new DBTableModel();
            $dbTableModel->name = $table['name'];
            $dbTableModel->modelName = Str::of($table['name'])->camel()->ucfirst();
            $dbTableModel->comment = $table['comment'] ?? '';
            $dbTableModel->guardName = $guards[$table['name']] ?? null;
            $dbTableModel->hasRoles = in_array($dbTableModel->name, $hasRoles);
            $dbTableModel->hasNodeTrait = in_array($dbTableModel->name, $hasNodeTrait);
            $dbTableModel->hasTags = in_array($dbTableModel->name, $hasTags);
            $dbTableModel->skipModel = in_array($dbTableModel->name, $dbSkipGenModel);

            foreach (Schema::getColumns($table['name']) as $column) {
                $dbTableColumnModel = new DBTableColumnModel();
                $dbTableColumnModel->name = $column['name'];
                $dbTableColumnModel->typeName = $column['type_name'];
                $dbTableColumnModel->type = $column['type'];
                $dbTableColumnModel->propertyType = self::PROPERTY_TYPES[$column['type_name']] ?? 'unknown:' . $column['type_name'];
                $dbTableColumnModel->validateType = self::VALIDATE_TYPES[$column['type_name']] ?? 'unknown:' . $column['type_name'];
                $dbTableColumnModel->nullable = $column['nullable'];
                $dbTableColumnModel->default = $column['default'];
                $dbTableColumnModel->description = $column['comment'];
                $dbTableColumnModel->required = !$column['nullable'] && $column['default'] === null;
                $dbTableColumnModel->isPrimaryKey = $column['type_name'] == 'bigint' && $column['auto_increment'] == true;

                $dbTableModel->columns[] = $dbTableColumnModel;
                $dbTableModel->columnNames[] = $column['name'];
                if (str_contains($column['comment'], '[hidden]'))
                    $dbTableModel->hiddenColumns[] = $column['name'];
                if ($column['type'] === 'json')
                    $dbTableModel->jsonColumns[] = $column['name'];
                if ($column['name'] === 'deleted_at')
                    $dbTableModel->hasSoftDelete = true;

                // foreign key
                $foreignTableName = self::parseColumnForeignInfo($column);
                if ($foreignTableName) {
                    $dbTableColumnModel->isForeignKey = true;
                    $dbTableModel->foreignColumns[$column['name']] = $foreignTableName;
                }
            }

            $dbModel->tables[] = $dbTableModel;
            $dbModel->tableKeys[] = $table['name'];
        }

        // 第二次处理
        foreach ($dbModel->tables as $tableModel) {
            $tableModel->hasMany = self::parseHasMany($dbModel, $tableModel);
            $tableModel->belongsTo = self::parseBelongsTo($tableModel);
        }

        return $dbModel;
    }

    /**
     * @param array $column
     * @return string|null
     */
    private static function parseColumnForeignInfo(array $column): ?string
    {
        if ($column['type_name'] != 'bigint')
            return null;
        if (!str()->of($column['name'])->endsWith('s_id'))
            return null;

        $comment = $column['comment'];
        $name = $column['name'];

        // foreign key 备注中有：ref[表名]
        if (str()->of($comment)->contains("[ref:"))
            return str()->of($comment)->between("[ref:", "]")->snake();

        // foreign key 列名称：表名+_id
        return str()->of($name)->before('_id')->snake();
    }

    /**
     * @param DBModel $dbModel
     * @param DBTableModel $tableModel
     * @return array
     */
    private static function parseHasMany(DBModel $dbModel, DBTableModel $tableModel): array
    {
        $hasMany = [];
        foreach ($dbModel->tables as $table) {
            // 排除自己
            if ($table->name == $tableModel->name)
                continue;
            // 是否有跟自己相关的外键
            foreach ($table->foreignColumns as $foreignKey => $foreignTableName) {
                if ($foreignTableName == $tableModel->name)
                    $hasMany[$table->name] = [
                        'related' => str()->of($table->name)->studly()->toString(),
                        'foreignKey' => $foreignKey,
                        'localKey' => 'id'
                    ];
            }
        }
        return $hasMany;
    }

    /**
     * @param DBTableModel $tableModel
     * @return array
     */
    private static function parseBelongsTo(DBTableModel $tableModel): array
    {
        $belongsTo = [];
        foreach ($tableModel->foreignColumns as $foreignKey => $foreignTableName) {
            $belongsTo[str()->of(str_replace('_id', '', $foreignKey))->singular()->toString()] = [
                'related' => str()->of($foreignTableName)->studly()->toString(),
                'foreignKey' => $foreignKey,
                'ownerKey' => 'id'
            ];
        }
        return $belongsTo;
    }
}
