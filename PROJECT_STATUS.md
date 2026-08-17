# Household App - Project Status

> このファイルは、Household Appの現在の実装状態をAIへ引き継ぐための開発コンテキスト。
>
> 人間向けの日報・作業履歴ではなく、次回のAIが過去のチャットを読まなくても、現在の実装状態を把握して続きから開発できることを目的とする。

---

# 0. このプロジェクトの全体像

## 人間向け簡易概要

```text
Household App
│
├─ 認証
│   ├─ 会員登録        ← 実装済み・動作確認済み
│   ├─ ログイン        ← 実装済み・動作確認済み
│   └─ ログアウト      ← 実装済み・動作確認済み
│
├─ 家計簿基盤
│   ├─ ユーザー
│   ├─ 口座
│   ├─ カテゴリ
│   ├─ 取引
│   ├─ 振替
│   └─ 自動入力ルール
│
├─ 家計簿機能
│   ├─ 口座管理        ← 未実装
│   ├─ カテゴリ管理    ← 未実装
│   ├─ 取引管理        ← 未実装
│   ├─ 振替画面        ← 未実装
│   ├─ 開始残高        ← 未実装
│   ├─ 自動カテゴリ    ← 未実装
│   ├─ 経費処理        ← 未実装
│   └─ 領収書管理      ← 未実装
│
└─ 集計・UI
    ├─ 月間収支        ← 未実装
    ├─ カテゴリ別集計  ← 未実装
    ├─ 口座残高        ← 未実装
    ├─ Dashboard       ← 未実装
    └─ 本格UI          ← 未実装
```

現在は、
**認証基盤 + 家計簿DB/Model/Service/Test基盤まで完成し、実際の家計簿機能の実装へ移行する段階。**

---

# 1. AI引き継ぎルール

## このファイルが共有された場合

ユーザーがこの `PROJECT_STATUS.md` の全文を共有した場合、基本的に**この内容を現在の開発状態として引き継ぎ、続きから実装する**。

ユーザーが単にこのファイルを渡しただけの場合でも、

- 「更新して」
- 「全文返して」
- 「現在の状態に合わせて」

などの追加指示は不要。

現在の会話内で実装・変更・動作確認された内容がある場合は、それを優先して現在状態を更新する。

## 引き継ぎ時の原則

1. このファイルから現在の実装状態を把握する。
2. 現在の会話で確認できる情報を、このファイルの古い情報より優先する。
3. 実装済み・未実装・動作確認済みの状態を現在の情報に合わせる。
4. 不明な内容は推測して変更しない。
5. コード全文をこのファイルへ保存しない。
6. 重要な設計判断・制約・仕様のみ記録する。
7. 古くなった情報や現在の状態と矛盾する情報は整理する。
8. 新しく重要な実装・設計判断があれば追記する。
9. Gitのcommit / pushが確認できる場合は、Git状態も更新する。
10. 更新が必要な場合は、**更新後の `PROJECT_STATUS.md` をダウンロードできるファイルとして返す**。
11. 更新不要な場合でも、現在の状態を把握したうえで実装を続行できる状態にする。

## 「続きから実装始めたい」と言われた場合

このファイルの内容を現在状態として扱い、次の未実装項目から開発を開始する。

実装順序は固定ではない。
設計上必要な順序や、現在の実装状態に応じて合理的に変更してよい。

---

# 2. このファイルの更新タイミング

理想的な開発フロー：

```text
PROJECT_STATUS.mdを共有
        ↓
「続きから実装始めたい」
        ↓
実装
        ↓
動作確認・テスト
        ↓
一区切り
        ↓
AIからPROJECT_STATUS.md更新を提案
        ↓
PROJECT_STATUS.mdを更新
        ↓
ユーザーが内容確認
        ↓
git commit / push
        ↓
作業終了
```

以下のタイミングでは、AIは `PROJECT_STATUS.md` の更新を提案する。

- まとまった機能の実装が完了したとき
- 動作確認が完了したとき
- テスト追加・変更が完了したとき
- 設計判断が確定したとき
- Gitへpushする前
- 1日の作業終了時
- チャットを切り替える必要があるとき

ユーザーがこのファイルを共有した場合は、**その時点までの会話内容を反映した最新版をダウンロードできるファイルとして返す**。

