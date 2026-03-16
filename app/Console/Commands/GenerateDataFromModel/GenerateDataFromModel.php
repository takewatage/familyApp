<?php

namespace App\Console\Commands\GenerateDataFromModel;

use FumeApp\ModelTyper\Overrides\ModelInspector;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionNamedType;
use Symfony\Component\Finder\Finder;

class GenerateDataFromModel extends Command
{
    protected $signature = 'make:data-from-model
        {model? : The model class name (e.g. User). If omitted, all models are generated.}
        {--force : Overwrite existing files}';

    protected $description = 'Generate Laravel Data classes from Eloquent models';

    /**
     * PHP の型マッピング
     * ModelInspector が返す型 → PHP の型ヒント
     */
    protected array $typeMap = [
        'int' => 'int',
        'integer' => 'int',
        'float' => 'float',
        'double' => 'float',
        'decimal' => 'float',
        'string' => 'string',
        'bool' => 'bool',
        'boolean' => 'bool',
        'datetime' => 'string',
        'date' => 'string',
        'timestamp' => 'string',
        'json' => 'array',
        'array' => 'array',
        'object' => 'array',
        'text' => 'string',
    ];

    public function handle(): int
    {
        $modelName = $this->argument('model');

        if ($modelName) {
            $models = $this->resolveModel($modelName);
        } else {
            $models = $this->discoverModels();
        }

        if ($models->isEmpty()) {
            $this->error('No models found.');
            return self::FAILURE;
        }

        $generated = 0;

        foreach ($models as $modelClass) {
            if ($this->generateDataClass($modelClass)) {
                $generated++;
            }
        }

        $this->info("Generated {$generated} Data class(es).");

        return self::SUCCESS;
    }

    /**
     * モデル名からクラスを解決
     */
    protected function resolveModel(string $modelName): Collection
    {
        $class = "App\\Models\\{$modelName}";

        if (!class_exists($class)) {
            $this->error("Model [{$class}] not found.");
            return collect();
        }

        if (!is_subclass_of($class, Model::class)) {
            $this->error("[{$class}] is not an Eloquent Model.");
            return collect();
        }

        return collect([$class]);
    }

    /**
     * app/Models 配下の全モデルを検出
     */
    protected function discoverModels(): Collection
    {
        $modelPath = app_path('Models');

        if (!is_dir($modelPath)) {
            return collect();
        }

        $finder = (new Finder())
            ->files()
            ->name('*.php')
            ->in($modelPath);

        return collect($finder)
            ->map(function ($file) {
                $relativePath = $file->getRelativePathname();
                $class = 'App\\Models\\' . str_replace(
                        ['/', '.php'],
                        ['\\', ''],
                        $relativePath
                    );

                return $class;
            })
            ->filter(function ($class) {
                if (!class_exists($class)) {
                    return false;
                }
                $ref = new ReflectionClass($class);
                return $ref->isSubclassOf(Model::class) && !$ref->isAbstract();
            })
            ->values();
    }

