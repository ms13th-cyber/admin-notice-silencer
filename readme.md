# Admin Notice Silencer

A lightweight, single-function WordPress plugin designed to keep your admin dashboard clean by silencing persistent notices and banners.

WordPress管理画面に乱立するプラグインの通知やバナーをワンクリックで永続的にミュートし、作業に集中できるクリーンな環境を提供する軽量プラグインです。

---

## Key Features

- **One-Click Mute**: Add a "Mute" button to every non-critical admin notice for instant silencing.
- **Plugin-Based Identification**: Automatically identifies the source plugin (via CSS classes) to mute related notices collectively.
- **Smart Safety Guard**: Critical system errors and core update nag notices are protected and cannot be muted to ensure site safety.
- **User-Specific Control**: Mute settings are saved per user, ensuring other administrators' views remain unaffected.
- **Integrated Management UI**: A simple settings page to review and restore muted notices at any time.
- **Performance Optimized**: Silenced notices are hidden instantly via CSS injection, preventing "layout shift" during page load.

## 主な機能（日本語）

- **ワンクリック・ミュート**: 各通知の右側に配置された「ミュート」ボタンから、即座に非表示化が可能。
- **プラグイン単位の識別**: CSSクラス等を解析し、特定のプラグインから出力される通知を系統立ててブロック。
- **高度な安全弁**: 致命的なシステムエラーやコアの更新通知はミュート対象外として保護。運用の安全を確保します。
- **ユーザーごとの個別設定**: ミュート状態はユーザーメタデータとして保存。他の管理者の画面には影響を与えません。
- **統合管理画面**: 設定メニューから、現在ミュート中の識別子を一覧確認・一括解除が可能。
- **超軽量・高速動作**: 保存されたIDに基づきPHPからCSSを直接注入。ページ読み込み時のチラつき（チャタリング）が発生しません。

---

## Features Overview / 機能概要

### Admin Interaction / 操作機能
- Silence/Mute button injection / 通知へのミュートボタン挿入
- Instant fade-out & Ajax save / フェードアウト演出とAjaxによる非同期保存
- Restoration UI in Settings / 設定画面からの解除機能

### Safety & Logic / ロジック
- Skip critical notices (`.notice-error`, `.update-nag`) / 致命的なエラー等の除外
- Fallback text-hashing for unclassed notices / クラス名のない通知へのテキストハッシュ判定
- Zero-config, single-file architecture / 設定不要、1ファイル完結のシンプル構成

---

## Installation / インストール

1. Upload the `admin-notice-silencer` folder to your `/wp-content/plugins/` directory.
   (`wp-content/plugins/` ディレクトリにプラグインフォルダをアップロードします)
2. Activate the plugin through the 'Plugins' menu in WordPress.
   (管理画面の「プラグイン」メニューから有効化してください)
3. Click the **"Mute (ミュート)"** button on any annoying notice.
   (邪魔な通知に表示される「ミュート」ボタンをクリックします)
4. Manage or restore notices via **Settings > Notice Silencer**.
   (設定 > Notice Silencer から、ミュート状態の管理・解除が可能です)

---

## Technical Details

### Storage
- Data is stored in the `wp_usermeta` table under the key `ans_silenced_classes`.

### Technology Stack
- WordPress (User Meta API, Ajax, Settings API)
- Vanilla JavaScript (No jQuery dependency)
- Pure CSS Injection

---

## Developer Info / 開発者情報
- **Author**: masato shibuya (Image-box Co., Ltd.)
- **GitHub**: [https://github.com/ms13th-cyber/](https://github.com/ms13th-cyber/)
- **License**: MIT License