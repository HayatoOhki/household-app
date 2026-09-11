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
│   ├─ 口座管理        ← 実装済み・動作確認済み
│   ├─ カテゴリ管理    ← 実装済み・動作確認済み
│   ├─ 取引管理        ← 支出/収入CRUD実装済み・動作確認済み
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
**認証基盤・家計簿DB/Model/Service/Test基盤・Account/Category管理・通常Transaction（支出/収入）CRUDまで完成。次はTransfer UIを実装する段階。**

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

**認証基盤 + 家計簿バックエンド基盤 + 基本CRUD完成。**

Account / Category管理と、通常Transaction（支出・収入）の登録・一覧・編集・削除まで実装・動作確認済み。

現在は次の家計簿機能である **Transfer UI** の実装へ進む段階。

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
- ログアウト後は `/login` にリダイレクトされる
- 未認証で `/home` にアクセスすると `/login` にリダイレクトされる
- 認証失敗・Validationメッセージは日本語化済み

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

金額仕様：

- `amount` は日本円専用の整数
- DB型は `unsignedBigInteger`
- Eloquent castは `integer`
- HTTP入力は `integer|min:1`
- 小数金額は許可しない
- `expense_ratio` は小数を許容するため `decimal(5,2)` を維持

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
- 金額は日本円の整数として扱う
- 1円以上 → 許可

## テスト済み

`tests/Unit/TransactionServiceTest.php`

正常系・異常系・DB登録結果・属性を検証済み。

---

# 10. Seeder

`database/seeders/HouseholdDataSeeder.php`

`DatabaseSeeder` から `HouseholdDataSeeder` を呼び出し、開発確認用の家計簿データ一式を作成する。

開発用ユーザー：

- 名前：`大木　颯人`
- メールアドレス：`hayato1114.drums@gmail.com`
- パスワード：`password`

`migrate:fresh --seed` で開発確認用データを再構築できる。

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

給与：

- `expense_ratio = 100`

振替：

- `TransactionService::createTransfer()` を利用して作成

## Sample TransactionRule

キーワード：

`ﾔﾁﾝ`

適用カテゴリ：

`家賃`

priority：

`100`

---

# 11. Tests

最新確認結果：

**70 passed / 186 assertions**

## Feature

### AuthenticationTest

- 登録
- ログイン
- 不正パスワード
- ログアウト（`/login` へリダイレクト）
- 認証済みHome
- 未認証Homeアクセス
- Guest状態

### AccountManagementTest

Account CRUD、ユーザー分離、同一ユーザー内の重複名禁止を検証。

### CategoryManagementTest

Category CRUD、ユーザー分離、同一ユーザー内の重複名禁止を検証。

### TransactionManagementTest

通常Transaction（支出・収入）について以下を検証。

- 自分のTransactionのみ一覧表示
- 支出登録
- 収入登録
- 収入でも `expense_ratio` を保持
- 収入では `withdrawal_date` をNULL化
- 通常フォームからTransfer / Opening Balanceを登録不可
- 他ユーザーのAccount / Categoryを利用不可
- `amount >= 1`
- 小数金額を拒否
- `expense_ratio` は0〜100
- 編集画面
- 更新
- 他ユーザーTransactionの編集・更新不可
- 更新時のAccount / Categoryユーザー分離
- 支出→収入変更時の引落日クリア
- Transfer / Opening Balanceを通常編集不可
- 削除
- 他ユーザーTransactionの削除不可

## Unit

### AccountTransactionTest

Account ↔ Transaction Relationを検証。

### TransactionTypeTest

TransactionTypeの定義とvalue変換を検証。

### TransferRelationshipTest

Transfer ↔ Transaction Relationおよび外部キー名を検証。

### TransactionServiceTest

振替Serviceについて以下を検証。

- 2件のTransaction + Transfer生成
- 同一口座拒否
- 他ユーザー口座拒否
- 0円拒否
- 負数拒否
- 1円を最小正常値として許可
- 生成Transactionの属性

---

# 12. Current Routes

## Web

`routes/web.php`

現在の主要ルート：

