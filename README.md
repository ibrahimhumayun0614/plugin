# Universal Phone Input for WordPress

WordPress plugin that automatically applies an international phone input with auto country detection to ALL forms across the site (native HTML forms, Elementor Forms, WPForms, Contact Form 7, Gravity Forms, Fluent Forms, Forminator, etc.).

## 🚀 How It Works

1.  **Auto-Discovery**:
    When a page loads, the plugin's JavaScript (`universal-iti-init.js`) scans the DOM for:
    -   Inputs with `type="tel"`
    -   Expected "phone-like" text inputs (inputs with names/IDs containing "phone", "mobile", "whatsapp", or with `autocomplete="tel"`).
    
    It also uses a **MutationObserver** to watch for new forms that appear dynamically (like Elementor Popups or AJAX multi-step forms) and initializes them instantly.

2.  **Country Detection (Browser Timezone)**:
    To automatically select the user's country flag:
    -   The plugin reads the browser's **IANA timezone** via `Intl.DateTimeFormat().resolvedOptions().timeZone` (e.g., `"America/New_York"` → `US`, `"Africa/Cairo"` → `EG`).
    -   If the timezone is unavailable, it falls back to the browser's **language setting** (e.g., `"en-US"` → `US`).
    -   No external API calls are made — detection is **instant, offline, and privacy-friendly**.

3.  **Data Standardization**:
    -   The plugin creates a **hidden input field** next to every phone field.
    -   **Format**: `fieldname_e164` (e.g., if your field is named `billing_phone`, the hidden field is `billing_phone_e164`).
    -   **Value**: As the user types, this hidden field is automatically updated with the full international format (E.164), e.g., `+12125550123`.
    -   On form submission, this standardized value is sent to the server.

---

## 🌐 No External API Dependencies

This plugin does **not** call any external APIs for country detection. Country is detected entirely client-side using the browser's timezone (`Intl` API) with a built-in timezone-to-country mapping. This means:

-   **Zero network requests** for GeoIP — instant detection.
-   **No privacy concerns** — no IP addresses are sent to third parties.
-   **No rate limits** — works reliably at any traffic volume.
-   **Works offline** — no server connectivity needed for country detection.

The only external resource loaded is the **intl-tel-input** library from `cdnjs.cloudflare.com` (CSS, JS, and flag images).

---

## Installation
1.  Download the `universal-phone` folder.
2.  Upload it to your WordPress site's `wp-content/plugins/` directory.
3.  Activate the plugin via the **Plugins** menu in WordPress.

## Configuration & Filters

### 1. Default Fallback Country
If timezone detection fails, the plugin defaults to the user's browser language. If that also fails, it defaults to 'US'. Change this via filter:

```php
add_filter( 'universal_phone_default_country', function( $country_code ) {
    return 'in'; // Set default to India
} );
```

### 2. Overwrite Original Input (Optional)
By default, the plugin creates a *separate* hidden field for the standardized number. To force the visible input to update to the E.164 format on form submission:

```php
add_filter( 'universal_phone_overwrite_input', '__return_true' );
```

## Testing

### Basic HTML Form
Create a new page/post and add a custom HTML block:
```html
<form action="#" method="post">
    <label for="phone">Phone Number:</label>
    <input type="tel" id="phone" name="phone">
    <button type="submit">Submit</button>
</form>
```
**Verification steps:**
1.  Open the page.
2.  Confirm the flag matches your current country (timezone detection working).
3.  Inspect the element (Right Click -> Inspect).
4.  Find the hidden input `<input type="hidden" name="phone_e164" ...>`.
5.  Type a number and watch the hidden input value update.

---

## License & Credits

### License
This plugin is released under the **GNU General Public License v2 or later**.

### Credits
*   **International Telephone Input**: This plugin uses the [intl-tel-input](https://github.com/jackocnr/intl-tel-input) library by Jack O'Connor, which is licensed under the MIT License.

### Disclaimer
This software is provided "as is", without warranty of any kind.
