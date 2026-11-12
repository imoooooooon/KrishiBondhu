<?php

/*
| Bangla validation messages. Laravel's English messages are the fallback, so
| only the rules our forms use are listed. Add a rule here when your form uses it.
| Field names (:attribute) are translated in each Form Request's attributes() via lang/bn.json.
*/
return [
    'array' => ':attribute সঠিকভাবে বেছে নিন।',
    'confirmed' => ':attribute দুইবার একই রকম লিখুন।',
    'distinct' => ':attribute একাধিকবার দেওয়া হয়েছে।',
    'email' => 'সঠিক :attribute ঠিকানা দিন।',
    'exists' => 'বেছে নেওয়া :attribute সঠিক নয়।',
    'in' => 'বেছে নেওয়া :attribute সঠিক নয়।',
    'max' => [
        'string' => ':attribute :max অক্ষরের বেশি হতে পারবে না।',
    ],
    'min' => [
        'array' => 'অন্তত :min টি :attribute বেছে নিন।',
        'string' => ':attribute কমপক্ষে :min অক্ষরের হতে হবে।',
    ],
    'regex' => ':attribute সঠিক নয়।',
    'required' => ':attribute দিতে হবে।',
    'string' => ':attribute লেখা হতে হবে।',
    'unique' => 'এই :attribute আগেই ব্যবহার করা হয়েছে।',
];
