# Household App - Project Status

> このファイルは、Household App の現在の実装状態を AI
> へ引き継ぐための開発コンテキスト。
>
> 人間向けの日報・作業履歴ではなく、次回の AI
> が過去のチャットを読まなくても、現在の実装状態を把握して続きから開発できることを目的とする。

------------------------------------------------------------------------

# 0. このプロジェクトの全体像

## 人間向け簡易概要

``` text
Household App
│
├─ 認証
│   ├─ 会員登録                  ← 実装済み・動作確認済み
│   ├─ ログイン                  ← 実装済み・動作確認済み
│   └─ ログアウト                ← 実装済み・動作確認済み
│
├─ 家計簿基盤
│   ├─ ユーザー
│   ├─ 口座
│   ├─ カテゴリ
│   ├─ 取引
│   ├─ 振替
│   └─ 取引ルール
│
├─ 家計簿機能
│   ├─ 口座管理                  ← CRUD完了
│   ├─ カテゴリ管理              ← CRUD完了
│   ├─ 支出・収入管理            ← CRUD完了
│   ├─ 振替                      ← CRUD完了
│   ├─ 初期残高                  ← CRUD完了
│   ├─ 取引ルール                ← CRUD + 表示名変換 + カテゴリ補助完了
│   ├─ 月間集計                  ← 最低限版完了
│   ├─ 口座残高                  ← 最低限版完了
│   ├─ Dashboard                 ← 最低限版完了
│   ├─ 経費処理                  ← 未実装
│   └─ 領収書管理                ← 未実装
│
└─ UI / レポート
    ├─ 正式Tailwind UI           ← 未実装
    ├─ 年間集計                  ← 後回し
    ├─ グラフ                    ← 後回し
    ├─ 前月比較                  ← 後回し
    └─ 詳細レポート              ← 後回し
```

現在は、**家計簿として必要な主要入力・編集・削除・最低限の集計・Dashboardまで実装済み**。

次のフェーズは、実際に一通り使用してワークフロー・不足機能・必要情報を確認し、その後に正式UI・表示拡張へ進む。

------------------------------------------------------------------------

# 1. AI引き継ぎルール

## このファイルが共有された場合

ユーザーがこの `PROJECT_STATUS.md`
を共有した場合、基本的にこの内容を現在の開発状態として引き継ぎ、続きから実装する。

現在の会話内で実装・変更・動作確認された内容がある場合は、このファイルより新しい会話内容を優先する。

## 引き継ぎ時の原則

1.  このファイルから現在の実装状態を把握する。
2.  現在の会話で確認できる情報を、このファイルの古い情報より優先する。
3.  実装済み・未実装・動作確認済みを明確に区別する。
4.  不明な内容は推測しない。
5.  コード全文をこのファイルへ保存しない。
6.  重要な設計判断・制約・仕様のみ記録する。
7.  古くなった情報や矛盾は整理する。
8.  Git の commit / push
    は、ユーザーが結果を共有した場合のみ完了扱いにする。
9.  更新が必要な場合は、更新後の `PROJECT_STATUS.md`
    をダウンロードできるファイルとして返す。

## コード提示・修正スタイル

ユーザーは、AIがコマンド等でローカルファイルを直接確認・変更する進め方を希望していない。

確認が必要な場合：

``` text
app/Models/Transaction.php の現在の内容を出してください。
```

のように対象ファイルを明示する。

修正時は、可能な限り

``` text
このファイルを以下で全体差し替え
```

の形式でファイル全体を提示する。

単純な1行置換など、全体提示のメリットがない場合のみ部分修正でよい。

------------------------------------------------------------------------

# 2. Current Development Phase

## 現在のフェーズ

**主要機能の最低限実装フェーズは一区切り。**

実装・動作確認済み：

-   Laravel / Docker 開発基盤
-   Fortify認証
-   Account CRUD
-   Category CRUD
-   通常Transaction（支出・収入）CRUD
-   Transfer CRUD
-   Opening Balance CRUD
-   TransactionRule CRUD
-   TransactionRule表示名変換
-   TransactionRuleカテゴリ入力補助
-   Summary最低限版
-   Dashboard最低限版
-   SummaryService
-   ユーザー分離
-   開発用Seeder
-   Unit / Feature Test

現在の方針：

