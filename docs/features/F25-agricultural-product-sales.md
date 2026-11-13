# F25 — Agricultural Product Sales

Owner: Md. Abdullah Al Jarif (`jarifff`) · Issue: [#19](https://github.com/imoooooooon/KrishiBondhu/issues/19) · Sprint 1 ·
SRS row: R25 / F25 in [srs.md](../srs.md).

> **Living document.** Records the scope decisions for F25 and the contract other features rely on.
> Update it in the same PR as any change to F25 behaviour.

## 1. Scope decision (option A: focused F25)

F25 in the SRS mentions a cart, orders, offers and cancellation. Those belong to shared features that are not built yet
(F5 cart/orders, F3 negotiation, F4 checkout, F26 payments), and no `Cart`, `Order` or `PriceOffer` model exists.
To avoid designing teammates' schemas, F25 is built in slices that only touch F25's own data:

| Slice | What | Routes | Status |
| --- | --- | --- | --- |
| 1 | Product schema, policy, seller workspace (add, list, view, edit, publish/unpublish, remove) | `seller.products.*` | Built (branch `jarif/f25-seller-products`) |
| 2 | Public shop: browse, search/filter, product detail | `shop.index`, `shop.show` | Planned |
| 3 (optional) | Product photos, reusing `CropListingPhotoService` without changing it | — | Planned |

Still on `PlannedController`, waiting for their owners: `shop.offers.store` (F3), `seller.orders.*` (needs F5's
Order model), and the shared `cart.*`, `checkout.*`, `orders.*`, `negotiations.*` and `payments.*` routes.

## 2. Feature boundaries

| Feature | F25 provides | F25 does not implement |
| --- | --- | --- |
| F3 Negotiation | `bulk_min_quantity` (public threshold) and `bulk_min_unit_price` (private minimum) on each product | PriceOffer, offers, counteroffers, accept/reject, agreed-price record |
| F5 Cart, orders, stock | `stock_quantity` (exact, never negative), a row lock + `version` contract, soft delete that keeps rows | Cart, Order, OrderItem snapshots, reservation/release, cancellation, `seller.orders.*` |
| F4 Delivery and cost | The fixed `unit_price` | Delivery choice, fees, packaging/platform charges, checkout |
| F26 Payments | Nothing | Payment attempts, provider sandbox, callbacks |

## 3. Business rules

- **Who:** only users with the `seller` role (`Role::SELLER`) have a workspace. A user may hold other roles too.
  Non-sellers (including administrators) get 403; another seller's product is 404; guests are sent to log in.
- **Categories:** fertilizer, pesticide, seed, tool or equipment, other supply.
- **Units:** kg, litre, bag, packet, bottle, piece. Price and stock use the same unit; nothing is converted.
  Bag, packet, bottle and piece are counted, so stock and the bulk threshold must be whole numbers.
- **Fixed price** (`unit_price`): more than ৳0 and at most ৳10,00,000, at most 2 decimals. Small purchases always pay it.
- **Stock** (`stock_quantity`): 0 to 1,000,000, at most 3 decimals. 0 is allowed and means "Out of stock": the product
  can stay published, but F5 must refuse to add or order it. Stock can never be negative (form rule and CHECK).
- **Bulk offers** (optional, "Accept bulk offers"): both fields or neither.
  - `bulk_min_quantity` ≥ 2 units (1 unit is always a fixed-price purchase) and ≤ 1,000,000. Public.
  - `bulk_min_unit_price` > 0 and **≤ `unit_price`** (equal is allowed). **Private**: shown only on the owner's pages,
    hidden from `toArray()`/`toJson()`, and the shared facts partial prints it only with `showPrivate`.
  - Unticking the box clears both fields, even if a forged request still sends them.
  - Lowering the fixed price below the saved bulk minimum is refused with a message; nothing is clamped silently.
  - The threshold may be above current stock (stock changes); slice 2 shows bulk availability only when stock allows it.
- **Lifecycle:** adding publishes immediately. Published ⇄ unpublished at any time (republishing keeps the first
  `published_at`). Removing is a soft delete after an explicit confirmation; the row stays for future order history,
  and the seller's account cannot be deleted while it owns products (`restrictOnDelete`).
- **Numbers:** exact decimal strings end to end (DECIMAL columns, `decimal:N` casts, `brick/math` comparisons).
  Bangla digits typed into number fields are converted to 0–9; commas, ৳, signs and `1e3` are rejected, not stripped.
- **Safe writes:** CSRF on every form; Form Requests whitelist the fields (seller, status, version and dates cannot be
  sent in); a `submission_token` makes a double-submitted "Add product" create one product (also across removed ones);
  every change sends the page's `version` and is refused with a 409 comparison page if the product changed meanwhile;
  10 new products and 30 changes per minute per account.

## 4. Database: `agricultural_products`

Migration `2026_11_14_000000_create_agricultural_products_table.php`. Columns: `seller_id` (FK users, restrict),
`name` (120), `brand` (80, nullable), `category`, `description` (1000), `unit_code`, `unit_price` DECIMAL(10,2),
`stock_quantity` DECIMAL(12,3), `bulk_min_quantity` DECIMAL(12,3) nullable, `bulk_min_unit_price` DECIMAL(10,2)
nullable, `status`, `published_at`, `version`, `submission_token`, timestamps, `deleted_at`.

MySQL CHECK constraints repeat the rules above (status, category, unit, price and stock ranges, whole numbers for
counted units, bulk both-or-neither with minimum ≤ fixed price). A CHECK that evaluates to NULL counts as passed in
MySQL, so the bulk rule tests `IS NOT NULL` explicitly. Indexes: unique (`seller_id`, `submission_token`), owner list,
public list, category filter.

## 5. Integration contract for F5 and F3

- Any code that changes `stock_quantity` (orders, cancellations, accepted offers) must lock the product row
  (`SELECT … FOR UPDATE`) inside a transaction and add 1 to `version`, like `AgriculturalProductService`.
  Then a seller's edit opened before the change is refused (409) instead of overwriting the new stock.
- Once orders exist, a seller's stock edit must be refused if it would go below stock reserved by open orders.
- Order items must copy the product name, unit and agreed price at purchase time; editing a product must never
  rewrite past orders. Removed (soft-deleted) products must still resolve for order history (`withTrashed()`).
- Offers (F3) must be for at least `bulk_min_quantity` units, at a price between `bulk_min_unit_price` and
  `unit_price`. The minimum must never be sent to the buyer, including in error messages.

## 6. Code map (slice 1)

| Layer | Files |
| --- | --- |
| Model / factory | `app/Models/AgriculturalProduct.php`, `database/factories/AgriculturalProductFactory.php` |
| Policy | `app/Policies/AgriculturalProductPolicy.php` |
| Validation | `app/Http/Requests/AgriculturalProducts/` (Store, Update, Destroy) |
| Business rules | `app/Services/AgriculturalProductService.php`, `app/Exceptions/AgriculturalProductConflictException.php` |
| Controller | `app/Http/Controllers/Seller/AgriculturalProductController.php` |
| Views | `resources/views/seller/products/`, `resources/views/agricultural-products/` (partials shared with slice 2), `public/css/shop.css` |
| Routes | `routes/modules/shop.php` (same URIs and names as IA.md §5.8) |
| Tests | `tests/Feature/AgriculturalProducts/` |

## 7. Implementation record

- **Slice 1** (2026-11-14, branch `jarif/f25-seller-products`): built with Claude Code at Jarif's request (see
  [ai-usage.md](../ai-usage.md)). Evidence: `composer check` passed; 63 new feature tests on the MySQL test database
  (249 in total). Writing the tests caught a real bug in the first draft of the bulk CHECK constraint (NULL counted as
  passing); fixed before any commit. Manual light/dark, English/Bangla and phone-width checks: see the PR.
- Bangla strings added to `lang/bn.json` are drafts and should be checked by a fluent reader.
