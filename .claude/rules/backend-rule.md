---
paths: app/Http/Controllers/*
---

# コントローラールール

- Laravel-dataで型安全に処理できるような設計にしてください。
    - 命名規則
        - apiのRequestなどで使用する場合は~~Request.phpにしてください。
        - apiの返り値などで使用する場合は~~Result.phpにしてください。
    - typescriptに変換できるよう`#[TypeScript]`は忘れないでください。
    - apiのRequestなどで使用する場合はフロントからキャメルケースで来るので``#[MapInputName(CamelCaseMapper::class)]``
      を追加してください。
    - バリデーションもDtoの中で書いてください。
