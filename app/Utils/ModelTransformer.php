<?php

namespace App\Utils;

use FumeApp\ModelTyper\Actions\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionClass;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Transformers\Transformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;
use FumeApp\ModelTyper\Overrides\ModelInspector;

class ModelTransformer implements Transformer
{
    public function __construct(protected TypeScriptTransformerConfig $config)
    {
    }

    public function transform(ReflectionClass $class, string $name): ?TransformedType
    {
        if (!$class->isSubclassOf(Model::class)) {
            return null;
        }

        // (1) 利用 Class を集める
        $inspector = app(ModelInspector::class);
        $inspect = $inspector->inspect($class->getName());

        $modelMaps = collect()
            // cast で利用するキャストを取得
            ->merge(collect(data_get($inspect, 'attributes'))->pluck('cast'))
            // relation で利用するキャストを取得
            ->merge(collect(data_get($inspect, 'relations'))->pluck('related'))
            // 大文字で始まるものを抽出
            ->filter(fn($attr) => Str::of($attr)->test('/^[A-Z]/'))
            // 一意にする
            ->unique()
//            // 使いやすいように [ClassName => Namespace] に変換
            ->mapWithKeys(fn($attr) => [
                Str::of($attr)->afterLast('\\')->toString() =>
                    Str::of($attr)->replace('\\', '.')->toString()
            ]);

        // (2) Model to Type
        $modelTyper = app(Generator::class)(
            specificModel: $class->getName(),
        );

        // type を成型する
        $format = Str::of($modelTyper)
            // 装飾子削除
            ->replaceMatches('/export interface \w+ /s', '')
            // 下部 enum 削除
            ->replaceMatches('/.const.*/s', '')
            // 末尾改行削除
            ->replaceMatches('/(\n|\r\n|\r)$/s', '')
            // 大文字クラス置換
            ->replaceMatches('/ ([A-Z]\w*)/', fn($match) => ' ' . $modelMaps->get(data_get($match, 1), data_get($match, 1)))
            // スネークケース → キャメルケース変換
            ->replaceMatches('/^(\s*)(\/\/\s*\w+.*|[a-z_]\w*\??):/m', function ($match) {
                $indent = $match[1];
                $prop = $match[2];

                // コメント行はそのまま
                if (str_starts_with(trim($prop), '//')) {
                    return "{$indent}{$prop}:";
                }

                $optional = str_ends_with($prop, '?') ? '?' : '';
                $name = rtrim($prop, '?');
                $camel = lcfirst(str_replace('_', '', ucwords($name, '_')));

                return "{$indent}{$camel}{$optional}:";
            });

        return TransformedType::create(
            $class,
            $name,
            $format->toString(),
        );
    }
}