- `/`
- `/home`
- `/accounts` 系CRUD
- `/categories` 系CRUD
- `/transactions` 系（index / create / store / edit / update / destroy）

家計簿管理ルートは `auth` middleware配下。

Transactionの通常CRUDは `expense` / `income` 専用。
`transfer` / `opening_balance` は専用機能で扱う。

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

共通レイアウト：

- `resources/views/layouts/app.blade.php`

共通レイアウトでは、フォーム内のEnterキーによる意図しないsubmitをアプリ全体で防止する。
`textarea` の改行は許可する。

## Authentication

- `resources/views/auth/login.blade.php`
- `resources/views/auth/register.blade.php`

認証機能は完成済み。画面デザイン自体は今後の正式UIフェーズで改善する。

## Home

- `resources/views/home.blade.php`

現在はDashboardへの入口となる暫定Home。

## Account

- `resources/views/accounts/index.blade.php`
- `resources/views/accounts/create.blade.php`
- `resources/views/accounts/edit.blade.php`

CRUD実装・動作確認済み。

## Category

- `resources/views/categories/index.blade.php`
- `resources/views/categories/create.blade.php`
- `resources/views/categories/edit.blade.php`

CRUD実装・動作確認済み。

## Transaction

- `resources/views/transactions/index.blade.php`
- `resources/views/transactions/create.blade.php`
- `resources/views/transactions/edit.blade.php`

通常Transaction（支出・収入）のCRUD実装・動作確認済み。

金額入力は整数円。
収入では引落日を非表示・クリアするが、経費割合は収入でも利用可能。

---

# 14. Not Yet Implemented

## Transfer

`TransactionService::createTransfer()` は実装・テスト済み。

未実装：

- Controller
- 入力画面
- 一覧等のUI
- 実際の画面からの振替処理

## Transaction Detail

通常Transactionの一覧・登録・編集・削除は実装済み。
独立した詳細画面（show）は現時点では未実装。必要性は今後のUI設計で判断する。

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

通常Transaction画面では `expense_ratio` の入力・更新まで対応済み。

未実装：

- 会計上の経費計算
- 経費登録処理
- 専用UI

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
Account / Category管理                         ← 完了
        ↓
Transaction登録（支出・収入）                  ← 完了
        ↓
Transaction一覧・編集・削除                    ← 完了
        ↓
Transfer UI                                    ← 次
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

※ 現時点では家計簿機能を最優先で完成させる。
※ 将来的に会計・確定申告機能を追加するが、現段階で既存家計簿DBを
   会計前提へ大規模変更する必要はない。
```

ただし、実装中に依存関係や設計上の理由があれば順序変更可能。

次の実装対象は、

**Transfer UI**

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

## 金額

家計簿は日本円専用として、Transactionの `amount` は整数円で管理する。

- 小数金額は扱わない
- 1円以上
- DBは `unsignedBigInteger`
- Modelは `integer` cast

## Expense Ratio

`expense_ratio` は支出だけでなく収入でも保持できる。

収入へ変更した場合：

- `withdrawal_date` はNULLにする
- `expense_ratio` はクリアしない

## 共通UI

Blade画面は `resources/views/layouts/app.blade.php` を共通レイアウトとして利用する。

フォーム内のEnterキーによる意図しないsubmitを共通処理で防止する。

## Localization

- locale：`ja`
- fallback locale：`ja`
- faker locale：`ja_JP`
- timezone：`Asia/Tokyo`
- Fortify認証エラーとValidationメッセージを日本語化

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
├── Enums/
│   └── TransactionType.php
├── Http/
│   ├── Controllers/
│   │   ├── AccountController.php
│   │   ├── CategoryController.php
│   │   ├── Controller.php
│   │   └── TransactionController.php
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
├── home.blade.php
└── welcome.blade.php

tests/
├── Feature/
│   ├── AccountManagementTest.php
│   ├── AuthenticationTest.php
│   ├── CategoryManagementTest.php
│   ├── TransactionManagementTest.php
│   └── ExampleTest.php
└── Unit/
    ├── AccountTransactionTest.php
    ├── ExampleTest.php
    ├── TransactionServiceTest.php
    ├── TransactionTypeTest.php
    └── TransferRelationshipTest.php
```