    /**
     * Data クラスを生成
     */
    protected function generateDataClass(string $modelClass): bool
    {
        $ref = new ReflectionClass($modelClass);
        $modelName = $ref->getShortName();
        $dataClassName = "{$modelName}Data";

        $outputDir = app_path('Dtos/Model');
        $outputPath = "{$outputDir}/{$dataClassName}.php";

        if (File::exists($outputPath) && !$this->option('force')) {
            $this->warn("  [skip] {$dataClassName} already exists. Use --force to overwrite.");
            return false;
        }

        $this->info("  Generating {$dataClassName} from {$modelClass}...");

        // ModelInspector でモデル情報を取得
        $inspector = app(ModelInspector::class);
        $inspect = $inspector->inspect($modelClass);

        // カラム情報を収集
        $properties = $this->buildColumnProperties(
            collect(data_get($inspect, 'attributes', []))
        );

        // リレーション情報を収集
        $relationProperties = $this->buildRelationProperties(
            collect(data_get($inspect, 'relations', []))
        );

        // accessor 情報を収集
        $accessorProperties = $this->buildAccessorProperties(
            collect(data_get($inspect, 'attributes', [])),
            $ref,
        );

        // use 文を収集
        $imports = collect([
            'Spatie\\LaravelData\\Attributes\\MapOutputName',
            'Spatie\\LaravelData\\Data',
            'Spatie\\LaravelData\\Mappers\\CamelCaseMapper',
            'Spatie\\TypeScriptTransformer\\Attributes\\TypeScript',
        ]);

        // Optional が必要かチェック（リレーションまたは accessor がある場合）
        $needsOptional = $relationProperties->isNotEmpty() || $accessorProperties->isNotEmpty();
        if ($needsOptional) {
            $imports->push('Spatie\\LaravelData\\Optional');
        }

        // リレーション・accessor の Data クラスの use 文
        $extraImports = $relationProperties
            ->merge($accessorProperties)
            ->pluck('import')
            ->filter()
            ->unique();
        $imports = $imports->merge($extraImports);

        // DataCollection が必要かチェック
        $needsDataCollection = $relationProperties->contains(fn($p) => $p['is_collection']);
        if ($needsDataCollection) {
            $imports->push('Spatie\\LaravelData\\DataCollection');
        }

        // プロパティ文字列を組み立て
        $allProperties = $properties
            ->merge($accessorProperties)
            ->merge($relationProperties)
            ->map(fn($p) => $this->formatProperty($p))
            ->join("\n");

        // ファイル内容を生成
        $content = $this->buildFileContent(
            $dataClassName,
            $imports->unique()->sort()->values(),
            $allProperties,
        );

        // 出力
        File::ensureDirectoryExists($outputDir);
        File::put($outputPath, $content);

        $this->line("    → {$outputPath}");

        return true;
    }

    /**
     * カラム情報から プロパティ定義を構築
     */
    protected function buildColumnProperties(Collection $attributes): Collection
    {
        return $attributes
            ->filter(fn($attr) => !data_get($attr, 'hidden', false))
            ->filter(fn($attr) => data_get($attr, 'cast') !== 'accessor')
            ->map(function ($attr) {
                $name = data_get($attr, 'name');
                $type = $this->resolvePhpType($attr);
                $nullable = data_get($attr, 'nullable', false);

                return [
                    'name' => $name,
                    'type' => $type,
                    'nullable' => $nullable,
                    'is_collection' => false,
                    'import' => null,
                    'comment' => null,
                ];
            });
    }

    /**
     * リレーション情報からプロパティ定義を構築
     */
    protected function buildRelationProperties(Collection $relations): Collection
    {
        return $relations
            ->filter(fn($relation) => Str::startsWith(data_get($relation, 'related', ''), 'App\\'))
            ->map(function ($relation) {
                $name = data_get($relation, 'name');
                $type = data_get($relation, 'type');
                $related = data_get($relation, 'related');

                $relatedShortName = Str::of($related)->afterLast('\\')->toString();
                $dataClass = "{$relatedShortName}Data";

                $isCollection = in_array($type, [
                    'HasMany',
                    'BelongsToMany',
                    'MorphMany',
                    'MorphToMany',
                    'HasManyThrough',
                ]);

                $phpType = $isCollection
                    ? "DataCollection"
                    : $dataClass;

                $import = "App\\Dtos\\Model\\{$dataClass}";

                return [
                    'name' => $name,
                    'type' => $phpType,
                    'nullable' => false,
                    'hidden' => false,
                    'is_collection' => $isCollection,
                    'import' => $import,
                    'comment' => "// relation: {$type}",
                    'data_class' => $dataClass,
                ];
            });
    }

