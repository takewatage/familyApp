---
paths: resources/js/*
---

# フロントエンドルール

- vuetifyのコンポーネントをできるだけ使用する。
- formはuseInertiaFormを使用してください。
- apiに送るときの型は[dto.generated.d.ts](../../resources/js/Types/dto.generated.d.ts)に作られたものを使用してください。
    - もし存在しない場合は```sail artisan typescript:transform```でtypescriptを生成してください。
    - それでもない場合は../../app/Dtos/にdtoを追加してください。
