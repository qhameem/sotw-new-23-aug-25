# Add Product AI Description Prompt

## Purpose

1. Keep a record of the live AI description flow used for `/add-product`.
2. Document the switch from direct HTML generation to structured JSON generation plus deterministic Laravel rendering.
3. Track the provider order, optional-section rules, and output contract used by `App\Services\DescriptionRewriterService`.

## Current Status

1. Description generation now uses a JSON-first pipeline.
2. Gemini is the primary provider when `GOOGLE_API_KEY` is present.
3. Groq is the fallback provider when Gemini is unavailable or fails.
4. The model now returns structured JSON instead of final HTML.
5. Laravel renders the final HTML itself, which removes heading drift, nested-list issues, and most formatting instability.
6. When the drafted editorial fields still look generic, the service now runs a narrow second-pass edit on `summary`, `what_it_is`, and `pros`.
7. If those fields still contain known low-quality patterns, the service now rejects and repairs them one field at a time.
8. The service now detects a few product patterns and can add a product-type writing brief for specific tool shapes.

## Live Integration Path

1. Active service: `app/Services/DescriptionRewriterService.php`
2. Used by:
- `app/Http/Controllers/ProductController.php`
- `app/Jobs/FetchBasicInfo.php`
3. Provider order:
- Primary: Gemini via `GOOGLE_API_KEY`
- Fallback: Groq via `GROQ_API_KEY`

## Why This Changed

1. Direct HTML generation was too unstable.
2. Repeated prompt tuning and post-processing still allowed:
- duplicated section labels
- awkward phrasing
- formatting drift
- optional sections appearing in the wrong shape
3. JSON-first generation makes the model responsible for content only.
4. Laravel now controls:
- HTML structure
- section order
- list rendering
- FAQ rendering
- gating of optional sections

## Live Flow

1. Scraped product context is cleaned and trimmed.
2. The service decides whether to allow:
- `alternatives`
- `integrations`
- `faq`
3. The prompt tells the model to return valid JSON only.
4. The provider response is decoded.
5. If the drafted editorial fields are too generic, the same provider rewrites only `summary`, `what_it_is`, and `pros`.
6. The service applies a quality gate to `summary`, `what_it_is`, and `pros`, and repairs only the fields that still look generic.
7. The service cleans individual strings and arrays.
8. Laravel renders the final HTML from the structured payload.

## Product-Type Guidance

1. The service can now detect some product shapes from the scraped source.
2. Current special case:
- `link_to_website`
3. This fires for tools that turn existing links, listings, profiles, or reviews into a website.
4. When detected, the prompt tells the model to:
- describe the transformation from existing source content to a ready-to-use site
- avoid generic `online presence` framing
- avoid over-focusing on one source like `Google Maps` when several are supported
- emphasize reuse of existing content, preview-before-publish workflow, and reduced manual setup

## Prompt Goals

1. Keep descriptions short, specific, and easy to scan.
2. Help a user understand the product quickly without stuffing every page with template filler.
3. Keep the tone calm and editorial rather than promotional.
4. Avoid unsupported claims, guessed limitations, and generic comparison copy.
5. Use optional sections only when the source clearly supports them.

## Current Output Contract

The model is asked to return this JSON shape:

```json
{
  "summary": "string",
  "supporting_sentence": "string",
  "what_it_is": "string",
  "key_features": ["string"],
  "best_for": ["string"],
  "pros": ["string"],
  "limitations": ["string"],
  "alternatives": ["string"],
  "integrations": ["string"],
  "faq": [
    {
      "question": "string",
      "answer": "string"
    }
  ]
}
```

## Rendering Rules

Laravel always renders the stable base structure:

1. Strong summary paragraph
2. Supporting paragraph
3. `What is {productName}?`
4. `What are the key features of {productName}?`
5. `Who is {productName} best for?`
6. `What are the pros and limitations of {productName}?`

Optional sections are rendered only when both conditions are true:

1. The source signals support the section.
2. The returned array contains valid items.

Optional sections:

1. `How does {productName} compare to alternatives?`
2. `What integrations and ecosystem support does {productName} offer?`
3. `Frequently asked questions about {productName}`

## Optional Section Heuristics

### Alternatives

1. Enabled when the scraped source contains grounded comparison language.
2. Signals include phrases like:
- `compare`
- `comparison`
- `alternative`
- `alternatives`
- `versus`
- `vs`
- `unlike`
- `switch from`

### Integrations

1. Enabled when the source contains both ecosystem-style language and named supported platforms or tools.
2. Signals include phrases like:
- `integration`
- `integrations`
- `api`
- `sdk`
- `webhook`
- `plugin`
- `extension`
3. Named-platform checks include tools or connected platforms mentioned in the source.

### FAQ

1. Enabled only when the source is detailed enough to support grounded Q&A.
2. Current rule requires:
- enough text volume
- at least two question-style headings
- at least one strong support signal such as docs, setup, pricing, integrations, or workflows

## Tone and Safety Rules

The prompt currently emphasizes:

1. Plain language
2. Short, specific sentences
3. Compact arrays
4. Real platform names when available
5. Source-backed limitations

The prompt also tells the model to avoid:

1. HTML output
2. Markdown output
3. fenced code blocks
4. hype-heavy words and empty marketing language
5. invented integrations, pricing, comparisons, or limitations

## Deterministic Cleanup

There is still a small cleanup pass, but it is now limited to string normalization rather than full HTML repair.

Current cleanup focuses on:

1. stripping tags and Markdown fences
2. removing Markdown bold markers
3. normalizing a short list of recurring weak phrases
4. collapsing repeated whitespace

## Second-Pass Field Editor

1. The first provider call still produces the main JSON payload.
2. If the draft contains generic phrases like `online presence`, `quickly`, `easy to use`, or too much repeated platform naming, the service asks the same provider to rewrite only:
- `summary`
- `what_it_is`
- `pros`
3. This editor keeps the stable JSON shape while improving specificity and tone in the fields that most affect first impressions.

## Quality Gate

1. After the second-pass editor, the service checks for recurring low-quality patterns such as:
- `online presence`
- `in seconds`
- `various platforms`
- `supports multiple platforms`
- pros that read like filler instead of concrete value
2. If a field fails the check, the service asks the same provider to rewrite only that one field.
3. This keeps repairs narrow and avoids disturbing the stable HTML structure.

## Verification

1. Test file: `tests/Unit/DescriptionRewriterServiceTest.php`
2. Coverage now checks:
- deterministic HTML rendering from structured JSON
- Gemini preference
- Groq fallback
- second-pass refinement of generic editorial fields
- field-level repair of low-quality editorial copy
- optional-section inclusion
- optional-section omission
- invalid JSON handling

## Future Update Checklist

1. If the JSON shape changes, update:
- `app/Services/DescriptionRewriterService.php`
- `tests/Unit/DescriptionRewriterServiceTest.php`
- this document
2. If section heuristics change, update the relevant tests.
3. If rendering structure changes, document the new HTML contract here.