    /**
     * accessor (get*Attribute) からプロパティ定義を構築
     */
    protected function buildAccessorProperties(Collection $attributes, ReflectionClass $ref): Collection
    {
        return $attributes
            ->filter(fn($attr) => data_get($attr, 'cast') === 'accessor')
            ->filter(fn($attr) => !data_get($attr, 'hidden', false))
            ->map(function ($attr) use ($ref) {
                $name = data_get($attr, 'name');

                // get{Name}Attribute メソッドを探す
                $methodName = 'get' . Str::studly($name) . 'Attribute';
                $type = 'string';
                $nullable = false;
                $import = null;

                if ($ref->hasMethod($methodName)) {
                    $method = $ref->getMethod($methodName);
                    $returnType = $method->getReturnType();

                    if ($returnType instanceof ReflectionNamedType) {
                        $nullable = $returnType->allowsNull();
                        $typeName = $returnType->getName();

                        if (class_exists($typeName) && is_subclass_of($typeName, Model::class)) {
                            // Model の場合は対応する Data クラスに変換
                            $shortName = (new ReflectionClass($typeName))->getShortName();
                            $type = "{$shortName}Data";
                            $import = "App\\Dtos\\Model\\{$shortName}Data";
                        } elseif ($returnType->isBuiltin()) {
                            $type = $typeName;
                        } else {
                            $type = '\\' . $typeName;
                        }
                    }
                }

                return [
                    'name' => $name,
                    'type' => $type,
                    'nullable' => $nullable,
                    'is_collection' => false,
                    'import' => $import,
                    'comment' => '// accessor',
                ];
            });
    }

    /**
     * ModelInspector の属性情報から PHP の型を解決
     */
    protected function resolvePhpType(array $attr): string
    {
        $cast = data_get($attr, 'cast');
        $phpType = data_get($attr, 'type', 'string');

        // cast が設定されている場合、cast を優先
        if ($cast) {
            $castLower = Str::of($cast)->lower()->before(':')->toString();

            if (array_key_exists($castLower, $this->typeMap)) {
                return $this->typeMap[$castLower];
            }

            // Enum などのクラスキャスト
            if (class_exists($cast)) {
                return '\\' . $cast;
            }
        }

        // 型名を正規化
        $normalized = Str::of($phpType)->lower()->before(':')->toString();

        return $this->typeMap[$normalized] ?? 'string';
    }

    /**
     * プロパティ定義を1行のコンストラクタ引数文字列にフォーマット
     */
    protected function formatProperty(array $prop): string
    {
        $name = $prop['name'];
        $type = $prop['type'];
        $nullable = $prop['nullable'];
        $isCollection = $prop['is_collection'];
        $comment = $prop['comment'] ?? null;

        $parts = [];

        if ($comment) {
            $parts[] = "        {$comment}";
        }

        // 型の組み立て
        $typeHint = $type;

        if ($isCollection) {
            // /** @var DataCollection<int, XxxData> */
            $dataClass = $prop['data_class'];
            $parts[] = "        /** @var DataCollection<int, {$dataClass}> */";
        }

        // Optional（リレーションまたは accessor）
        $isOptional = $prop['import'] !== null || ($comment ?? '') === '// accessor';
        if ($isOptional) {
            $typeHint = $nullable
                ? "{$typeHint}|Optional|null"
                : "{$typeHint}|Optional";
        } elseif ($nullable) {
            $typeHint = "?{$typeHint}";
        }

        $parts[] = "        public {$typeHint} \${$name},";

        return implode("\n", $parts);
    }

    /**
     * ファイルの内容を組み立て
     */
    protected function buildFileContent(
        string     $className,
        Collection $imports,
        string     $properties,
    ): string
    {
        $useStatements = $imports
            ->map(fn($import) => "use {$import};")
            ->join("\n");

        // 末尾のカンマを除去
        $properties = Str::of($properties)->replaceLast(',', '');

        return <<<PHP
        <?php

        namespace App\Dtos\Model;

        {$useStatements}

        #[TypeScript]
        #[MapOutputName(CamelCaseMapper::class)]
        class {$className} extends Data
        {
            public function __construct(
        {$properties}
            ) {}
        }

        PHP;
    }
}
