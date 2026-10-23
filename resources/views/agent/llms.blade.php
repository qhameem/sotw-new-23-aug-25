# Software on the Web

> A directory for discovering software, product information, alternatives, and comparisons.

## Browse

- [Software directory]({{ route('home') }})
- [Alternatives and comparisons]({{ route('alternatives.index') }})
- [Articles]({{ route('articles.index') }})
- [Sitemap]({{ url('/sitemap.xml') }})

## Product documents

For a canonical product URL {{ url('/product/{slug}') }}, append /document.md or /document.json:

- Markdown: {{ url('/product/{slug}/document.md') }}
- JSON: {{ url('/product/{slug}/document.json') }}

Use an existing product slug from the directory or sitemap. Only approved, published, indexable products are available. Missing or withdrawn products return 404.

JSON schema_version 1 fields: name, slug, url (canonical HTML), website, tagline, description_markdown, categories, published_at, updated_at. Dates use ISO 8601; unavailable dates and websites are null. Categories is an array of names. Content comes from existing directory records; omissions do not imply a feature is unavailable or a product is free. Product information can change; consult the linked official website for current details.

These endpoints require no authentication and do not record product views or outbound clicks. Cite the canonical HTML product URL. This file is a discovery guide, not an access-control policy.
