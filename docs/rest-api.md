# LW SEO REST API

REST API endpoints for headless WordPress implementations. Get SEO meta data, Schema.org JSON-LD, and breadcrumbs in JSON format.

## Base URL

```
/wp-json/lw-seo/v1/
```

## Authentication

All endpoints are publicly accessible (read-only). No authentication required for published content.

For draft/private content, use WordPress Application Passwords with Basic Auth:
```
Authorization: Basic base64(username:app_password)
```

---

## Endpoints

### Get Post SEO Meta

Returns SEO meta data for a post, page, or custom post type.

**Endpoint:** `GET /wp-json/lw-seo/v1/meta/{id}`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Post ID |

**Response:**
```json
{
  "title": "Post Title - Site Name",
  "description": "Meta description for the post...",
  "canonical": "https://example.com/post-slug/",
  "robots": {
    "index": "index",
    "follow": "follow"
  },
  "og": {
    "locale": "en_US",
    "type": "article",
    "title": "Post Title",
    "description": "OG description...",
    "url": "https://example.com/post-slug/",
    "site_name": "Site Name",
    "image": "https://example.com/wp-content/uploads/image.jpg",
    "article:published_time": "2024-01-15T10:30:00+00:00",
    "article:modified_time": "2024-01-16T14:22:00+00:00",
    "article:author": "John Doe"
  },
  "twitter": {
    "card": "summary_large_image",
    "title": "Post Title",
    "description": "Twitter description...",
    "image": "https://example.com/wp-content/uploads/image.jpg"
  }
}
```

**Example:**
```bash
curl "https://example.com/wp-json/lw-seo/v1/meta/123"
```

**Errors:**
- `404` - Post not found
- `403` - Post is not accessible (draft/private without auth)

---

### Get Term SEO Meta

Returns SEO meta data for a taxonomy term (category, tag, custom taxonomy).

**Endpoint:** `GET /wp-json/lw-seo/v1/meta/term/{id}`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Term ID |
| taxonomy | string | No | Taxonomy name (default: `category`) |

**Response:**
```json
{
  "title": "Category Name - Site Name",
  "description": "Category description...",
  "canonical": "https://example.com/category/slug/",
  "robots": {
    "index": "index",
    "follow": "follow"
  },
  "og": {
    "locale": "en_US",
    "type": "website",
    "title": "Category Name - Site Name",
    "description": "Category description...",
    "url": "https://example.com/category/slug/",
    "site_name": "Site Name",
    "image": "https://example.com/wp-content/uploads/default-og.jpg"
  },
  "twitter": {
    "card": "summary_large_image",
    "title": "Category Name - Site Name",
    "description": "Category description...",
    "image": "https://example.com/wp-content/uploads/default-og.jpg"
  }
}
```

**Examples:**
```bash
# Get category meta
curl "https://example.com/wp-json/lw-seo/v1/meta/term/5"

# Get custom taxonomy term meta
curl "https://example.com/wp-json/lw-seo/v1/meta/term/10?taxonomy=product_cat"
```

**Errors:**
- `404` - Term not found

---

### Get Author SEO Meta

Returns SEO meta data for an author archive.

**Endpoint:** `GET /wp-json/lw-seo/v1/meta/author/{id}`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | User/Author ID |

**Response:**
```json
{
  "title": "John Doe - Site Name",
  "description": "Author bio description...",
  "canonical": "https://example.com/author/johndoe/",
  "robots": {
    "index": "index",
    "follow": "follow"
  },
  "og": {
    "locale": "en_US",
    "type": "profile",
    "title": "John Doe - Site Name",
    "description": "Author bio description...",
    "url": "https://example.com/author/johndoe/",
    "site_name": "Site Name",
    "image": "https://secure.gravatar.com/avatar/..."
  },
  "twitter": {
    "card": "summary",
    "title": "John Doe - Site Name",
    "description": "Author bio description...",
    "image": "https://secure.gravatar.com/avatar/..."
  }
}
```

**Example:**
```bash
curl "https://example.com/wp-json/lw-seo/v1/meta/author/1"
```

**Errors:**
- `404` - Author not found

---

### Get Post Schema

Returns Schema.org JSON-LD structured data for a post.

**Endpoint:** `GET /wp-json/lw-seo/v1/schema/{id}`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Post ID |