``` text
主要機能の最低限実装
        ↓
実際に一通り使用する        ← 次
        ↓
不足機能・使いにくさを洗い出す
        ↓
必要な機能修正
        ↓
正式UI / Tailwind
        ↓
年間集計・グラフ・前月比較等の表示拡張
        ↓
会計・確定申告機能を段階的に追加
```

年間集計・グラフ等は現時点では必須ではない。
DB構造や取引の意味を変える問題が見つかった場合のみ、正式UIより前に対応する。

------------------------------------------------------------------------

# 3. Authentication

Laravel Fortifyを使用。

## 実装済み・動作確認済み

-   ユーザー登録
-   ログイン
-   ログアウト
-   Remember Me
-   認証Middleware
-   ログイン画面
-   会員登録画面
-   認証失敗・Validationメッセージ日本語化
-   ログアウト後 `/login` リダイレクト
-   未認証Dashboardアクセス時 `/login` リダイレクト

## Dashboard URL

旧 `/home` は廃止済み。

現在：

``` text
ログイン成功           → /
Dashboard              → /
route('dashboard')     → /
ログアウト             → /login
未認証で /             → /login
/home                  → 404
```

自作コード上では、

``` text
route('home')
name('home')
/home
```

を廃止し、Dashboardへ統一した。

`config/fortify.php` の

``` php
'home' => '/',
```

は Fortify の設定キー名なので正常。これは旧Home画面の残骸ではない。

`resources/views/home.blade.php` は削除済み。

認証画面のデザインは正式UIフェーズで改善する。

------------------------------------------------------------------------

# 4. Database Design

家計簿の主要DB設計は実装済み。

## users

Laravel標準ユーザー情報。 Fortify用情報を含む。

## categories

ユーザーごとのカテゴリ。

主な項目：

-   `user_id`
-   `name`

制約：

-   `(user_id, name)` unique
-   User削除時cascade

## accounts

ユーザーごとの口座・決済手段。

主な項目：

-   `user_id`
-   `name`

制約：

-   `(user_id, name)` unique
-   User削除時cascade

## transactions

家計簿の中心となる取引。

主な項目：

-   `user_id`
-   `transaction_date`
-   `type`
-   `account_id`
-   `category_id`
-   `counterparty_name`
-   `amount`
-   `withdrawal_date`
-   `expense_ratio`
-   `expense_registered`
-   `receipt_saved`

金額仕様：

-   `amount` は日本円専用の整数
-   DB型は `unsignedBigInteger`
-   Eloquent cast は `integer`
-   通常Transaction / Transferは1円以上
-   Opening Balanceのみ0円を許可
-   小数金額は扱わない
-   `expense_ratio` は `decimal(5,2)`

主なindex：

-   `user_id + transaction_date`
-   `user_id + type`
-   `user_id + counterparty_name`

## transfers

口座間振替を2つのTransactionとして関連付ける。

-   `from_transaction_id`
-   `to_transaction_id`

両方unique。 Transaction削除時cascade。

## transaction_rules

口座ごとの取引先キーワードに対する表示名・カテゴリ入力補助。

-   `user_id`
-   `account_id`
-   `keyword`
-   `display_name` nullable
-   `category_id` nullable

制約：

-   `(user_id, account_id, keyword)` unique
-   `account_id` 必須
-   同じkeywordでも口座が異なれば登録可能
-   `priority` は使用しない

------------------------------------------------------------------------

# 5. Transaction Type

`App\Enums\TransactionType`

現在：

-   `expense`
-   `income`
-   `transfer`
-   `opening_balance`

`withdrawal` は定義しない。

クレジットカード等の引落は独立したTransactionTypeではなく、`withdrawal_date`
で管理する。

------------------------------------------------------------------------

# 6. Eloquent Models

実装済み：

-   `User`
-   `Account`
-   `Category`
-   `Transaction`
-   `Transfer`
-   `TransactionRule`

主なRelation：

## User

-   hasMany Categories
-   hasMany Accounts
-   hasMany Transactions
-   hasMany TransactionRules

## Account

-   belongsTo User
-   hasMany Transactions

## Category

-   belongsTo User
-   hasMany Transactions
-   hasMany TransactionRules

## Transaction

-   belongsTo User
-   belongsTo Account
-   belongsTo Category
-   hasOne outgoing Transfer
-   hasOne incoming Transfer

## Transfer

-   belongsTo from Transaction
-   belongsTo to Transaction

## TransactionRule

