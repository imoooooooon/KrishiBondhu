# F11 — Market Price Tracking

Owner: Md. Abdullah Al Jarif (`jarifff`) · Issue: [#26](https://github.com/imoooooooon/KrishiBondhu/issues/26) · Sprint 1 ·
SRS row: R11 / F11 in [srs.md](../srs.md).

> **Living document.** Records the decisions for F11 and what F12, F13 and F14 can rely on.
> Update it in the same PR as any change to F11 behaviour.

## 1. What F11 does

Market price contributors record the prices they see at local markets for everything farmers produce (grains,
vegetables, fish, meat, poultry, eggs, milk and oil), per kg, litre or dozen depending on the product. Vegetable and
fish prices are recorded per session of the day (morning, afternoon, evening); every other product has one price per
day. Contributors may add their estimate for tomorrow. They correct their own entries. Anyone
can see, for each item at each market, today's price, yesterday's price and tomorrow's estimate. Every entry is kept,
so price history is never lost.

| Slice | What | Routes | Status |
| --- | --- | --- | --- |
| 1 | Markets and price tables, recording and listing own prices | `contributor.prices.index/create/store` | Built |
| 2 | Sessions of the day, tomorrow's estimate, all farm products with their units | same routes | Built |
| 3 | Corrections with a revision log, 7-day window, stale-tab 409 | `contributor.prices.edit/update` | Planned |
| 4 | Public page: today, yesterday and tomorrow's estimate, filters, recent entries | `market-prices.index` | Planned |

Not F11: comparison across markets (F13, `market-prices.compare`), trends and charts over a time period (F14,
`market-prices.trends`), maps, coordinates and distance (F12, `markets.*`), and market administration
(`admin.markets.*`). Those routes stay on `PlannedController`. Jarif will build F12 and F13 next, including the
"which market pays the most after transport" calculation for sellers; that is not part of F11.

## 2. Decisions (agreed with Jarif, 2026-11-14)

| # | Decision | Why |
| --- | --- | --- |
| D1 | A minimal shared `markets` table (name, Bangla name, upazila, district). The initial 8 markets are inserted by the migration, like the roles. | No admin account can exist yet, so `/admin/markets` cannot create markets. F12 adds coordinates in its own migration; admins manage markets later. |
| D2 | Products are a fixed list on `MarketPrice::ITEMS` (code → group and unit) with English and Bangla names. | Free text would split one product into many ("Potato", "potato", "আলু"), breaking the latest price and F13/F14. Adding a product is a code change plus a migration for the CHECK; a test checks the two lists match. |
| D3 | A correction is allowed on your own entry within 7 days, needs a reason, and logs the old values (slice 2). | The SRS keeps previous entries: no value that was ever public disappears. |
| D4 | Every price is either retail or wholesale. | Mixing them would make the latest price and comparisons misleading. |
| D5 | The public page shows the recent entries for one crop at one market (slice 4). | Makes the kept history visible; charts and time periods stay F14's. |
| D6 | Prices that change during the day have a session: morning, afternoon or evening. | Vegetable and fish prices change during the day, sometimes several times. |
| D7 | A contributor may add an **estimate for tomorrow**, stored with `is_estimate` and session `all_day` (a CHECK ties them). The server sets the date to tomorrow. It is never shown as an actual price. | Users want an idea of tomorrow's price; an estimate from someone at the market is honest, a computed forecast would not be (trends are F14). |
| D8 | **Scope change** (requested by Jarif): F11 tracks **all farm produce**, not only crops: grains and pulses, vegetables, fish, meat (beef, mutton), poultry (broiler, sonali, deshi chicken, duck), eggs, cow milk and edible oil (34 products). Logged in the SRS revision log when F11 lands. | Krishi Bondhu helps farmers with everything they produce, and all of it is sold at the same local markets. |
| D10 | **Only vegetables and fish** have sessions (`MarketPrice::INTRADAY_GROUPS`). Every other product (grains, meat, poultry, eggs, milk, oil) has **one price per day**, stored with session `all_day`; a session sent for them is ignored. The database CHECK encodes the rule per product. The unique key is contributor + market + product + type + day + session + kind, so a whole-day actual price and the estimate for the same day stay separate. | Told by Jarif: only vegetable and fish prices change during a day. |
| D9 | Each product has a **fixed unit**: kg, litre (milk, oil) or dozen (eggs). The price column is `unit_price` ("৳ per unit") and the product column `item_code`; the unit is not stored but comes from `MarketPrice::ITEMS`, so a row can never disagree with its product. The select box shows "Cow milk (per litre)". | "Price per kg" is wrong for milk, oil and eggs. |

## 3. Business rules (slice 1)

- **Who:** only users with `Role::PRICE_CONTRIBUTOR` have a workspace (others 403, guests to log in, no admin
  bypass). A contributor sees only their own entries.
- **An entry:** market (from the list), product (from the list), retail or wholesale, price per unit, price date,
  optional note (up to 200 characters). The contributor and version are set by the server, never by the form.
- **Price:** a plain decimal with at most 2 decimals, more than ৳0 and at most ৳1,00,000 per unit. Bangla digits are
  converted; commas, ৳, signs and `1e3` are rejected, not stripped. Stored as DECIMAL(8,2) and compared with brick/math.
- **Date and session (actual price):** today or up to 365 days back, on the Bangladesh calendar (Asia/Dhaka). Vegetables
  and fish also need morning, afternoon or evening; other products are whole-day prices. Bangla digits are accepted.
- **Estimate:** always for tomorrow with session "all_day"; any date or session the form sends is ignored.
- **History:** a new price is always a new row. There is no delete route; markets and contributors with prices cannot
  be deleted (restrict foreign keys).
- **One entry per contributor + market + product + type + day + session + kind** (unique key): several a day for
  vegetables and fish, one a day for everything else. Sending the same form again
  (same price) adds nothing and says so. A different price for the same session is refused with "correct that entry
  instead". Another contributor may record the same item at the same market and time. One estimate per item for
  tomorrow, and tomorrow's actual prices are separate rows next to it.
- **Rate limit:** 20 new prices per minute per account.

## 4. Database

- `markets`: `name` (120), `name_bn` (120), `upazila` (80), `district` (60); unique (`name`, `district`).
- `market_prices`: `market_id` and `contributor_id` (restrict FKs), `item_code` (product), `price_type`,
  `unit_price` DECIMAL(8,2), `price_date` DATE, `price_session`, `is_estimate`, `note`, `version`, timestamps.
  CHECK constraints for item, type, session, estimate ↔ "all_day", price range and version. Unique key
  `market_prices_one_per_session_and_kind_unique`; indexes for the latest price per market + item + type and for "My prices".
  The session and estimate columns came in a forward-only migration, and a third one renamed `crop_code` →
  `item_code` and `price_per_kg` → `unit_price`, widened the product list and added the per-product session rule.

## 5. Code map

| Layer | Files |
| --- | --- |
| Migrations | `2026_11_14_400000_create_markets_table.php`, `2026_11_14_400001_create_market_prices_table.php`, `2026_11_14_400002_add_sessions_estimates_and_fish_to_market_prices.php`, `2026_11_14_400003_generalise_market_price_products_and_sessions.php` |
| Models / factories | `Market`, `MarketPrice`; `MarketFactory`, `MarketPriceFactory` |
| Policy | `MarketPricePolicy` (create; correction rules arrive in slice 2) |
| Validation | `app/Http/Requests/MarketPrices/StoreMarketPriceRequest.php` |
| Business rules | `app/Services/MarketPriceService.php` (one entry per day, resent forms) |
| Controller | `app/Http/Controllers/Contributor/MarketPriceController.php` |
| Views | `resources/views/contributor/prices/` (shared `.table` styles; no feature CSS needed) |
| Tests | `tests/Feature/MarketPrices/` |

## 6. For F12, F13 and F14

- Read prices from `market_prices` only; never write them. Group by market + item + **price type**: retail and
  wholesale must not be mixed. **Leave out estimates** (`is_estimate = false`) unless the page is about estimates.
- Always show the unit with a price (`$price->unitLabel()`): eggs are per dozen, milk and oil per litre.
- The latest actual price for a market + item + type is the row with the latest `price_date`, then the latest session
  (evening, afternoon, morning), then the highest `id`.
- F12 adds coordinates to `markets` with its own migration; do not rename the existing columns.

## 7. Implementation record

- **Slice 1** (2026-11-14, branch `jarif/f11-market-price-tracking`): built with Claude Code at Jarif's request (see
  [ai-usage.md](../ai-usage.md)). 35 feature tests on the MySQL test database. Bangla strings in `lang/bn.json` are
  drafts and should be checked by a fluent reader.
- **Slice 2** (2026-11-14, same branch): sessions, tomorrow's estimate and fish, after Jarif described how prices
  change during the day. A forward-only migration; its `down()` was tested by rolling back and migrating again.
  Then, at Jarif's request, all farm produce with kg / litre / dozen units (a third forward-only migration, also
  rolled back and re-applied to test `down()`), and the rule that only vegetables and fish have sessions (D10).
  Two things went wrong and were fixed: rolling back the session migration failed because a fish price existed
  (its `down()` restores the crop-only list); MySQL refused the whole statement, so nothing changed, and the
  limitation is now written on both `down()` methods. A test also caught a real bug: the duplicate check ignored
  "estimate", so an actual whole-day price was refused because of the estimate for the same day. 48 F11 tests.
