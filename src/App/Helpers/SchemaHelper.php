<?php

namespace LaravelDev\App\Helpers;

use Illuminate\Database\Schema\Blueprint;
use ReflectionEnum;
use ReflectionException;

class SchemaHelper
{
    /**
     * 标准枚举字段
     * @throws ReflectionException
     */
    public static function Enum(Blueprint $table, string $field, string $enumClass, string $comment, mixed $default = null, bool $nullable = false): void
    {
        $enum = new ReflectionEnum($enumClass);
        $className = str_replace('App\\Enums\\', '', $enumClass);

        $values = array_map(function ($item) {
            return $item->getValue()->value;
        }, $enum->getCases());

        $table->enum($field, $values)
            ->comment("$comment,[enum:$className]")
            ->nullable($nullable)
            ->default($default);
    }

    /**
     * 标准外键字段
     * @param Blueprint $table
     * @param string $field
     * @param string $comment
     * @param string|null $referenceTable
     * @param bool|null $nullable
     * @return void
     */
    public static function foreignId(Blueprint $table, string $field, string $comment, ?string $referenceTable = null, ?bool $nullable = false): void
    {
        $referenceTable = $referenceTable ?? str_replace('_id', '', $field);
        $table->foreignId($field)
            ->comment("{$comment}id,[ref:$referenceTable]")
            ->nullable($nullable);
    }

    /**
     * 省市区标准字段，可以统一加prefix
     * @param Blueprint $table
     * @param string|null $prefix
     * @param bool|null $nullable
     * @return void
     */
    public static function Cities(Blueprint $table, ?string $prefix = '', ?bool $nullable = true): void
    {
        $table->string($prefix . 'province', 20)->comment('省')->nullable($nullable);
        $table->string($prefix . 'province_code', 3)->comment('省编码')->nullable($nullable);
        $table->string($prefix . 'city', 20)->comment('市')->nullable($nullable);
        $table->string($prefix . 'city_code', 5)->comment('市编码')->nullable($nullable);
        $table->string($prefix . 'area', 20)->comment('区')->nullable($nullable);
        $table->string($prefix . 'area_code', 7)->comment('区编码')->nullable($nullable);
        $table->string($prefix . 'street', 50)->comment('街道')->nullable($nullable);
        $table->string($prefix . 'street_code', 12)->comment('街道编码')->nullable($nullable);
    }
}