-   belongsTo User
-   belongsTo Account
-   belongsTo Category

基本RelationはUnit Testあり。

------------------------------------------------------------------------

# 7. TransactionService

`app/Services/TransactionService.php`

## 実装済み

-   `createTransfer()`
-   `updateTransfer()`
-   `deleteTransfer()`

Transferは、

``` text
振替元Transaction
振替先Transaction
Transfer
```

の3要素を一体として扱う。

登録・更新・削除はDB Transactionを使用して原子的に処理する。

## バリデーション

-   振替元と振替先が同一口座 → 拒否
-   他ユーザーの口座 → 拒否
-   0以下の金額 → 拒否
-   金額は整数円
-   1円以上 → 許可
-   更新・削除時はペアTransactionの所有ユーザーを確認

### 将来の小さな改善候補

`validateTransferOwnership()`
はペアTransactionの存在とuser_idを確認しているが、 両Transactionの
`type === TransactionType::TRANSFER` を明示確認する改善余地がある。

現時点で機能・テストは正常。

------------------------------------------------------------------------

# 8. Normal Transaction

通常Transaction UIは `expense` / `income` 専用。

Transfer / Opening Balanceは専用Controller / UIから扱う。

## 入力項目

-   transaction_date：必須
-   type：expense / income
-   amount：1円以上の整数
-   account：必須
-   category：必須
-   counterparty_name：任意
-   withdrawal_date：expenseで使用
-   expense_ratio：expense / income双方で使用可能

## 重要仕様

収入でも `expense_ratio` を保持できる。

支出から収入へ変更した場合：

-   `withdrawal_date` はNULL
-   `expense_ratio` は維持

`expense_registered` / `receipt_saved`
は現在の通常入力UIでは編集しない。

------------------------------------------------------------------------

# 9. Transfer

## 完了

-   登録
-   一覧表示
-   編集
-   更新
-   削除
-   ユーザー分離
-   バリデーション
-   Feature Test

一覧では2つのTransfer Transactionを1行として表示する。

表示例：

``` text
三井住友銀行 → 現金
```

編集ではペアTransactionを同時更新する。
削除ではTransferとペアTransactionを原子的に削除する。

------------------------------------------------------------------------

# 10. Opening Balance

Opening Balanceは `TransactionType::OPENING_BALANCE`
のTransactionとして扱う。

## 完了

-   登録
-   一覧表示
-   編集
-   更新
-   削除
-   ユーザー分離
-   重複防止
-   Feature Test

仕様：

-   1口座1件
-   0円を許可
-   `category_id = null`
-   `counterparty_name = null`
-   `withdrawal_date = null`
-   `expense_ratio = 0`
-   `expense_registered = false`
-   `receipt_saved = false`

通常Transaction編集・削除機能からOpening Balanceを操作することは禁止。
専用Controller / UIから扱う。

------------------------------------------------------------------------

# 11. TransactionRule

Ruleは口座単位。

## 仕様

-   `(user_id, account_id, keyword)` unique
-   同一keywordでも口座が異なれば登録可能
-   `priority` は使用しない
-   raw `counterparty_name` はDBへそのまま保存
-   `display_name` は一覧表示時のみ利用
-   categoryは入力補助
-   ユーザーは自動選択されたcategoryを上書き可能

## 重要

TransactionRuleはサーバー側でTransactionのraw値を自動変換する仕組みではない。

例：

``` text
DB counterparty_name = ﾔﾁﾝ
Rule display_name    = 家賃
```

一覧では `家賃` と表示しても、DB上の `ﾔﾁﾝ` は維持する。

カテゴリRuleも最終保存値を強制するのではなく、作成・編集画面の入力補助として使用する。

------------------------------------------------------------------------

# 12. Summary / Aggregation

最低限版を実装済み。

## Summary画面

実装済み：

1.  月間収入
2.  月間支出
3.  月間収支
4.  月間カテゴリ別支出
5.  現在の口座残高
6.  月選択

## 集計仕様

### 月間収支

``` text
月間収支 = 月間収入 - 月間支出
```

-   `INCOME` を収入へ加算
-   `EXPENSE` を支出へ加算
-   `TRANSFER` は収入・支出に含めない
-   `OPENING_BALANCE` は収入・支出に含めない

### カテゴリ別支出

選択月の `EXPENSE` をカテゴリ単位で集計する。

### 口座残高

口座残高は選択月に依存しない現在残高。