---

# 3. コード確認について

このファイルにはコード全文を保存しない。

実装の詳細確認が必要な場合は、対象ファイルをユーザーに提示してもらう。

例：

```text
app/Models/Transaction.php の現在の内容を出してください。
```

など、必要なファイルを明示する。

GitHub等からコード全体を推測して補完しない。

---

# 4. Current Development Phase

## 現在のフェーズ

**認証基盤 + 家計簿バックエンド基盤完成。**

現在は、設計・基盤構築フェーズから、

**実際にユーザーが家計簿を操作できる機能の実装フェーズ**

へ移行している。

---

# 5. Authentication

Laravel Fortifyを使用。

## 実装済み

- ユーザー登録
- ログイン
- ログアウト
- Remember Me
- 認証Middleware
- `/home`の認証制御
- ログイン画面
- 会員登録画面
- パスワードリセット関連Fortifyルート
- 2FA関連DB
- Passkey関連DB

## 動作確認済み

- `/login` にアクセスするとログイン画面が表示される
- 新規登録できる
- 登録後にログイン状態になる
- 登録後 `/home` が表示される
- ログインできる
- 不正パスワードではログインできない
- ログアウトできる
- 未認証で `/home` にアクセスすると `/login` にリダイレクトされる

## 注意：現在の認証画面について

`resources/views/auth/login.blade.php`

`resources/views/auth/register.blade.php`

および

`resources/views/home.blade.php`

は、**認証機能を動作確認するための暫定・プレースホルダーUI**。

認証機能自体は実装済みだが、これらの画面デザインや家計簿UIが完成したという意味ではない。

後続のUI実装フェーズで、

- 本格的なログイン画面
- 本格的な会員登録画面
- 家計簿Dashboard
- 家計簿全体のレイアウト

へ置き換える。

**暫定UIを正式UIとして扱わないこと。**

---

# 6. Database Design

家計簿の主要DB設計は実装済み。

## users

Laravel標準ユーザー情報。

Fortify導入により、

- 2FA関連カラム
- Passkey関連テーブル

も追加済み。

## categories

ユーザーごとのカテゴリ。

主な項目：

- `user_id`
- `name`

制約：

- `(user_id, name)` unique
- User削除時cascade

## accounts

ユーザーごとの口座・決済手段。

主な項目：

- `user_id`
- `name`

制約：

- `(user_id, name)` unique
- User削除時cascade

## transactions

家計簿の中心となる取引。

主な項目：

- `user_id`
- `transaction_date`
- `type`
- `account_id`
- `category_id`
- `counterparty_name`
- `amount`
- `withdrawal_date`
- `expense_ratio`
- `expense_registered`
- `receipt_saved`

主なindex：

- `user_id + transaction_date`
- `user_id + type`
- `user_id + counterparty_name`

## transfers

口座間振替を2つのTransactionとして関連付ける。

項目：

- `from_transaction_id`
- `to_transaction_id`

両方unique。

Transaction削除時はcascade。

## transaction_rules

取引先名などからカテゴリを自動判定するためのルール。

項目：

- `user_id`
- `keyword`
- `display_name`
- `category_id`
- `priority`

主なindex：

- `user_id + keyword`
- `user_id + priority`

---

# 7. Transaction Type

`App\Enums\TransactionType`

現在の種別：

- `expense`
- `income`
- `transfer`
- `opening_balance`

`withdrawal` は定義しない。

クレジットカード等の引落は、独立したTransactionTypeではなく、
`withdrawal_date`
で管理する設計。

`TransactionTypeTest` で仕様を検証済み。

---

# 8. Eloquent Models

以下を実装済み。

- `User`
- `Account`
- `Category`
- `Transaction`
- `Transfer`
- `TransactionRule`

## User

- hasMany Categories
- hasMany Accounts
- hasMany Transactions
- hasMany TransactionRules

## Account

- belongsTo User
- hasMany Transactions

## Category

- belongsTo User
- hasMany Transactions
- hasMany TransactionRules

## Transaction

- belongsTo User
- belongsTo Account
- belongsTo Category
- hasOne outgoing Transfer
- hasOne incoming Transfer

## Transfer

- belongsTo from Transaction
- belongsTo to Transaction

