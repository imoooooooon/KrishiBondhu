# Third-party packages, APIs, and services

The course requires recording every significant third-party package, API, dataset, or service and its purpose.
Packages that implement a major assessed feature need **faculty approval before use**.
Add a row in the same PR that introduces the dependency.

| Name | Version / constraint | Type | Purpose | Added by | Faculty approval needed? |
| --- | --- | --- | --- | --- | --- |
| laravel/framework | ^13.0 | Composer (runtime) | MVC framework | Setup | No (framework) |
| laravel/tinker | ^3.0 | Composer (runtime) | Interactive console for development | Setup | No |
| laravel/pint | ^1.27 | Composer (dev) | PHP code formatting | Setup | No |
| phpunit/phpunit | ^12.5 | Composer (dev) | Automated tests | Setup | No |
| mockery/mockery, fakerphp/faker, nunomaduro/collision | see composer.json | Composer (dev) | Test doubles, fake test data, readable test errors | Setup | No |
| MySQL | 8.4 LTS | Database | Application data | Setup | No |
| GitHub Actions (actions/checkout, shivammathur/setup-php) | v7 / v2 | CI | Automated checks on PRs | Setup | No |
| Inter Tight (via @fontsource-variable/inter-tight 5.3.0) | variable, latin | Font (self-hosted, `public/fonts/`) | Latin UI typeface | Design system | No (SIL OFL 1.1) |
| Li Ador Noirrit (Lipighor) | Unicode Regular, SemiBold, Bold | Font (local only, **not committed**) | Primary Bangla UI typeface | Design system | No; licence forbids redistribution, needs footer credit and written permission from Lipighor before public web use |
| Noto Sans Bengali (via @fontsource-variable/noto-sans-bengali 5.3.0) | variable, bengali | Font (self-hosted, committed) | Bangla fallback typeface | Design system | No (SIL OFL 1.1) |
| Lucide icons (lucide-static 1.63.0) and Lucide Lab `cow-head` (@lucide/lab 0.7.0) | SVG paths copied | Icons (`components/ui/icon.blade.php`) | UI line icons | Design system | No (ISC) |

No CSS/JS framework is used (Bootstrap from the SRS draft was replaced by the design system).

Planned in the SRS but **not added yet** (each needs a team decision; confirm with faculty where the SRS says so):
Gemini API (AI guidance), Leaflet + OpenStreetMap (maps), Azure AI Speech (Bangla voice),
SSLCommerz sandbox (test payments), alwaysdata (hosting).