``` text
初期残高
+ 収入
- 支出
- 振替元
+ 振替先
```

全期間のTransactionから計算する。

## SummaryService

`app/Services/SummaryService.php`

集計ロジックをControllerから分離済み。

主な処理：

-   `getMonthlySummary()`
-   `getAccountBalances()`

SummaryControllerとDashboardControllerの双方から利用する。

年間集計・グラフ等を追加する場合も、このServiceを起点に拡張する方針。

------------------------------------------------------------------------

# 13. Dashboard

最低限版を実装済み。

## URL / Route

``` text
URL        /
route名    dashboard
Controller DashboardController
View       dashboard.blade.php
```

## 表示内容

-   ログインユーザー名
-   今月の収入
-   今月の支出
-   今月の収支
-   現在の口座残高
-   各管理画面へのメニュー
-   ログアウト

集計値は `SummaryService` を利用する。

## 現在の位置づけ

Dashboardは機能確認用の最低限UI。 最終デザインではない。

------------------------------------------------------------------------

# 14. Seeder

`database/seeders/HouseholdDataSeeder.php`

`DatabaseSeeder` から呼び出し、`migrate:fresh --seed`
で開発確認用データを再構築できる。

## 開発用データ

ユーザー、Categories、Accounts、Opening
Balance、通常Transaction、Transfer、TransactionRuleを作成する。

Categories：

-   食費
-   交通費
-   家賃
-   給与
-   その他

Accounts：

-   現金
-   三井住友銀行
-   クレジットカード

Opening Balance：

-   現金：30,000円
-   三井住友銀行：500,000円

Sample Transactions：

-   家賃：100,000円
-   Amazon：3,500円
-   給与：300,000円
-   三井住友銀行 → 現金：30,000円

家賃：

-   `expense_ratio = 40`

Amazon：

-   `withdrawal_date` 設定あり

給与：

-   `expense_ratio = 100`

振替：

-   `TransactionService::createTransfer()` を利用

Sample TransactionRule：

-   対象口座：三井住友銀行
-   キーワード：`ﾔﾁﾝ`
-   表示名：`家賃`
-   適用カテゴリ：`家賃`

※
開発ユーザーの具体的な氏名・メールアドレスは、この引き継ぎファイルでは重要ではないため省略する。

------------------------------------------------------------------------

# 15. Tests

## 最新確認結果

**143 passed / 451 assertions / 1.99s**

全件PASS確認済み。

テストはHost側PHPではなくDocker内で実行する。

``` bash
docker compose exec app php artisan test > test-result.txt 2>&1
```

`test-result.txt` は最新テスト結果共有用の一時ファイル。
Git管理対象には含めない。

## Feature Tests

### AuthenticationTest

-   登録
-   ログイン
-   不正パスワード
-   ログアウト
-   認証済みDashboard
-   未認証Dashboardアクセス
-   Guest状態

### AccountManagementTest

Account CRUD、ユーザー分離、同一ユーザー内の重複名禁止。

### CategoryManagementTest

Category CRUD、ユーザー分離、同一ユーザー内の重複名禁止。

### TransactionManagementTest

通常Transaction（支出・収入）について、

-   一覧
-   登録
-   編集
-   更新
-   削除
-   ユーザー分離
-   Account / Categoryユーザー分離
-   金額整数制約
-   expense_ratio
-   withdrawal_date
-   Transfer / Opening Balanceの通常フォーム操作禁止

を検証。

### TransferManagementTest

18件。

-   登録
-   編集
-   更新
-   削除
-   同一口座拒否
-   他ユーザー口座拒否
-   他ユーザーTransfer操作拒否
-   金額制約
-   一覧1行表示

等を検証。

### OpeningBalanceManagementTest

21件。

-   登録
-   編集
-   更新
-   削除
-   0円
-   1口座1件
-   他ユーザー口座拒否
-   他ユーザーOpening Balance操作拒否
-   通常TransactionをOpening Balanceとして操作不可
-   一覧表示

等を検証。

### TransactionRuleManagementTest

17件。

-   CRUD
-   ユーザー分離
-   Account / Categoryユーザー分離
-   同一口座 + 同一keyword重複禁止
-   異なる口座では同一keyword許可
-   create画面へのRuleデータ提供
-   display_name
-   raw counterparty保持
-   別口座Rule誤適用防止

等を検証。

### SummaryManagementTest

