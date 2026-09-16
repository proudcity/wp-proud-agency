# Changelog

## 2026.09.16.1351

### Department social media widget not displaying (#2933)

Two independent bugs, either of which alone hid a department's social accounts.

- Removed the hardcoded `return false;` at the top of `AgencySocial::hasContent()` (`widgets/agency-social-links-widget.class.php`). It was added in **89802cb on 2016-12-01** for issue #691 ("Formatting of share icons is weird"), and disabled the widget for nearly ten years while leaving it registered — so editors could add "Agency social media" in Page Builder and get a silently blank widget. Its markup was rewritten in the process: the old version was Font Awesome 4 (`fa fa-circle fa-stack-2x`), which is presumably the weird formatting that got it disabled, and it printed `$url` and `$service` with bare `print` and no escaping. It now uses the shared helpers below, `esc_url()` with a per-service scheme allowlist, and an `sr-only` text label since the icons are decorative.
- Fixed the social row in `widgets/templates/agency-contact.php`, which had been dead since **9b60fdf on 2026-02-02**. That commit rewrote the template to scan `$instance` for flat `social_<service>` keys so the newer `CustomContact` widget would work, but `AgencyContact` feeds it a nested `$instance['social']` array. The keys never matched, so `$socialLinks` was always empty and departments silently lost their icons for seven months. `AgencyContact::hasContent()` now writes both shapes.
- Extracted the icon map into shared helpers in `wp-proud-agency.php` — `social_icon_classes()`, `social_allowed_schemes()` and `agency_social_meta()`. The map previously existed inline in the contact template and again, stale, in the social widget, which is exactly how the two drifted apart. `social_icon_classes()` returns `null` for an unsupported service so it doubles as the render allowlist, and is built only from hardcoded literals so no caller input can reach a class attribute.
- `agency_social_meta()` returns meta values raw. The old code applied `esc_html()` at read time, which was never an `href`-context control and double-encoded `&` in query strings once the template began calling `esc_url()`. Escaping now happens at output in both render paths.

### Security

Found by security review of the above; both are older than #2933 but live in the code it touches.

- **Stored XSS via the contact name link.** `AgencyContact::hasContent()` validated `name_link` with `filter_var( $v, FILTER_VALIDATE_EMAIL )`, which accepts RFC quoted local parts — so a payload such as `"><img/src/onerror=alert(1)>"@a.com` passed validation, was concatenated into `"mailto:$v"` and printed unescaped, bypassing `esc_url()` entirely. Now uses `sanitize_email()` + `is_email()`, and `agency-contact.php` wraps the value in `esc_url()` at output so the `CustomContact` path — a raw widget field with no processing at all — is covered regardless of caller.
- **Stored XSS via the contact free-text fields.** `phone_tel_links()` and `email_mailto_links()` linkified with `preg_replace()` and returned the whole string, non-matching content included, as raw HTML for the template to `print`. Anything stored in `phone`, `fax`, `sms` or `email` executed for every visitor. Both now filter input through `wp_kses()` first and build their anchors with `preg_replace_callback()` + `esc_url()` rather than interpolating the capture into an `href`.
- The filter allowlist is `['br' => []]`, not `esc_html()`, and that is deliberate. A scan of the San Rafael, Somerville and Duchesne databases found one stored value that depends on markup surviving — San Rafael Fire's `Emergencies: 9-1-1   <br>  Main: (415) 485-3304`. `esc_html()` would have rendered that tag as visible text. Same allowlist wp-proud-core settled on for widget titles in #2916.

- Rewrote both linkifiers as a single `preg_replace_callback()` pass. Running the URL and email patterns as two sequential `preg_replace()` calls meant the email pass scanned the URL pass's own output — the URL host pattern does not consume `@` — so a contact URL containing one came out as `<a <a href="mailto:href=https//…">`. Not exploitable (both branches emit only `esc_url()`'d hrefs and `esc_html()`'d text) but the markup was malformed and the link wrong.
- Moved the comma-to-`<br/>` substitution to before linkifying. It ran after, so a comma inside a URL landed inside the generated `href`. Scanned all three site dumps: no stored value has a comma inside a URL, so nothing real changes.
- Documented on both methods that their return value is HTML and **not** attribute-safe — `wp_kses()` does not encode quotes, so they may only ever be printed as element content, which is what every current call site does.

### Tests

- Added a PHPUnit + Brain Monkey harness (`phpunit.xml`, `tests/`): **85 tests, 215 assertions**. The plugin had none, and `composer.json` was not valid JSON — trailing commas meant `composer install` could not run at all. Rewrote it, added the dev dependencies and a `test` script, and corrected the PHP requirement from `>=5.2.4` to `>=8.0` since the code already uses `str_starts_with()`.
- The bootstrap loads the **real** `wp-includes/kses.php` and `formatting.php` rather than modelling them, following the lesson wp-proud-core recorded in #1825: a hand-written kses diverged from the real one on 22 of 78 adversarial inputs, several in the dangerous direction, so a template test passed while real WordPress emitted raw markup. These tests assert that editor-controlled meta cannot break out of an `href`, which means they are really asserting the behaviour of `esc_url()`, `wp_kses()` and `sanitize_email()`.

References: https://github.com/proudcity/wp-proudcity/issues/2933