## TransactionRule

- belongsTo User
- belongsTo Category

基本的なRelationについてUnit Testあり。

---

# 9. TransactionService

`app/Services/TransactionService.php`

## 実装済み

`createTransfer()`

口座間の資金移動を、

- from Transaction
- to Transaction
- Transfer

の組み合わせで登録する。

DB Transactionを使用して一連の登録を原子的に処理。

## バリデーション

- 振替元と振替先が同一口座 → 拒否
- 他ユーザーの口座 → 拒否
- 0以下の金額 → 拒否
- 0.01以上 → 許可

## テスト済み

`tests/Unit/TransactionServiceTest.php`

正常系・異常系・DB登録結果・属性を検証済み。

---

# 10. Seeder

`database/seeders/HouseholdDataSeeder.php`

テスト用家計簿データを作成する。

## Categories

- 食費
- 交通費
- 家賃
- 給与
- その他

## Accounts

- 現金
- 三井住友銀行
- クレジットカード

## Sample Transactions

- 家賃 100,000円
- Amazon 3,500円
- 給与 300,000円
- 三井住友銀行 → 現金 30,000円

家賃：

- `expense_ratio = 40`

Amazon：

- `withdrawal_date` 設定あり

## Sample TransactionRule

キーワード：

`ﾔﾁﾝ`

適用カテゴリ：

`家賃`

priority：

`100`

---

# 11. Tests

## Feature

`tests/Feature/AuthenticationTest.php`

認証の主要フローを検証。

確認済み：

- 登録
- ログイン
- 不正パスワード
- ログアウト
- 認証済みHome
- 未認証Homeアクセス
- Guest状態

## Unit

### AccountTransactionTest

Account ↔ Transaction Relationを検証。

### TransactionTypeTest

TransactionTypeの定義とvalue変換を検証。

### TransferRelationshipTest

Transfer ↔ Transaction Relationおよび外部キー名を検証。

### TransactionServiceTest

振替Serviceの正常系・異常系・DB結果を検証。

---

# 12. Current Routes

## Web

`routes/web.php`

現在の主要ルート：

- `/`
- `/home`

`/home` は `auth` middleware配下。

Fortifyによる認証ルート：

- `/login`
- `/register`
- `/logout`
- その他Fortify提供ルート

## API

`routes/api.php`

現在は認証ユーザー情報取得用のAPIのみ。

家計簿APIは未実装。

---

# 13. Current Views

## Authentication

- `resources/views/auth/login.blade.php`
- `resources/views/auth/register.blade.php`

認証動作確認用の暫定UI。

認証処理は完成しているが、最終的なUIではない。

## Home

`resources/views/home.blade.php`

現在は、

- アプリ名
- ログインユーザー名
- ログアウト
- ホーム見出し
- Dashboard予定のプレースホルダー

のみ。

**正式な家計簿Dashboardではない。**

---

# 14. Not Yet Implemented

## Account

- 一覧
- 作成
- 編集
- 削除
- Controller
- UI

## Category

- 一覧
- 作成
- 編集
- 削除
- Controller
- UI

## Transaction

- 登録
- 一覧
- 詳細
- 編集
- 削除
- Controller / API
- UI

## Transfer

Serviceは実装済み。

未実装：

- Controller / API
- 入力画面
- UI
- 実際の画面からの振替処理

## TransactionRule

DB / Model / Seederは実装済み。

未実装または未確認：

- Transaction登録時の自動適用
- キーワード判定
- priorityによるルール選択
- ルール管理UI

## Opening Balance

Enumは存在する。

未実装：

- 開始残高登録Service
- Controller / API
- UI

## Expense

DB項目：

- `expense_ratio`
- `expense_registered`

は存在する。

未実装：

- 経費計算
- 経費登録処理
- UI

## Receipt

DB項目：

- `receipt_saved`

は存在する。

未実装：

- 領収書保存処理
- ファイル管理
- UI

## Dashboard

未実装。

想定：

- 月間収支
- カテゴリ別支出
- 口座残高
- その他家計簿情報

---

# 15. Recommended Next Implementation

現時点では以下の順序を基本とする。