11件。

-   Guestアクセス拒否
-   認証アクセス
-   月間収入 / 支出 / 収支
-   他月除外
-   ユーザー分離
-   Transferを月間収支から除外
-   Opening Balanceを月間収支から除外
-   カテゴリ別支出
-   口座残高
-   Transferによる残高移動
-   不正month拒否

### DashboardManagementTest

6件。

-   Guestアクセス拒否
-   認証アクセス
-   今月の収入 / 支出 / 収支表示
-   Opening Balance + Income - Expenseの口座残高
-   Transferによる口座残高反映
-   他ユーザーデータを混在させない

### ExampleTest

Root `/` が認証必須であることを確認。

## Unit Tests

-   AccountTransactionTest
-   TransactionTypeTest
-   TransferRelationshipTest
-   TransactionServiceTest
-   ExampleTest

------------------------------------------------------------------------

# 16. Current Routes

主要な家計簿ルートは `auth` middleware 配下。

## Dashboard

``` text
GET /
route('dashboard')
```

## Account

`/accounts` CRUD（show除外）

## Category

`/categories` CRUD（show除外）

## Transaction

`/transactions`

-   index
-   create
-   store
-   edit
-   update
-   destroy

通常Transactionは `expense` / `income` 専用。

## Transfer

-   create
-   store
-   edit
-   update
-   destroy

## TransactionRule

`/transaction-rules` CRUD（show除外）

## Opening Balance

-   create
-   store
-   edit
-   update
-   destroy

## Summary

``` text
GET /summary
route('summary.index')
```

## Fortify

-   `/login`
-   `/register`
-   `/logout`
-   その他Fortify提供ルート

## API

`routes/api.php`

現時点では家計簿APIは主要開発対象ではない。

------------------------------------------------------------------------

# 17. Current Views / UI

共通レイアウト：

-   `resources/views/layouts/app.blade.php`

共通レイアウトではフォーム内Enterキーによる意図しないsubmitを防止する。
`textarea` の改行は許可する。

## Authentication

-   `resources/views/auth/login.blade.php`
-   `resources/views/auth/register.blade.php`

## Dashboard

-   `resources/views/dashboard.blade.php`

## Account

-   `resources/views/accounts/index.blade.php`
-   `resources/views/accounts/create.blade.php`
-   `resources/views/accounts/edit.blade.php`

## Category

-   `resources/views/categories/index.blade.php`
-   `resources/views/categories/create.blade.php`
-   `resources/views/categories/edit.blade.php`

## Transaction

-   `resources/views/transactions/index.blade.php`
-   `resources/views/transactions/create.blade.php`
-   `resources/views/transactions/edit.blade.php`

## Transfer

-   `resources/views/transfers/create.blade.php`
-   `resources/views/transfers/edit.blade.php`

## TransactionRule

-   `resources/views/transaction-rules/index.blade.php`
-   `resources/views/transaction-rules/create.blade.php`
-   `resources/views/transaction-rules/edit.blade.php`

## Opening Balance

-   `resources/views/opening-balances/create.blade.php`
-   `resources/views/opening-balances/edit.blade.php`

## Summary

-   `resources/views/summary/index.blade.php`

## UI方針

現在の各画面は機能確認を優先した最低限UI。

正式UIフェーズでは、

-   Tailwind
-   共通ナビゲーション
-   レイアウト
-   色
-   余白
-   ボタン
-   テーブル
-   レスポンシブ
-   Dashboard情報設計

等をまとめて整える。

各画面を今の段階で個別に完成デザインへ寄せすぎない。

------------------------------------------------------------------------

# 18. Not Yet Implemented / Deferred

## Expense

DB項目：

-   `expense_ratio`
-   `expense_registered`

は存在する。

通常Transactionでは `expense_ratio` 入力・更新まで対応済み。

未実装：

-   会計上の経費計算
-   経費登録処理
-   専用UI

## Receipt

DB項目：

-   `receipt_saved`

は存在する。

未実装：

-   領収書保存
-   ファイル管理
-   専用UI

## Transaction Detail

独立したshow画面は未実装。 必要性は実利用・正式UI設計で判断する。

## 表示・レポート拡張

現時点では後回し：

-   年間集計
-   グラフ
-   前月比較
-   カテゴリ比率
-   期間フィルタ拡張
-   口座推移
-   最近の取引
-   Dashboardカード追加
-   その他詳細レポート

