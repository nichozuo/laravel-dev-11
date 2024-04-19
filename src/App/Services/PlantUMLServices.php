<?php

namespace LaravelDev\App\Services;

use LaravelDev\App\Models\Database\DBTableModel;

class PlantUMLServices
{
    /**
     * @param string $name
     * @return string
     */
    public static function getErMap(string $name): string
    {
        $config = config('project.erMaps') ?? [];
        if (!array_key_exists($name, $config))
            return '# Not Found';

        $tables = $config[$name];

        return self::getErMapMarkdownByTables($tables);

    }

    /**
     * @param array $tables
     * @return string
     */
    private static function getErMapMarkdownByTables(array $tables): string
    {
        $entityStr = "";
        $relationStr = "";

        foreach (DBServices::GetFromCache()->tables as $table) {
            if (in_array($table->name, $tables)) {
                self::parseEntity($table, $entityStr);
                self::parseRelation($table, $relationStr);
            }
        }
        return "$entityStr}$relationStr";
    }

    /**
     * @param DBTableModel $table
     * @param string $stringBuilder
     * @return void
     */
    private static function parseEntity(DBTableModel $table, string &$stringBuilder): void
    {
        $name = $table->comment ?? $table->name;
        $stringBuilder .= "entity \"$name\" as $table->name {\n";

        # pk
        foreach ($table->columns as $column) {
            if ($column->isPrimaryKey)
                $stringBuilder .= "\t* $column->name <<PK>>\n";
        }
        $stringBuilder .= "\t--\n";

        # fk
        $hasFk = false;
        foreach ($table->columns as $column) {
            if ($column->isForeignKey) {
                $prefix = $column->nullable ? '-' : '+';
                $stringBuilder .= "\t$prefix $column->name : $column->description <<FK>>\n";
                $hasFk = true;
            }
        }
        if ($hasFk)
            $stringBuilder .= "\t--\n";

        # fields
        foreach ($table->columns as $column) {
            if ($column->isPrimaryKey || $column->isForeignKey)
                continue;

            if (!$column->description)
                continue;

            $prefix = $column->nullable ? '-' : '+';
            $stringBuilder .= "\t$prefix $column->description\n";
        }

        $stringBuilder .= "}\n\n";
    }

    /**
     * @param DBTableModel $table
     * @param string $relationStr
     * @return void
     */
    private static function parseRelation(DBTableModel $table, string &$relationStr): void
    {
        foreach ($table->belongsTo as $item) {
            $relationStr .= "$table->name::{$item['foreignKey']} \"N\" --o \"1\" {$item['table']}::id\n";
        }
    }
}