```text
Account / Category管理
        ↓
Transaction登録
        ↓
Transaction一覧・詳細・編集・削除
        ↓
Transfer UI
        ↓
TransactionRule自動適用
        ↓
Opening Balance
        ↓
Expense / Receipt
        ↓
集計
        ↓
Dashboard
        ↓
正式UI・デザイン改善
```

ただし、実装中に依存関係や設計上の理由があれば順序変更可能。

最初の候補は、

**Account / Category管理**

---

# 16. Important Design Decisions

## Transaction中心設計

収入・支出・振替・開始残高をTransactionとして扱う。

## 振替

口座間振替は、

- 振替元Transaction
- 振替先Transaction
- Transfer

の3要素で表現する。

## Withdrawal

引落を独立したTransactionTypeにはしない。

`withdrawal_date` で管理する。

## User isolation

Account / Category / Transaction / TransactionRuleはuser_idを持つ。

ユーザー間のデータ混在を防ぐ設計。

TransactionServiceでは、振替元・振替先Accountが操作対象Userに属することを確認済み。

## Category / Account名

同一ユーザー内では同名を許可しない。

DBで、

`unique(user_id, name)`

を設定している。

---

# 17. Current Relevant File Structure

```text
app/
├── Actions/Fortify/
│   ├── CreateNewUser.php
│   ├── PasswordValidationRules.php
│   ├── ResetUserPassword.php
│   ├── UpdateUserPassword.php
│   └── UpdateUserProfileInformation.php
├── Enums/
│   └── TransactionType.php
├── Http/Controllers/
│   └── Controller.php
├── Models/
│   ├── Account.php
│   ├── Category.php
│   ├── Transaction.php
│   ├── TransactionRule.php
│   ├── Transfer.php
│   └── User.php
├── Providers/
│   ├── AppServiceProvider.php
│   └── FortifyServiceProvider.php
└── Services/
    └── TransactionService.php

database/
├── migrations/
├── seeders/
│   ├── DatabaseSeeder.php
│   └── HouseholdDataSeeder.php
└── factories/
    └── UserFactory.php

resources/views/
├── auth/
│   ├── login.blade.php
│   └── register.blade.php
├── home.blade.php
└── welcome.blade.php

routes/
├── api.php
├── console.php
└── web.php

tests/
├── Feature/
│   ├── AuthenticationTest.php
│   └── ExampleTest.php
└── Unit/
    ├── AccountTransactionTest.php
    ├── ExampleTest.php
    ├── TransactionServiceTest.php
    ├── TransactionTypeTest.php
    └── TransferRelationshipTest.php
```

---

# 18. Git / Last Known State

## 最新確認済みcommit

```text
57b8612
complete authentication views
```

このcommitで、

- Fortify認証画面
- ログイン画面
- 会員登録画面
- FortifyServiceProviderの修正

を含む認証機能の動作確認用UIがGitHubへpush済み。

GitHubの `main` とローカル `main` が同期していることを最後に確認済み。

※ この `PROJECT_STATUS.md` 自体は、このファイル作成後に別commitとしてpushする予定。

---

# 19. Current State Summary

現在の状態を一言で表すと：

**「認証と家計簿のバックエンド基盤は完成。認証の暫定UIまで動作確認済み。次は実際の家計簿操作機能を作る段階。」**

特に重要なのは、

- 認証は実装済み
- 会員登録 → `/home` まで動作確認済み
- DB設計は実装済み
- Model / Relationは実装済み
- TransactionTypeは実装済み
- 振替Serviceは実装済み
- 基本テストは実装済み
- ログイン・登録・Home画面は**動作確認用の暫定UI**
- 家計簿としての正式なUIはまだ作っていない
- 次はAccount / Category管理から実装する

という状態。

---

# 20. Handoff Rule

次回このファイル全文が共有された場合は、

**この状態から続きの実装を開始する。**

ユーザーが

> 続きから実装始めたい

と言った場合は、基本的に現在のNext Implementationから着手する。

実装・テスト・動作確認が一区切りついた場合は、

**`PROJECT_STATUS.md` の更新を提案する。**

更新する場合は、今回までの実装内容を反映した**最新版の `PROJECT_STATUS.md` をダウンロードできるファイルとして返す**。

コード全文や細かなコマンド履歴は、このファイルへ追加しない。

---

# End of Project Status
