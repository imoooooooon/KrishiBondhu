<?php

/*
| F6 demo fixtures, used by `php artisan kb:demo-cattle seed`. Development only. Everything here is fictional:
| names, ages, parents and health notes are illustrative and say nothing about the animals in the photos.
| Days are counted back from the first run's reference date, so a rerun never shifts birthdays or history.
| Photo credits: docs/demo-assets.md. All photos are free Unsplash photos (https://unsplash.com/license).
*/
return [
    // Demo accounts (phone => label). Karim, Salma, Hamid and Nasrin are the same demo accounts as the F1/F20 demos.
    'accounts' => [
        '01700000001' => ['name' => 'Karim Mia (demo farmer)', 'label' => 'Demo Farmer A', 'cattle' => 6],
        '01700000002' => ['name' => 'Salma Khatun (demo farmer and worker)', 'label' => 'Demo Farmer B', 'cattle' => 4],
        '01700000004' => ['name' => 'Abdul Hamid (demo farmer)', 'label' => 'Demo Farmer C', 'cattle' => 2],
        '01700000005' => ['name' => 'Nasrin Akter (demo farmer)', 'label' => 'Demo Farmer D (no cattle: empty state)', 'cattle' => 0],
    ],

    // Unsplash photo page id => [CDN image id, photographer]
    'photos' => [
        '490-753Sa6c' => ['photo-1678986992000-2697f18027a4', 'Tino Rischawy'],
        'bCiXtXP8Jfs' => ['photo-1570936207244-0e33d26e790f', 'Brad R'],
        '0T4QkPg0k7Y' => ['photo-1573611236714-1fe596de1fd3', 'Teagan Turner'],
        'y0dSeflqUWo' => ['photo-1595664652035-0956d50e0311', 'Anand Thakur'],
        'C7Ot7E1Jvjw' => ['photo-1776881184163-271918c61276', 'Vinícius Costa'],
        'Zv4CKvXe-A8' => ['photo-1723456607904-7747a6020c8f', 'Andrei R. Popescu'],
        'psnkLaX2wbA' => ['photo-1547158732-1de2d876e497', 'Sunnie-Lee Davison'],
        'bX03VSFcOxI' => ['photo-1714542467280-ab917e9f5d89', 'Francesco Ungaro'],
        'YMiBhFC8GQY' => ['photo-1610301298807-50a7529d6111', 'Clara Metivier Beukes'],
        'uumZxpJQRmI' => ['photo-1569262383272-7491cf634b02', 'Giordano Rossoni'],
        'pC6VTUligkU' => ['photo-1723720487169-5141eb66f08d', 'Andrei R. Popescu'],
        'BfwqkjSMzoE' => ['photo-1640876751909-bb2f8f5eb60b', 'Riccardo Vicidomini Varotto'],
    ],

    // key => owner phone, photo, profile, age ['exact', days before reference] | ['estimated', months] | ['unknown'],
    //        history [type, title, days before reference or null (undated), details], archived?
    'cattle' => [
        'A-01' => ['01700000001', '490-753Sa6c', ['nickname' => 'Moni', 'farm_tag' => 'T-01', 'sex' => 'female', 'breed_description' => 'Holstein Friesian cross',
            'mother_details' => 'Tag T-00, bought from the Savar dairy farm', 'father_details' => 'AI bull, upazila livestock office'], ['exact', 1650], [
                ['vaccination', 'FMD vaccine', 120, 'Demo: vaccine recorded in the farmer’s notebook.'],
                ['vet_visit', 'Routine check by the upazila vet', 40, 'Demo: routine visit recorded in the farmer’s notebook.'],
            ], false],
        'A-02' => ['01700000001', 'bCiXtXP8Jfs', ['nickname' => 'Raja', 'farm_tag' => 'T-02', 'sex' => 'male', 'breed_description' => 'Local (deshi)',
            'mother_details' => null, 'father_details' => 'Bull from a neighbouring village, owner Rahim'], ['estimated', 48], [
                ['observation', 'Limped for a few days', null, 'Demo: the farmer remembers this but not the date.'],
            ], false],
        'A-03' => ['01700000001', '0T4QkPg0k7Y', ['nickname' => 'Chhoto', 'farm_tag' => 'T-03', 'sex' => 'female', 'breed_description' => 'Holstein Friesian cross',
            'mother_details' => 'Moni (tag T-01)', 'father_details' => 'AI bull, upazila livestock office'], ['exact', 20], [], false],
        'A-04' => ['01700000001', 'y0dSeflqUWo', ['nickname' => 'লালী', 'farm_tag' => 'T-04', 'sex' => 'female', 'breed_description' => 'রেড চিটাগাং',
            'mother_details' => 'পাশের বাড়ির গাভী, রহিম মিয়ার', 'father_details' => 'জানা নেই'], ['exact', 1100], [
                ['vaccination', 'তড়কা রোগের টিকা', 200, 'ডেমো: ইউনিয়ন টিকা ক্যাম্পে দেওয়া হয়েছে, খাতায় লেখা আছে।'],
            ], false],
        'A-05' => ['01700000001', 'C7Ot7E1Jvjw', ['nickname' => null, 'farm_tag' => 'T-05', 'sex' => 'female', 'breed_description' => null,
            'mother_details' => null, 'father_details' => null], ['estimated', 30], [
                ['other', 'Bought at the Hat', 300, 'Demo: bought at the weekly cattle market. Breed not known.'],
            ], false],
        'A-06' => ['01700000001', 'Zv4CKvXe-A8', ['nickname' => 'Bulbuli', 'farm_tag' => 'T-06', 'sex' => 'female', 'breed_description' => 'Pabna cattle',
            'mother_details' => null, 'father_details' => null], ['exact', 2600], [
                ['vet_visit', 'Vet visit for a cough', 500, 'Demo: visit recorded before the animal was archived.'],
            ], true],
        'B-01' => ['01700000002', 'psnkLaX2wbA', ['nickname' => 'Shada', 'farm_tag' => 'T-01', 'sex' => 'female', 'breed_description' => 'Holstein Friesian cross',
            'mother_details' => null, 'father_details' => null], ['exact', 1300], [
                ['vaccination', 'Black quarter vaccine', 90, 'Demo: same farm tag T-01 as Karim’s cow, which is fine for a different farmer.'],
            ], false],
        'B-02' => ['01700000002', 'bX03VSFcOxI', ['nickname' => 'Kalo', 'farm_tag' => 'S-2', 'sex' => 'female', 'breed_description' => 'Local (deshi)',
            'mother_details' => 'Salma’s older cow (sold)', 'father_details' => null], ['exact', 2000], [
                ['vet_visit', 'Second check-up', 30, 'Demo: entered first, although it happened last.'],
                ['vaccination', 'FMD vaccine', 400, 'Demo: entered second.'],
                ['treatment_history', 'Deworming (past)', 200, 'Demo: entered third. Timeline sorts by event date.'],
            ], false],
        'B-03' => ['01700000002', 'YMiBhFC8GQY', ['nickname' => 'Baby', 'farm_tag' => 'S-3', 'sex' => 'male', 'breed_description' => 'Local (deshi)',
            'mother_details' => 'Kalo (tag S-2)', 'father_details' => null], ['estimated', 0], [], false],
        'B-04' => ['01700000002', 'uumZxpJQRmI', ['nickname' => 'Shundori Maa-er Boro Meye Lalbanu', 'farm_tag' => 'S-4', 'sex' => 'female',
            'breed_description' => 'Red Chittagong cross',
            'mother_details' => 'A very long note to test wrapping: bought from the Shibganj Thursday market from a farmer in Mokamtola, who said the mother was a Red Chittagong cow from his uncle’s farm', 'father_details' => null], ['exact', 900], [
                ['observation', 'Ate less for two days', 15, 'Demo: farmer’s observation, no vet visit.'],
            ], false],
        'C-01' => ['01700000004', 'pC6VTUligkU', ['nickname' => null, 'farm_tag' => 'H-1', 'sex' => 'female', 'breed_description' => null,
            'mother_details' => null, 'father_details' => null], ['unknown'], [], false],
        'C-02' => ['01700000004', 'BfwqkjSMzoE', ['nickname' => 'Dui Bon', 'farm_tag' => 'H-2', 'sex' => 'unknown', 'breed_description' => 'Holstein Friesian cross',
            'mother_details' => null, 'father_details' => null], ['exact', 700], [
                ['other', 'General note', 60, 'Demo: a general historical note.'],
            ], false],
    ],
];
