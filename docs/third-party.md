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

Planned in the SRS but **not added yet** (each needs a team decision; confirm with faculty where the SRS says so):
Bootstrap (UI), Gemini API (AI guidance), Leaflet + OpenStreetMap (maps), Azure AI Speech (Bangla voice),
SSLCommerz sandbox (test payments), alwaysdata (hosting).
