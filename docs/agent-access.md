# Public agent access

- `/llms.txt`: discovery guide linked from the footer.
- `/product/{slug}/document.md`: Markdown from the existing public product description or product facts.
- `/product/{slug}/document.json`: version 1 JSON with an explicit public-field allowlist; description content uses Markdown.
- Product HTML advertises both representations with alternate links. Responses identify the HTML canonical URL and use `noindex, follow` to avoid duplicate search listings.
- Only approved, published products without `generation_noindex` are accessible, regardless of login. Unknown, withdrawn, draft, and private records return 404.
- Reads do not update product view/click counters. No external requests, generation, credentials, user records, pending edits, MCP server, or CLI are involved.
- Responses bypass shared page caching. Content reflects current database records without a second content store or cache invalidation requirement.
- Deploy through the normal Laravel release process; rebuild route/view caches if used. No migration, environment setting, or frontend build is required.

JSON fields: `schema_version`, `name`, `slug`, `url`, `website`, `tagline`, `description_markdown`, `categories`, `published_at`, `updated_at`. Dates are ISO 8601 or null; invalid/non-HTTP website URLs become null. Missing information does not imply zero pricing or absent features.

`llms.txt` is an experimental discovery convention, not a guarantee of crawler adoption, citation, or rankings.
