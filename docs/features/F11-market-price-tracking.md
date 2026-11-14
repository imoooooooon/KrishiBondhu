# F11 — Market Price Tracking

Owner: Md. Abdullah Al Jarif (`jarifff`) · Issue: [#26](https://github.com/imoooooooon/KrishiBondhu/issues/26) · Sprint 1 ·
SRS row: R11 / F11 in [srs.md](../srs.md).

> **Living document.** Records the decisions for F11 and what F12, F13 and F14 can rely on.
> Update it in the same PR as any change to F11 behaviour.

## 1. What F11 does

Market price contributors record the crop prices they see at local markets, per kilogram, and correct their own
entries. Anyone can see the latest price for each crop at each market with the date it applied. Every entry is kept,
so price history is never lost.

| Slice | What | Routes | Status |
| --- | --- | --- | --- |
| 1 | Markets and price tables, recording and listing own prices | `contributor.prices.index/create/store` | Built |
| 2 | Corrections with a revision log, 7-day window, stale-tab 409 | `contributor.prices.edit/update` | Planned |
| 3 | Public latest prices with filters and recent entries | `market-prices.index` | Planned |

Not F11: comparison across markets (F13, `market-prices.compare`), trends and charts over a time period (F14,
`market-prices.trends`), maps, coordinates and distance (F12, `markets.*`), and market administration
(`admin.markets.*`). Those routes stay on `PlannedController`.

## 2. Decisions (agreed with Jarif, 2026-11-14)

| # | Decision | Why |
| --- | --- | --- |
| D1 | A minimal shared `markets` table (name, Bangla name, upazila, district). The initial 8 markets are inserted by the migration, like the roles. | No admin account can exist yet, so `/admin/markets` cannot create markets. F12 adds coordinates in its own migration; admins manage markets later. |
| D2 | Crops are a fixed list of codes on `MarketPrice::CROPS` with English and Bangla labels (14 crops sold by the kg). | Free text would split one crop into many ("Potato", "potato", "আলু"), breaking the latest price and F13/F14. Adding a crop is a code change plus a migration for the CHECK. |
| D3 | A correction is allowed on your own entry within 7 days, needs a reason, and logs the old values (slice 2). | The SRS keeps previous entries: no value that was ever public disappears. |
| D4 | Every price is either retail or wholesale. | Mixing them would make the latest price and comparisons misleading. |
| D5 | The public page shows the recent entries for one crop at one market (slice 3). | Makes the kept history visible; charts and time periods stay F14's. |

## 3. Business rules (slice 1)

- **Who:** only users with `Role::PRICE_CONTRIBUTOR` have a workspace (others 403, guests to log in, no admin
  bypass). A contributor sees only their own entries.
- **An entry:** market (from the list), crop (from the list), retail or wholesale, price per kg, price date, optional
  note (up to 200 characters). The contributor and version are set by the server, never by the form.
- **Price:** a plain decimal with at most 2 decimals, more than ৳0 and at most ৳1,00,000 per kg. Bangla digits are
  converted; commas, ৳, signs and `1e3` are rejected, not stripped. Stored as DECIMAL(8,2) and compared with brick/math.
- **Date:** today or up to 365 days back, on the Bangladesh calendar (Asia/Dhaka). Bangla digits are accepted.
- **History:** a new price is always a new row. There is no delete route; markets and contributors with prices cannot
  be deleted (restrict foreign keys).
- **One entry per contributor + market + crop + type + day** (unique key). Sending the same form again (same price)
  adds nothing and says so. A different price for the same day is refused with "correct that entry instead".
  Another contributor may record the same crop at the same market on the same day.
- **Rate limit:** 20 new prices per minute per account.

## 4. Database

- `markets`: `name` (120), `name_bn` (120), `upazila` (80), `district` (60); unique (`name`, `district`).
- `market_prices`: `market_id` and `contributor_id` (restrict FKs), `crop_code`, `price_type`, `price_per_kg`
  DECIMAL(8,2), `price_date` DATE, `note`, `version`, timestamps. CHECK constraints for crop, type, price range and
  version. Unique key `market_prices_one_per_day_unique`; indexes for the latest price per market + crop + type and
  for "My prices".

## 5. Code map

| Layer | Files |
| --- | --- |
| Migrations | `2026_11_14_400000_create_markets_table.php`, `2026_11_14_400001_create_market_prices_table.php` |
| Models / factories | `Market`, `MarketPrice`; `MarketFactory`, `MarketPriceFactory` |
| Policy | `MarketPricePolicy` (create; correction rules arrive in slice 2) |
| Validation | `app/Http/Requests/MarketPrices/StoreMarketPriceRequest.php` |
| Business rules | `app/Services/MarketPriceService.php` (one entry per day, resent forms) |
| Controller | `app/Http/Controllers/Contributor/MarketPriceController.php` |
| Views | `resources/views/contributor/prices/` (shared `.table` styles; no feature CSS needed) |
| Tests | `tests/Feature/MarketPrices/` |

## 6. For F12, F13 and F14

- Read prices from `market_prices` only; never write them. Group by market + crop + **price type**: retail and
  wholesale must not be mixed.
- The latest price for a market + crop + type is the row with the highest `price_date`, then the highest `id`.
- F12 adds coordinates to `markets` with its own migration; do not rename the existing columns.

## 7. Implementation record

- **Slice 1** (2026-11-14, branch `jarif/f11-market-price-tracking`): built with Claude Code at Jarif's request (see
  [ai-usage.md](../ai-usage.md)). 35 feature tests on the MySQL test database. Bangla strings in `lang/bn.json` are
  drafts and should be checked by a fluent reader.