**Response:**
```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "WebSite",
      "@id": "https://example.com/#website",
      "url": "https://example.com/",
      "name": "Site Name",
      "description": "Site tagline",
      "inLanguage": "en_US",
      "potentialAction": {
        "@type": "SearchAction",
        "target": {
          "@type": "EntryPoint",
          "urlTemplate": "https://example.com/?s={search_term_string}"
        },
        "query-input": "required name=search_term_string"
      },
      "publisher": {
        "@id": "https://example.com/#organization"
      }
    },
    {
      "@type": "Organization",
      "@id": "https://example.com/#organization",
      "name": "Company Name",
      "url": "https://example.com/",
      "logo": {
        "@type": "ImageObject",
        "@id": "https://example.com/#logo",
        "url": "https://example.com/logo.png"
      },
      "sameAs": [
        "https://facebook.com/example",
        "https://twitter.com/example"
      ]
    },
    {
      "@type": "WebPage",
      "@id": "https://example.com/post-slug/#webpage",
      "url": "https://example.com/post-slug/",
      "name": "Post Title",
      "isPartOf": {"@id": "https://example.com/#website"},
      "inLanguage": "en_US",
      "datePublished": "2024-01-15T10:30:00+00:00",
      "dateModified": "2024-01-16T14:22:00+00:00",
      "primaryImageOfPage": {"@id": "https://example.com/#primaryimage"},
      "thumbnailUrl": "https://example.com/wp-content/uploads/image.jpg"
    },
    {
      "@type": "Article",
      "@id": "https://example.com/post-slug/#article",
      "headline": "Post Title",
      "datePublished": "2024-01-15T10:30:00+00:00",
      "dateModified": "2024-01-16T14:22:00+00:00",
      "mainEntityOfPage": {"@id": "https://example.com/post-slug/#webpage"},
      "wordCount": 1250,
      "inLanguage": "en_US",
      "author": {
        "@type": "Person",
        "@id": "https://example.com/author/johndoe/#author",
        "name": "John Doe",
        "url": "https://example.com/author/johndoe/"
      },
      "publisher": {"@id": "https://example.com/#organization"},
      "image": {
        "@type": "ImageObject",
        "@id": "https://example.com/#primaryimage",
        "url": "https://example.com/wp-content/uploads/image.jpg"
      },
      "keywords": "Category 1, Category 2"
    }
  ]
}
```

**Example:**
```bash
curl "https://example.com/wp-json/lw-seo/v1/schema/123"
```

**Errors:**
- `404` - Post not found
- `403` - Post is not accessible

---

### Get Post Breadcrumbs

Returns breadcrumb navigation items for a post.

**Endpoint:** `GET /wp-json/lw-seo/v1/breadcrumbs/{id}`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Post ID |

**Response:**
```json
[
  {
    "title": "Home",
    "url": "https://example.com/",
    "position": 1
  },
  {
    "title": "Category Name",
    "url": "https://example.com/category/slug/",
    "position": 2
  },
  {
    "title": "Post Title",
    "url": "https://example.com/post-slug/",
    "position": 3
  }
]
```

**Example:**
```bash
curl "https://example.com/wp-json/lw-seo/v1/breadcrumbs/123"
```

**Errors:**
- `404` - Post not found
- `403` - Post is not accessible

---

## Usage Examples

### Next.js / React

```javascript
// Fetch SEO data for a post
async function getPostSEO(postId) {
  const response = await fetch(
    `https://example.com/wp-json/lw-seo/v1/meta/${postId}`
  );
  return response.json();
}

// Use in Next.js page
export async function generateMetadata({ params }) {
  const seo = await getPostSEO(params.id);

  return {
    title: seo.title,
    description: seo.description,
    alternates: {
      canonical: seo.canonical,
    },
    openGraph: {
      title: seo.og.title,
      description: seo.og.description,
      url: seo.og.url,
      siteName: seo.og.site_name,
      images: seo.og.image ? [seo.og.image] : [],
      locale: seo.og.locale,
      type: seo.og.type,
    },
    twitter: {
      card: seo.twitter.card,
      title: seo.twitter.title,
      description: seo.twitter.description,
      images: seo.twitter.image ? [seo.twitter.image] : [],
    },
    robots: {
      index: seo.robots.index === 'index',
      follow: seo.robots.follow === 'follow',
    },
  };
}
```

### Nuxt.js / Vue

```javascript
// composables/useSeo.js
export async function useSeo(postId) {
  const { data: seo } = await useFetch(
    `https://example.com/wp-json/lw-seo/v1/meta/${postId}`
  );

  useHead({
    title: seo.value.title,
    meta: [
      { name: 'description', content: seo.value.description },
      { property: 'og:title', content: seo.value.og.title },
      { property: 'og:description', content: seo.value.og.description },
      { property: 'og:image', content: seo.value.og.image },
      { name: 'twitter:card', content: seo.value.twitter.card },
    ],
    link: [
      { rel: 'canonical', href: seo.value.canonical },
    ],
  });

  return seo;
}
```

### PHP (Server-side)

```php
// Fetch SEO data from another WordPress site
$response = wp_remote_get( 'https://example.com/wp-json/lw-seo/v1/meta/123' );
$seo = json_decode( wp_remote_retrieve_body( $response ), true );

// Use the data
echo '<title>' . esc_html( $seo['title'] ) . '</title>';
echo '<meta name="description" content="' . esc_attr( $seo['description'] ) . '">';
```

---

## Error Responses

All error responses follow this format:

```json
{
  "code": "error_code",
  "message": "Human readable error message",
  "data": {
    "status": 404
  }
}
```

| Code | Status | Description |
|------|--------|-------------|
| `post_not_found` | 404 | The requested post does not exist |
| `post_not_accessible` | 403 | Post is draft/private and user lacks permission |
| `term_not_found` | 404 | The requested term does not exist |
| `author_not_found` | 404 | The requested author does not exist |