これらは現在のTransaction /
SummaryServiceを利用して後から追加可能と判断している。

------------------------------------------------------------------------

# 19. Important Design Decisions

## Transaction中心設計

収入・支出・振替・初期残高をTransactionとして扱う。

## 振替

振替は、

``` text
振替元Transaction
振替先Transaction
Transfer
```

の3要素。

一覧では1行表示。 登録・編集・削除では必ず一体として扱う。

## Opening Balance

`TransactionType::OPENING_BALANCE` のTransaction。

1口座1件。 0円を許可。

## TransactionRule

Ruleは口座単位。

raw `counterparty_name` を保存し、`display_name` は表示時のみ使用。
categoryは入力補助。

## Withdrawal

独立したTransactionTypeにはせず `withdrawal_date` で管理。

## User isolation

Account / Category / Transaction / TransactionRuleはユーザー単位。

Controller / Service / 集計で他ユーザーのデータを混在させない。

## 金額

日本円専用の整数円。

-   通常Transaction / Transfer：1円以上
-   Opening Balance：0円以上
-   DB：`unsignedBigInteger`
-   Model：integer cast

## Expense Ratio

支出だけでなく収入でも保持可能。

収入へ変更した場合：

-   `withdrawal_date` → NULL
-   `expense_ratio` → 維持

## 集計

月間収支と現在残高は意味を分離する。

-   月間収支：選択月のINCOME / EXPENSE
-   現在残高：全期間のTransaction

Transfer / Opening Balanceを月間収支へ混ぜない。

## Dashboard

`/` をDashboardとする。 旧 `/home` は廃止。

内部route名も `dashboard` に統一する。

## 共通UI

Blade + Tailwind方針。 Reactは採用しない。

現在は機能優先。 正式UIは主要ワークフロー確認後にまとめて実装する。

## Localization

-   locale：`ja`
-   fallback locale：`ja`
-   faker locale：`ja_JP`
-   timezone：`Asia/Tokyo`
-   Fortify認証エラー / Validation日本語化

------------------------------------------------------------------------

# 20. Current Relevant File Structure

``` text
app/
├── Actions/Fortify/
├── Enums/
│   └── TransactionType.php
├── Http/
│   ├── Controllers/
│   │   ├── AccountController.php
│   │   ├── CategoryController.php
│   │   ├── DashboardController.php
│   │   ├── OpeningBalanceController.php
│   │   ├── SummaryController.php
│   │   ├── TransactionController.php
│   │   ├── TransactionRuleController.php
│   │   └── TransferController.php
│   └── Responses/
│       └── LogoutResponse.php
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
    ├── SummaryService.php
    └── TransactionService.php

database/
├── migrations/
├── seeders/
│   ├── DatabaseSeeder.php
│   └── HouseholdDataSeeder.php
└── factories/
    └── UserFactory.php

lang/
└── ja/
    ├── auth.php
    └── validation.php

resources/views/
├── layouts/
│   └── app.blade.php
├── auth/
│   ├── login.blade.php
│   └── register.blade.php
├── accounts/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── categories/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── transactions/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── transfers/
│   ├── create.blade.php
│   └── edit.blade.php
├── transaction-rules/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── opening-balances/
│   ├── create.blade.php
│   └── edit.blade.php
├── summary/
│   └── index.blade.php
└── dashboard.blade.php

tests/
├── Feature/
│   ├── AccountManagementTest.php
│   ├── AuthenticationTest.php
│   ├── CategoryManagementTest.php
│   ├── DashboardManagementTest.php
│   ├── OpeningBalanceManagementTest.php
│   ├── SummaryManagementTest.php
│   ├── TransactionManagementTest.php
│   ├── TransactionRuleManagementTest.php
│   ├── TransferManagementTest.php
│   └── ExampleTest.php
└── Unit/
    ├── AccountTransactionTest.php
    ├── ExampleTest.php
    ├── TransactionServiceTest.php
    ├── TransactionTypeTest.php
    └── TransferRelationshipTest.php
```

------------------------------------------------------------------------

# 21. Future Accounting Expansion Plan

## 基本方針

**現時点では既存の家計簿テーブルを会計前提に大規模変更しない。**

現在の `Transaction` を「家計簿上で発生した1件の取引」として維持し、
会計機能が必要になった段階で関連テーブル・Model・Serviceを追加する。