---

# 18. Future Accounting Expansion Plan

今回追加された将来要件：

```text
フリーランスの確定申告が必要
        ↓
家計簿と確定申告を同じアプリで管理したい
        ↓
家計簿アプリを優先して完成
        ↓
次回確定申告までに会計機能を追加
        ↓
最終的には青色申告・e-Tax提出用データ生成を目標とする
```

## 基本方針

**現時点では既存の家計簿テーブルを会計前提に大規模変更しない。**

現在の `Transaction` を「家計簿上で発生した1件の取引」として維持し、
会計機能が必要になった段階で関連テーブル・Model・Serviceを追加して拡張する。

現時点で、会計機能のために `transactions` へ必須で追加するカラムはないと判断している。

### 現在の設計をそのまま利用するもの

- `Transaction` を家計簿取引の中心として維持
- `withdrawal_date` はクレジットカード等の引落日として継続利用
- クレジットカード専用の `TransactionType` は追加しない
- 家計簿の `Category` と会計上の勘定科目は分離する
- 現在の家計簿カテゴリ運用は変更しない
  - 例：食品と日用品をまとめて購入した場合、金額比率が大きいカテゴリに分類する現在のルールを維持

### 将来追加する可能性がある機能・テーブル

#### 外部データ取り込み

クレジットカード・銀行等のスクレイピングによるデータ取得を予定。

候補：

```text
transaction_imports
```

外部サービス、外部取引ID、取得日時などを管理し、
同一明細の重複取り込みを防止する。

`Transaction` 自体は家計簿取引として維持する。

#### 取引内訳

1件のクレジットカード明細に複数の商品・用途が含まれる場合、
将来的に必要に応じて `TransactionItem` 等を追加する。

例：

```text
クレジットカード明細 180,000円
        ↓
Transaction
        ↓
TransactionItem
├─ PC              150,000円
├─ キーボード        20,000円
└─ マウス            10,000円
```

家計簿上の1Transactionと、会計上の複数の内訳を分離して扱えるようにする。

#### 会計カテゴリ

家計簿 `Category` と会計上の勘定科目を分離する。

将来的に、

```text
Category
    ↓
会計上の勘定科目へのマッピング
```

を追加する。

#### Expense

現在の `transactions` にある、

- `expense_ratio`
- `expense_registered`

は現時点ではそのまま利用する。

会計機能拡張時に必要であれば `Expense` テーブルへ分離し、
Transactionとリレーションする。

#### Fixed Asset

将来的にTransactionから「固定資産として登録」できる機能を追加する。

候補：

```text
Transaction
    ↓
FixedAsset
    ↓
Depreciation
```

固定資産・減価償却を複数年度にわたって管理する。

#### 複式簿記・仕訳

会計機能追加時に、Transactionとは別に仕訳を管理する。

想定：

```text
Transaction
    ↓
会計上の処理
    ↓
JournalEntry
    ↓
JournalEntryLine
```

Transactionをそのまま仕訳として扱わない。

将来的に総勘定元帳・試算表・青色申告関連帳票等へ展開する。

#### 年度管理

Transaction自体に会計年度を固定保存するのではなく、
会計機能側で年度を扱う。

年をまたぐ取引や減価償却など、複数年度に影響する処理は会計側で管理する。

## 会計機能追加時の想定

