# Local SEO Settings

Navigate to **LW Plugins → SEO → Local SEO** to configure LocalBusiness schema markup.

## Overview

Local SEO helps your business appear in local search results and Google Maps. The plugin generates Schema.org LocalBusiness structured data.

## Enable Local SEO

Toggle LocalBusiness schema generation on/off.

## Business Information

### Business Type

Select the Schema.org type that best describes your business. 100+ options available including:

| Category | Examples |
|----------|----------|
| Food | Restaurant, Bakery, CafeOrCoffeeShop, FastFoodRestaurant |
| Retail | Store, ClothingStore, ElectronicsStore, GroceryStore |
| Services | AutoRepair, HairSalon, DaySpa, RealEstateAgent |
| Health | Dentist, Pharmacy, MedicalClinic, Optician |
| Entertainment | MovieTheater, NightClub, SportingGoodsStore |
| Professional | AccountingService, Attorney, FinancialService |

### Business Name

Your official business name. Leave empty to use the site name.

### Business Description

A short description of your business (1-2 sentences).

### Price Range

Indicate your price level:
- `$` - Budget
- `$$` - Moderate
- `$$$` - Expensive
- `$$$$` - Luxury

## Address

Enter your physical business address:

| Field | Description | Example |
|-------|-------------|---------|
| Street Address | Primary street address | 123 Main Street |
| Address Line 2 | Suite, unit, floor | Suite 100 |
| City | City/locality | Budapest |
| State/Region | State, province, region | Budapest |
| Postal Code | ZIP/postal code | 1051 |
| Country | ISO 3166-1 alpha-2 code | HU |

**Country codes:** US, HU, DE, GB, FR, etc.

## Contact Information

### Phone

Enter your business phone number in international format:
```
+36 1 234 5678
```

### Email

Business contact email:
```
info@yourbusiness.com
```

## Opening Hours

### Enable Opening Hours

Toggle opening hours in the schema output.

### Configure Hours

For each day of the week:

1. **Closed** - Check if closed on this day
2. **Open time** - Opening time (e.g., 09:00)
3. **Close time** - Closing time (e.g., 17:00)

Use 24-hour format for times.

## Coordinates

### Latitude & Longitude

Enter your exact location coordinates for map accuracy.

**Finding coordinates:**
1. Go to [Google Maps](https://maps.google.com)
2. Right-click on your location
3. Click the coordinates to copy them
4. First number is latitude, second is longitude

**Example:**
- Latitude: `47.4979`
- Longitude: `19.0402`

## Generated Schema

The plugin outputs JSON-LD structured data:

```json
{
  "@context": "https://schema.org",
  "@type": "Restaurant",
  "name": "My Business",
  "description": "A great local business",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "123 Main Street",
    "addressLocality": "Budapest",
    "postalCode": "1051",
    "addressCountry": "HU"
  },
  "telephone": "+36 1 234 5678",
  "email": "info@example.com",
  "priceRange": "$$",
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": 47.4979,
    "longitude": 19.0402
  },
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": "Monday",
      "opens": "09:00",
      "closes": "17:00"
    }
  ]
}
```

## Shortcodes

Display your business info on the frontend using shortcodes. See [Shortcodes](shortcodes.md) for details:

- `[lw_address]` - Display address
- `[lw_phone]` - Display phone number
- `[lw_email]` - Display email
- `[lw_hours]` - Display opening hours
- `[lw_map]` - Display Google Maps embed

## Testing

Validate your Local SEO markup:

- [Google Rich Results Test](https://search.google.com/test/rich-results)
- [Schema.org Validator](https://validator.schema.org/)

## Tips

- Use the most specific business type available
- Keep business info consistent across the web (NAP consistency)
- Add coordinates for accurate map placement
- Update hours for holidays using your Google Business Profile
