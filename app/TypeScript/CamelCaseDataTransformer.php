<?php

namespace App\TypeScript;

use ReflectionClass;
use Spatie\LaravelData\Support\TypeScriptTransformer\DataTypeScriptTransformer;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

class CamelCaseDataTransformer extends DataTypeScriptTransformer
{
    protected function transformProperties(
        ReflectionClass $class,
        MissingSymbolsCollection $missingSymbols
    ): string {
        // 親の実装を呼び出してプロパティ文字列を生成し、スネークケースをキャメルケースに変換
        $result = parent::transformProperties($class, $missingSymbols);

        return preg_replace_callback(
            '/^(\s*)([a-z_][a-zA-Z0-9_]*?)(\??):\s*(.+)$/m',
            function ($matches) {
                $indent = $matches[1];
                $propertyName = $matches[2];
                $optional = $matches[3];
                $type = $matches[4];

                $camelCaseName = lcfirst(str_replace('_', '', ucwords($propertyName, '_')));

                return "{$indent}{$camelCaseName}{$optional}: {$type}";
            },
            $result
        );
    }
}