```text
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

## 重要な設計判断

- 家計簿と会計で必要な情報の粒度は異なる
- 家計簿のCategoryを会計上の勘定科目に置き換えない
- クレジットカード明細1件＝会計上の1仕訳とは限らない
- 1件のTransactionから複数のTransactionItemや仕訳行へ展開できる設計を将来採用する
- 会計機能は家計簿機能完成後に段階的に追加する
- スクレイピングのメンテナンスは自分専用アプリであり、仕様・技術を理解したうえで対応するため、現時点では大きな制約とはしない

## Current Accounting Readiness

**会計機能追加を前提としても、現時点で既存 `transactions` に必須で追加するカラムはない。**

したがって、現在の家計簿アプリ開発をそのまま継続してよい。

会計機能を実装する際に、必要なテーブル・Model・Service・UIを追加する。

---

# 18. Git / Last Known State

## 現在確認済み状態

- branch：`main`
- 実装開始前の `origin/main` とローカル `main` は同期済み
- 今回の変更はまだcommit前
- `git diff --check` はエラーなし
- Account / Category / Transaction CRUD、日本語化、共通レイアウト、Seeder、テスト等がworking treeに存在する
- 最新テスト結果：**70 passed / 186 assertions**

この `PROJECT_STATUS.md` 更新後、今回のまとまった変更をcommit / pushする予定。

---

# 19. Current State Summary

現在の状態を一言で表すと：

**「認証・家計簿バックエンド基盤に加え、Account / Category管理と通常Transaction（支出・収入）CRUDまで完成。JPY整数金額、日本語化、共通レイアウト、Seeder、ユーザー分離とテストも整備済み。次は既存TransactionServiceを利用したTransfer UIを実装する。」**

特に重要なのは、

- 認証は実装・動作確認済み
- ログアウト後は `/login`
- 認証・Validationメッセージは日本語
- Account CRUD実装・動作確認済み
- Category CRUD実装・動作確認済み
- Transactionの支出・収入CRUD実装・動作確認済み
- Transaction金額は日本円の整数
- 収入でも `expense_ratio` を保持可能
- 収入では `withdrawal_date` をNULL化
- Transfer Serviceは実装・テスト済み
- Transfer / Opening Balanceは通常Transactionフォームから登録・編集不可
- ユーザー間のデータ分離をController / Validation / Service / Testで確認
- `migrate:fresh --seed` で開発用サンプルデータを復元可能
- 最新テストは **70 passed / 186 assertions**
- 正式Dashboard・最終デザインは未実装
- 次はTransfer UI

という状態。

---

# 20. Current Progress

## 全体進捗

現在は**家計簿基盤 + 基本CRUD完成 → Transfer UI実装直前**。

### 完了

- [x] Laravelプロジェクト基盤
- [x] Docker / 開発環境
- [x] Git管理
- [x] Laravel Fortify認証
- [x] 会員登録
- [x] ログイン
- [x] ログアウト
- [x] Remember Me
- [x] 認証Middleware
- [x] 認証・Validation日本語化
- [x] 日本向けtimezone / locale
- [x] 共通Bladeレイアウト
- [x] Enterキーによる意図しないフォームsubmit防止
- [x] 家計簿DB基盤
- [x] Eloquent Model / Relation
- [x] TransactionType
- [x] Transaction金額のJPY整数化
- [x] Transfer Service
- [x] Account CRUD
- [x] Category CRUD
- [x] Transaction支出 / 収入登録
- [x] Transaction一覧
- [x] Transaction編集 / 更新
- [x] Transaction削除
- [x] Account / Category / Transactionのユーザー分離
- [x] 開発用Seeder
- [x] `migrate:fresh --seed` 動作確認
- [x] Unit / Feature Test
- [x] 70 tests / 186 assertions PASS

### 未実装

- [ ] Transfer UI
- [ ] TransactionRule自動適用
- [ ] Opening Balance
- [ ] Expense機能
- [ ] Receipt管理
- [ ] 集計
- [ ] Dashboard
- [ ] 正式UI / デザイン改善

### 将来拡張

- [ ] クレジットカード / 銀行等のスクレイピング
- [ ] TransactionImport
- [ ] TransactionItem
- [ ] 会計用勘定科目
- [ ] Expense会計拡張
- [ ] FixedAsset
- [ ] Depreciation
- [ ] FiscalYear
- [ ] JournalEntry / JournalEntryLine
- [ ] 青色申告対応
- [ ] e-Tax提出用データ生成

## 現時点の開発判断

**会計・確定申告機能を将来追加する前提でも、現在の家計簿DBを先に大きく改修する必要はない。**

まず家計簿アプリを完成させ、その後に会計機能を段階的に追加する。

次の実装対象は **Transfer UI**。

---

# 21. Handoff Rule

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
