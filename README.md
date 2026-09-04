# FreeShipping

Offers the shipping cost of an order once its products reach a threshold, per delivery area and, if you want, per carrier.

The cart page announces what is left to reach the threshold, which is what makes the average order go up.

## Requirements

Thelia 3.0.0 or later.

## Install

```bash
composer require thelia/free-shipping-module
```

Then activate the module in the back office, under Modules.

## Settings

Two settings, in the module configuration screen:

- **Compare the threshold with the tax included total** (on by default). Turn it off to compare with the total before taxes, which is what a business to business shop expects.
- **Subtract discounts before comparing** (on by default). Turn it off to compare the threshold with the total before any promotion code or customer discount.

The amount compared with a threshold is always the products of the cart, never its shipping cost.

## Rules

A rule is a threshold, a delivery area and, optionally, one carrier and a validity period.

- Several rules can coexist: fifty euros in France, eighty in the European area, nothing elsewhere.
- A rule left open to every carrier applies to all of them; a rule tied to one carrier leaves the others with their usual price.
- A rule outside its period, or turned off, does nothing.
- When several rules apply to the same cart, the lowest threshold wins, both for the offer and for the message.
- Deleting an area deletes its rules with it, and deleting a carrier deletes the rules tied to it: the remaining rules are simply the ones left, and the usual shipping cost applies where none does.
- Nothing about free shipping can block the checkout. Should the offer fail to be computed at all, the price the carrier gave stands and the error is logged.

## Coupons

A coupon that already removes the shipping cost and a free shipping rule lead to the same result, and the order shows a single line at zero. Nothing has to be configured for the two to live together.

## What the customer sees

- On the cart page, above the products: "Only 4.40 € more for free delivery", or "Your delivery is free". The message follows the cart as quantities change, without reloading the page. It disappears when no rule applies.
- The cart message is based on the customer's default address, because that is the only destination known while the cart is being filled; the delivery step prices the address actually chosen.
- A rule tied to one carrier is only announced when that carrier is active and serves the area the rule covers, so the message never promises a price no carrier can honour. Applying the offer to a carrier being priced does not need that check: it has already proved it can deliver.
- At the delivery step, the carrier keeps its name and shows "Free" instead of a price.
- `GET /api/front/delivery_modules` answers with `postage`, `postageTax` and `postageUntaxed` at zero, so a site that consumes the API does not need to know the rules.

## Themes

The message is rendered on the `cart.top` theme hook, which the Flexy theme provides. A theme without that hook keeps everything else: only the message is missing.

The component re-renders on the browser side events the Flexy cart emits (`UPDATE_ITEM_QUANTITY_EVENT`, `CART_DELETE_ITEM_EVENT`, `CART_ADD_ITEM_EVENT`, `syncSummary`). A theme that emits none of them shows a message that only follows a page reload.

## What changed since 1.x

Version 1.x was a delivery module of its own, with one free shipping amount per area and a shipping confirmation email. Version 2.0 is a classic module that leaves every delivery module in place and cancels their price when a rule applies, so a third party carrier benefits without knowing about it. Rules now carry a carrier and a validity period, the back office lists and edits them, and the cart announces the threshold.

Rules are not migrated from 1.x: the table is new and a 2.x shop does not upgrade to 3.0 in place.

## License

GPL-3.0-or-later. See LICENSE.txt.