現時点で会計機能のために `transactions`
へ必須追加するカラムはないと判断している。

## 現在の設計を維持するもの

-   Transactionを家計簿取引の中心として維持
-   `withdrawal_date` をクレジットカード等の引落日として利用
-   クレジットカード専用TransactionTypeは追加しない
-   家計簿Categoryと会計上の勘定科目を分離
-   現在の家計簿カテゴリ運用を維持
-   家計簿では複数用途購入時に主要カテゴリへまとめる現在方針を維持
-   会計側で必要に応じて明細分割する

## 将来候補

### 外部データ取り込み

-   クレジットカード
-   銀行
-   スクレイピング

候補テーブル：

-   `TransactionImport`

外部サービス、外部取引ID、取得日時等を管理し、重複取り込みを防止する。

### 取引内訳

候補：

-   `TransactionItem`

家計簿上の1Transactionから会計上の複数内訳へ展開可能にする。

### 会計用勘定科目

家計簿Categoryとは分離する。

### Expense

現在の、

-   `expense_ratio`
-   `expense_registered`

を起点に、必要に応じて会計側へ拡張する。

### Fixed Asset

候補：

``` text
Transaction
    ↓
FixedAsset
    ↓
Depreciation
```

### 複式簿記・仕訳

候補：

``` text
Transaction
    ↓
JournalEntry
    ↓
JournalEntryLine
```

Transaction自体を仕訳にはしない。

### 年度管理

会計機能側でFiscalYear等を扱う。
Transactionへ会計年度を固定保存する方針にはしない。

## 将来像

``` text
既存家計簿
├─ Account
├─ Category
├─ Transaction
├─ Transfer
└─ TransactionRule

        ↓ 拡張

会計機能
├─ TransactionImport
├─ TransactionItem
├─ AccountingAccount
├─ Expense
├─ FixedAsset
├─ Depreciation
├─ FiscalYear
├─ JournalEntry
└─ JournalEntryLine

        ↓

青色申告
        ↓
e-Tax提出用データ生成
```

## Current Accounting Readiness

会計機能追加を前提としても、現時点で既存 `transactions`
に必須追加するカラムはない。

現在の家計簿アプリ開発をそのまま継続してよい。

------------------------------------------------------------------------

# 22. Git / Last Known State

## 現在確認できていること

-   branchは過去確認時 `main`
-   以前のまとまった変更についてcommit / push用コマンドは案内済み
-   その後のcommit / push結果は現在の会話では確認できていない
-   したがって最新変更をGitへpush済みとは断定しない

現在の実装状態には少なくとも、

-   Transfer CRUD
-   Opening Balance CRUD
-   Summary
-   SummaryService
-   Dashboard
-   `/home` 廃止 / `route('dashboard')` 統一
-   DashboardManagementTest
-   AuthenticationTest Dashboard対応

が含まれる。

最新テスト：

**143 passed / 451 assertions / 1.99s**

次にGitへ反映する場合は、変更内容確認後にcommit / pushする。

------------------------------------------------------------------------

# 23. Current Progress

## 完了

-   [x] Laravelプロジェクト基盤
-   [x] Docker / 開発環境
-   [x] Git管理
-   [x] Laravel Fortify認証
-   [x] 会員登録
-   [x] ログイン
-   [x] ログアウト
-   [x] Remember Me
-   [x] 認証Middleware
-   [x] 認証・Validation日本語化
-   [x] 日本向けtimezone / locale
-   [x] 共通Bladeレイアウト
-   [x] Enterキーによる意図しないフォームsubmit防止
-   [x] 家計簿DB基盤
-   [x] Eloquent Model / Relation
-   [x] TransactionType
-   [x] Transaction金額JPY整数化
-   [x] Account CRUD
-   [x] Category CRUD
-   [x] Transaction支出 / 収入 CRUD
-   [x] Transfer登録
-   [x] Transfer一覧1行表示
-   [x] Transfer編集 / 更新
-   [x] Transfer削除
-   [x] TransactionRule CRUD
-   [x] TransactionRule表示名変換
-   [x] TransactionRuleカテゴリ入力補助
-   [x] Opening Balance登録
-   [x] Opening Balance一覧
-   [x] Opening Balance編集 / 更新
-   [x] Opening Balance削除
-   [x] Opening Balance同一口座重複防止
-   [x] 月間収入 / 支出 / 収支
-   [x] 月間カテゴリ別支出
-   [x] 現在口座残高
-   [x] SummaryService
-   [x] Summary最低限UI
-   [x] Dashboard最低限UI
-   [x] `/` Dashboard化
-   [x] `/home` 廃止
-   [x] `route('dashboard')` 統一
-   [x] ユーザー分離
-   [x] 開発用Seeder
-   [x] `migrate:fresh --seed` 動作確認
-   [x] `test-result.txt` によるテスト結果共有
-   [x] Unit / Feature Test
-   [x] 143 tests / 451 assertions PASS

