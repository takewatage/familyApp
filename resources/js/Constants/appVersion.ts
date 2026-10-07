// vite.config.js の define でビルド時に埋め込まれるバージョン情報（表示例: v1.0.0 (abc1234)）
export const APP_VERSION = __APP_VERSION__
export const APP_BUILD_ID = __APP_BUILD_ID__
export const APP_VERSION_LABEL = `v${APP_VERSION} (${APP_BUILD_ID})`
