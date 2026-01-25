# Shortcodes

LW SEO provides shortcodes for displaying SEO-related content in your posts, pages, and widgets.

## Breadcrumbs

### `[lw_breadcrumbs]`

Display a breadcrumb navigation trail.

**Attributes:**

| Attribute | Default | Description |
|-----------|---------|-------------|
| separator | `»` | Character between breadcrumb items |
| home_text | `Home` | Text for home link |
| schema | `true` | Include Schema.org markup |

**Examples:**

```
[lw_breadcrumbs]

[lw_breadcrumbs separator=" / " home_text="Start"]

[lw_breadcrumbs schema="false"]
```

**Output:**
```html
<nav class="lw-seo-breadcrumbs" aria-label="Breadcrumb">
  <ol itemscope itemtype="https://schema.org/BreadcrumbList">
    <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
      <a itemprop="item" href="https://example.com/">
        <span itemprop="name">Home</span>
      </a>
      <meta itemprop="position" content="1" />
    </li>
    <li>»</li>
    <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
      <span itemprop="name">Current Page</span>
      <meta itemprop="position" content="2" />
    </li>
  </ol>
</nav>
```

**PHP Function:**

```php
<?php
if ( function_exists( 'lw_seo_breadcrumbs' ) ) {
    lw_seo_breadcrumbs();
}
?>
```

---

## Local SEO Shortcodes

These shortcodes display business information configured in **LW Plugins → SEO → Local SEO**.

### `[lw_address]`

Display your business address.

**Attributes:**

| Attribute | Default | Description |
|-----------|---------|-------------|
| format | `full` | `full` includes country, `short` excludes it |
| schema | `true` | Include Schema.org microdata |

**Examples:**

```
[lw_address]

[lw_address format="short"]

[lw_address schema="false"]
```

**Output:**
```html
<address class="lw-seo-address" itemscope itemtype="https://schema.org/PostalAddress">
  <span itemprop="streetAddress">123 Main Street, Suite 100</span><br>
  <span itemprop="postalCode">1051</span>
  <span itemprop="addressLocality">Budapest</span>,
  <span itemprop="addressRegion">Budapest</span><br>
  <span itemprop="addressCountry">HU</span>
</address>
```

---

### `[lw_phone]`

Display your business phone number.

**Attributes:**

| Attribute | Default | Description |
|-----------|---------|-------------|
| link | `true` | Wrap in clickable `tel:` link |
| schema | `true` | Include Schema.org microdata |

**Examples:**

```
[lw_phone]

[lw_phone link="false"]

[lw_phone schema="false"]
```

**Output:**
```html
<span class="lw-seo-phone" itemprop="telephone">
  <a href="tel:+3612345678">+36 1 234 5678</a>
</span>
```

---

### `[lw_email]`

Display your business email address.

**Attributes:**

| Attribute | Default | Description |
|-----------|---------|-------------|
| link | `true` | Wrap in clickable `mailto:` link |
| schema | `true` | Include Schema.org microdata |

**Examples:**

```
[lw_email]

[lw_email link="false"]
```

**Output:**
```html
<span class="lw-seo-email" itemprop="email">
  <a href="mailto:info@example.com">info@example.com</a>
</span>
```

---

### `[lw_hours]`

Display your business opening hours.

**Attributes:**

| Attribute | Default | Description |
|-----------|---------|-------------|
| format | `table` | `table` or `list` |
| schema | `true` | Include Schema.org microdata |

**Examples:**

```
[lw_hours]

[lw_hours format="list"]

[lw_hours format="table" schema="false"]
```

**Table Output:**
```html
<div class="lw-seo-hours">
  <table class="lw-seo-hours-table">
    <tbody>
      <tr>
        <th>Monday</th>
        <td>
          <time itemprop="openingHours" datetime="Monday 09:00-17:00">
            09:00 - 17:00
          </time>
        </td>
      </tr>
      <tr>
        <th>Saturday</th>
        <td>Closed</td>
      </tr>
    </tbody>
  </table>
</div>
```

**List Output:**
```html
<div class="lw-seo-hours">
  <ul class="lw-seo-hours-list">
    <li><strong>Monday:</strong> 09:00 - 17:00</li>
    <li><strong>Saturday:</strong> Closed</li>
  </ul>
</div>
```

---

### `[lw_map]`

Display a Google Maps embed of your business location.

**Requires:** Latitude and longitude set in Local SEO settings.

**Attributes:**

| Attribute | Default | Description |
|-----------|---------|-------------|
| width | `100%` | Map width (px or %) |
| height | `400` | Map height (px) |
| zoom | `15` | Zoom level (1-20) |

**Examples:**

```
[lw_map]

[lw_map height="300" zoom="17"]

[lw_map width="600" height="400"]
```

**Output:**
```html
<div class="lw-seo-map" style="width:100%;height:400px;">
  <iframe
    src="https://maps.google.com/maps?q=123+Main+Street%2C+Budapest&output=embed&z=15"
    width="100%"
    height="100%"
    style="border:0;"
    allowfullscreen=""
    loading="lazy"
    referrerpolicy="no-referrer-when-downgrade"
    title="My Business">
  </iframe>
</div>
```

---

## Styling

Add custom CSS to style shortcode output:

```css
/* Breadcrumbs */
.lw-seo-breadcrumbs {
  padding: 10px 0;
  font-size: 14px;
}

.lw-seo-breadcrumbs ol {
  list-style: none;
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  margin: 0;
  padding: 0;
}

/* Address */
.lw-seo-address {
  font-style: normal;
  line-height: 1.6;
}

/* Hours Table */
.lw-seo-hours-table {
  width: 100%;
  border-collapse: collapse;
}

.lw-seo-hours-table th,
.lw-seo-hours-table td {
  padding: 8px;
  border-bottom: 1px solid #eee;
  text-align: left;
}

/* Map */
.lw-seo-map {
  border-radius: 8px;
  overflow: hidden;
}
```

## Tips

- Use shortcodes in widgets for sidebar/footer display
- Combine multiple shortcodes for a complete contact section
- Enable schema markup for better search visibility
- Test shortcode output after changing Local SEO settings
