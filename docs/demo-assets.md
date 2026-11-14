# Demo assets and credits

Photos used **only by the local demo data** (`php artisan db:seed --class=CropListingDemoSeeder`, `php artisan kb:demo-cattle seed`).
They are free photos from [Unsplash](https://unsplash.com), used under the [Unsplash License](https://unsplash.com/license)
(free use, attribution appreciated but not required). They are downloaded from Unsplash's image CDN when the demo runs,
cached privately on your computer (`storage/app/private/demo/`), and never committed to Git.

The names, ages, breeds, parents and health notes in the demo are **fictional**. They say nothing about the animals in
the photos. **Remove all demo data before release** (`php artisan kb:demo-cattle cleanup --apply`; F1 demo listings
can be removed by their demo farmers).

## Demo accounts

All demo accounts use the password `password`. `php artisan db:seed --class=DemoSeeder` creates them all.

| Mobile (login) | Account | Roles | Demo data |
| --- | --- | --- | --- |
| 01700000001 | Karim Mia | farmer | F1 listings, F6 cattle, F15 guidance, F18 visit requests, F19 treatments and reminders, F20 jobs |
| 01700000002 | Salma Khatun | farmer, worker | F1 listings (one hidden), F6 cattle, F15 guidance (one failed request), F18 visit requests, F19 treatments, F20 jobs |
| 01700000003 | Rafiq Islam | worker | none: searches F20 jobs |
| 01700000004 | Abdul Hamid | farmer | F1 listings, F6 cattle, F15 guidance, one declined F18 request |
| 01700000005 | Nasrin Akter | farmer, buyer | F1 listings, no cattle (empty state) |
| 01700000006 | Tania Rahman | buyer | none: browses the marketplace and shop |
| 01700000007 | Dr. Farhana Yasmin | veterinarian | F18 listed vet, Dhaka (cattle, goats), pending/accepted/declined requests |
| 01700000008 | Dr. Anisur Rahman | veterinarian | F18 listed vet, Bogura (cattle, poultry) |
| 01700000009 | Dr. Shirin Sultana | veterinarian | F18 vet profile that is not listed |
| 01700000010 | Jamal Uddin | price contributor | F11 prices for Kawran Bazar and Jatrabari, one corrected and one withdrawn |
| 01700000011 | Rina Begum | price contributor | F11 prices for Mohasthan Hat |
| 01700000012 | Rahim Krishi Bitan | seller | F25 products in Savar (one out of stock, one hidden) |
| 01700000013 | Mitu Agro Store | seller | F25 products in Bogura, several with bulk offers |

The F11 prices, F15 guidance texts, F18 requests and F19 treatment notes are fictional examples, not real market data
or veterinary or agronomic advice. The F15 answers were written by hand and are saved with `ai_model` `demo-seeder`,
so they are never presented as real Gemini output. F25 demo products have no photos.

## F6 demo cattle (accessed 13 November 2026)

| Fixture | Unsplash photo | Photographer |
| --- | --- | --- |
| A-01 Moni | [490-753Sa6c](https://unsplash.com/photos/490-753Sa6c) | Tino Rischawy |
| A-02 Raja | [bCiXtXP8Jfs](https://unsplash.com/photos/bCiXtXP8Jfs) | Brad R |
| A-03 Chhoto (calf) | [0T4QkPg0k7Y](https://unsplash.com/photos/0T4QkPg0k7Y) | Teagan Turner |
| A-04 লালী | [y0dSeflqUWo](https://unsplash.com/photos/y0dSeflqUWo) | Anand Thakur |
| A-05 (tag T-05) | [C7Ot7E1Jvjw](https://unsplash.com/photos/C7Ot7E1Jvjw) | Vinícius Costa |
| A-06 Bulbuli (archived) | [Zv4CKvXe-A8](https://unsplash.com/photos/Zv4CKvXe-A8) | Andrei R. Popescu |
| B-01 Shada | [psnkLaX2wbA](https://unsplash.com/photos/psnkLaX2wbA) | Sunnie-Lee Davison |
| B-02 Kalo | [bX03VSFcOxI](https://unsplash.com/photos/bX03VSFcOxI) | Francesco Ungaro |
| B-03 Baby | [YMiBhFC8GQY](https://unsplash.com/photos/YMiBhFC8GQY) | Clara Metivier Beukes |
| B-04 Lalbanu | [uumZxpJQRmI](https://unsplash.com/photos/uumZxpJQRmI) | Giordano Rossoni |
| C-01 (tag H-1) | [pC6VTUligkU](https://unsplash.com/photos/pC6VTUligkU) | Andrei R. Popescu |
| C-02 Dui Bon | [BfwqkjSMzoE](https://unsplash.com/photos/BfwqkjSMzoE) | Riccardo Vicidomini Varotto |

Each photo was checked by hand: it shows cattle, has no people, and is a free photo (not Unsplash+).

## F1 demo crop listings

The 17 crop photos are listed with their Unsplash page ids in `database/seeders/CropListingDemoSeeder.php`
(photo pages: `https://unsplash.com/photos/{id}`).
