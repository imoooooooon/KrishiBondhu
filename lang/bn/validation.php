<?php

/*
| Bangla validation messages. Laravel's English messages are the fallback, so
| only the rules our forms use are listed. Add a rule here when your form uses it.
| Field names (:attribute) are translated in each Form Request's attributes() via lang/bn.json.
*/
return [
    'after_or_equal' => ':attribute :date বা তার পরের তারিখ হতে হবে।',
    'array' => ':attribute সঠিকভাবে বেছে নিন।',
    'confirmed' => ':attribute দুইবার একই রকম লিখুন।',
    'date_format' => ':attribute সঠিক তারিখ নয়।',
    'decimal' => ':attribute দশমিকের পরে সর্বোচ্চ :decimal ঘর হতে পারবে।',
    'distinct' => ':attribute একাধিকবার দেওয়া হয়েছে।',
    'email' => 'সঠিক :attribute ঠিকানা দিন।',
    'exists' => 'বেছে নেওয়া :attribute সঠিক নয়।',
    'gt' => [
        'numeric' => ':attribute :value এর বেশি হতে হবে।',
    ],
    'in' => 'বেছে নেওয়া :attribute সঠিক নয়।',
    'integer' => ':attribute পূর্ণ সংখ্যা হতে হবে।',
    'max' => [
        'numeric' => ':attribute :max এর বেশি হতে পারবে না।',
        'string' => ':attribute :max অক্ষরের বেশি হতে পারবে না।',
    ],
    'min' => [
        'array' => 'অন্তত :min টি :attribute বেছে নিন।',
        'numeric' => ':attribute কমপক্ষে :min হতে হবে।',
        'string' => ':attribute কমপক্ষে :min অক্ষরের হতে হবে।',
    ],
    'numeric' => ':attribute সংখ্যা হতে হবে।',
    'regex' => ':attribute সঠিক নয়।',
    'required' => ':attribute দিতে হবে।',
    'required_if' => ':attribute দিতে হবে।',
    'required_with' => ':attribute দিতে হবে।',
    'string' => ':attribute লেখা হতে হবে।',
    'unique' => 'এই :attribute আগেই ব্যবহার করা হয়েছে।',
];
