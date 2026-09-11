<?php

return [

    'required' => ':attributeは必須です。',
    'string' => ':attributeは文字列で入力してください。',
    'integer' => ':attributeは整数で入力してください。',
    'numeric' => ':attributeは数値で入力してください。',
    'date' => ':attributeは正しい日付を入力してください。',
    'email' => ':attributeには正しいメールアドレスを入力してください。',
    'confirmed' => ':attributeと確認用の入力が一致していません。',
    'unique' => 'この:attributeはすでに使用されています。',
    'exists' => '選択された:attributeが正しくありません。',
    'different' => ':attributeには:otherとは異なる値を指定してください。',

    'max' => [
        'numeric' => ':attributeは:max以下で入力してください。',
        'string' => ':attributeは:max文字以内で入力してください。',
    ],

    'min' => [
        'numeric' => ':attributeは:min以上で入力してください。',
        'string' => ':attributeは:min文字以上で入力してください。',
    ],

    'between' => [
        'numeric' => ':attributeは:minから:maxの間で入力してください。',
    ],

    'in' => '選択された:attributeが正しくありません。',

    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード（確認）',

        'transaction_date' => '取引日',
        'type' => '種別',
        'account_id' => '口座',
        'category_id' => 'カテゴリ',
        'counterparty_name' => '取引先名',
        'amount' => '金額',
        'withdrawal_date' => '引落日',
        'expense_ratio' => '経費割合',

        'from_account_id' => '振替元口座',
        'to_account_id' => '振替先口座',
    ],

];