## 次フェーズ

-   [ ] 実際に一通り使用する
-   [ ] 入力・編集・削除・集計のワークフロー確認
-   [ ] 不足機能の洗い出し
-   [ ] 使いにくい部分の洗い出し
-   [ ] 必要な機能修正
-   [ ] 正式UI / Tailwind
-   [ ] レスポンシブ対応

## 後回し可能な表示拡張

-   [ ] 年間集計
-   [ ] グラフ
-   [ ] 前月比較
-   [ ] カテゴリ比率
-   [ ] 期間フィルタ拡張
-   [ ] 口座推移
-   [ ] 最近の取引
-   [ ] Dashboard情報拡張

## 将来拡張

-   [ ] Expense機能
-   [ ] Receipt管理
-   [ ] クレジットカード / 銀行等のスクレイピング
-   [ ] TransactionImport
-   [ ] TransactionItem
-   [ ] 会計用勘定科目
-   [ ] Expense会計拡張
-   [ ] FixedAsset
-   [ ] Depreciation
-   [ ] FiscalYear
-   [ ] JournalEntry / JournalEntryLine
-   [ ] 青色申告対応
-   [ ] e-Tax提出用データ生成

------------------------------------------------------------------------

# 24. Recommended Next Implementation

現在は新しい機能をすぐ追加するより、まず現在の最低限版を実際に使う。

推奨：

``` text
migrate:fresh --seed 等で確認データを準備
        ↓
Dashboard
        ↓
支出・収入登録
        ↓
振替登録・編集・削除
        ↓
初期残高登録・編集・削除
        ↓
TransactionRule
        ↓
Transaction一覧
        ↓
Summary
        ↓
一連の操作で不足をメモ
```

確認ポイント：

-   欲しい情報が入力できるか
-   編集時に困る項目がないか
-   一覧で判断に必要な情報が足りるか
-   登録導線が自然か
-   Summary / Dashboardで欲しい数字が取れるか
-   実運用前にDB構造変更が必要な要件がないか

ここで見つかった問題のうち、**データ構造・意味・ワークフローに関わるものを先に修正**する。

表示だけの問題は正式UIフェーズへ回してよい。

------------------------------------------------------------------------

# 25. Current State Summary

現在の状態を一言で表すと：

**「認証から主要な家計簿CRUD、振替、初期残高、取引ルール、月間集計、口座残高、Dashboardまで最低限版が完成し、143
tests / 451 assertions
が全PASS。次は実利用ベースで不足機能を洗い出し、その後正式UIへ進む段階。」**

特に重要：

-   Dashboardは `/`
-   `/home` は廃止
-   route名は `dashboard`
-   ログアウト後 `/login`
-   Account / Category / Transaction CRUD完了
-   Transfer CRUD完了
-   Opening Balance CRUD完了
-   TransactionRule CRUD完了
-   raw counterpartyを保持
-   月間収支ではTransfer / Opening Balanceを除外
-   口座残高ではTransfer / Opening Balanceを正しく反映
-   SummaryServiceで集計ロジック共通化
-   正式UIはまだ
-   年間集計・グラフ等は後回し可能
-   会計拡張のためのTransaction必須カラム追加は現時点で不要
-   最新テストは **143 passed / 451 assertions / 1.99s**
-   最新Git push状態は未確認

------------------------------------------------------------------------

# 26. Handoff Rule

次回このファイルが共有された場合は、この状態から続きの実装を開始する。

ユーザーが、

> 続きから実装始めたい

と言った場合は、原則として
**実利用によるワークフロー確認・不足機能洗い出し** から開始する。

ただし現在の会話で、より新しい具体的な実装対象が決まっている場合はそちらを優先する。

実装・テスト・動作確認が一区切りついた場合は `PROJECT_STATUS.md`
更新を提案する。

------------------------------------------------------------------------

# End of Project Status
