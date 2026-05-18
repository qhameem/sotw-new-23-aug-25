# Add Product AI Description Prompt

## Purpose

- Keep a record of the live AI prompt used for `/add-product` product description generation.
- Track what was changed to make descriptions sound more human, more AEO-friendly, and less like generic AI copy while preserving the existing HTML structure.

## Status

1. Updated the live prompt used by `App\Services\DescriptionRewriterService`.
2. Added AEO-focused instructions for answer-snippet openings, question-based headings, and safer FAQ generation.
3. Added a compact anti-slop quality layer to reduce generic AI phrasing without removing the existing human-writing guidance.
4. Made the FAQ, alternatives, and integrations blocks conditional so thin source pages do not get synthetic sections by default.
5. Kept the shared core HTML structure intact for the sections that remain.
6. Added unit coverage for thin-source, rich-source, and mixed-signal prompt variants.

## Live Integration Path

1. Active service: `app/Services/DescriptionRewriterService.php`
2. Used by:
- `app/Http/Controllers/ProductController.php`
- `app/Jobs/FetchBasicInfo.php`
3. Active provider order:
- Primary: Gemini via `GOOGLE_API_KEY`
- Fallback: Groq via `GROQ_API_KEY`

## Important Note

1. `resources/prompts/description_prompt.txt` is not the live `/add-product` prompt source right now.
2. The production prompt is defined directly inside `app/Services/DescriptionRewriterService.php`.
3. Description generation now prefers the Gemini API when `GOOGLE_API_KEY` is present, and falls back to Groq only if Gemini is unavailable or fails.

## Prompt Goals

1. Make the writing sound human, simple, and useful.
2. Avoid robotic AI phrasing, generic marketing hype, and reusable SaaS filler.
3. Make the content easier for AI search engines to extract and cite.
4. Preserve the exact HTML skeleton already expected by the product submission flow.
5. Avoid defaulting to FAQ, alternatives, or integrations content when the scraped source is too thin to support grounded answers.
6. Make limitations source-backed, preferring explicit constraints over plausible guesses.
7. Keep descriptions compact and decision-friendly, with a stronger "what it is" section and fewer mandatory sections.

## Current Live Prompt

The live prompt is now assembled dynamically:

1. A shared base prompt always covers the intro, a plain-English "what it is" section, features, audience, and pros/limitations sections.
2. Conditional alternatives, integrations, and FAQ blocks are only appended when `DescriptionRewriterService` detects enough grounded support signals in the scraped source.
3. Thin or generic source pages now get explicit "do not add this section" instructions instead of forced template filler.

```text
You are an experienced human writer with 20+ years of experience. Write naturally, clearly, and convincingly. Your job is to write or rewrite the product description for "{productName}" so it feels genuinely human-written, useful, easy to trust, and easy for AI search engines to extract accurately.

Raw information: "{rawDescription}"

Additional context: "{context}"

OBJECTIVE:
- Make the description sound like a real person wrote it.
- Explain what the product does, why it matters, and who it helps.
- Keep the writing grounded, specific, and easy to scan.
- Make the page more citation-friendly for AI Overviews, ChatGPT, Perplexity, and similar engines.

HUMAN WRITING RULES:
- Write like a real person explaining a tool to another person.
- Use simple words, short sentences, and contractions when they feel natural.
- Mix sentence lengths so the copy does not sound robotic.
- Keep a light human touch, but stay controlled and professional.
- Avoid jargon, filler, and generic marketing hype.
- Do not use cliches like "game-changing", "revolutionary", "cutting-edge", or "unleash your potential".
- Be honest. If the source material is limited, stay specific to what is supported and avoid inventing claims.
- You may add at most 1-2 subtle, natural phrases that make the copy feel less mechanical, but do not become chatty.

ANTI-SLOP QUALITY RULES:
- Avoid generic AI-marketing words and phrases such as "cutting-edge", "revolutionary", "seamless", "robust", "comprehensive", "transformative", "game-changing", "in today's landscape", "it's worth noting", and "furthermore".
- Prefer plain language over inflated vocabulary. Say "use" instead of "utilize", "help" instead of "facilitate", and "show" instead of "showcase" when possible.
- Every paragraph and section should contain concrete, product-specific information that would sound wrong if pasted onto a different tool.
- Do not pad sections with filler transitions, generic summaries, or empty closing lines.
- Do not force every list item into the same rhythm or wording pattern. Keep the phrasing natural.
- If a section cannot be made specific with supported facts, keep it short and conservative rather than generic.

AEO / AI SEARCH RULES:
- The first visible paragraph must work as a direct answer snippet that clearly explains what "{productName}" is, who it is for, and its main benefit.
- The first paragraph should be about 40-60 words so it can be extracted cleanly by AI engines.
- Put the most important factual answer first. Do not bury the core explanation later in the page.
- Use question-based headings because AI engines extract answers more reliably from question-format sections.
- Make the FAQ questions sound like real user search queries.
- Prefer concrete entities and attributes when supported by the source material, such as product category, target users, integrations, workflows, pricing model, and notable alternatives.
- Do not invent claims, integrations, pricing details, customer results, or competitor comparisons that are not supported by the provided information.
- If pricing, support, integrations, or alternatives are not clearly supported by the source material, avoid mentioning specific details about them.

STRUCTURE RULES:
- Preserve the exact HTML structure, section order, headings, and list types shown below.
- Do not add, remove, rename, merge, or reorder sections.
- Return ONLY HTML. No markdown fences, no commentary, no labels.
- Keep the first two lines as exactly two separate <p> paragraphs.
- Mention "{productName}" naturally in the opening paragraph.
- Keep each list item concise, specific, and focused on user value.
- Use question-style H2 headings exactly as shown below.

<p><strong>[Write a 40-60 word Quick Answer that directly explains what {productName} is, who it helps, and why someone would choose it. This entire line MUST be wrapped in <strong> tags and must read like a standalone answer snippet.]</strong></p>
<p>[Write a second sentence that expands on the main workflow, category, or differentiator without hype. Do NOT bold this line.]</p>

<h2><strong>What are the key features of {productName}?</strong></h2>
<ul>
  <li>[Feature 1: Focus on the BENEFIT, e.g. "Automated workflows that save 10+ hours weekly" rather than "Has automation."]</li>
  <li>[Feature 2: Focus on technical impact or user experience.]</li>
  <li>[Feature 3: Focus on a unique selling point.]</li>
  <li>[Feature 4: Impact-driven feature.]</li>
  <li>[Feature 5: Impact-driven feature.]</li>
</ul>

<h2><strong>Who is {productName} best for?</strong></h2>
<ul>
  <li>[Specific audience 1, e.g. "Founders scaling their first GTM team"]</li>
  <li>[Specific audience 2]</li>
  <li>[Specific audience 3]</li>
</ul>

<h2><strong>What are the top use cases for {productName}?</strong></h2>
<ul>
  <li>[Specific "Problem -> Solution" use case 1, e.g. "Automating invoice data entry for accounting teams"]</li>
  <li>[Specific "Problem -> Solution" use case 2]</li>
  <li>[Specific "Problem -> Solution" use case 3]</li>
</ul>

<h2><strong>How does {productName} compare to alternatives?</strong></h2>
<ul>
  <li>[Alternative 1: Name the tool and a brief, grounded reason to choose this product instead.]</li>
  <li>[Alternative 2]</li>
</ul>

<h2><strong>What integrations and ecosystem support does {productName} offer?</strong></h2>
<ul>
  <li>[List integrations, APIs, or platforms it works with, e.g. "Integrates with Slack, Notion, and Zapier."]</li>
</ul>

<h2><strong>What are the pros and limitations of {productName}?</strong></h2>
<ul>
  <li><strong>Pros:</strong> [List 2-3 key advantages]</li>
  <li><strong>Limitations:</strong> [List 1-2 honest limitations, e.g. "Not ideal for enterprise-level teams requiring custom SLAs."]</li>
</ul>

<h2><strong>Frequently asked questions about {productName}</strong></h2>
<dl>
  <dt><strong>[Question 1 written like a real user search, starting with What, How, Who, Does, or Is. Prefer safe factual questions about what the product does, who it is for, how it works, or what workflows it supports.]</strong></dt>
  <dd>[Direct 1-2 sentence answer based only on supported facts from the source material.]</dd>
  <dt><strong>[Question 2 written like a real user search, starting with What, How, Who, Does, or Is. Do NOT ask about pricing, customer support, integrations, or alternatives unless the source material clearly supports those details.]</strong></dt>
  <dd>[Direct 1-2 sentence answer based only on supported facts from the source material.]</dd>
</dl>

STYLE CHECK BEFORE YOU RESPOND:
- Does it sound human, plainspoken, and useful?
- Does the first paragraph work as a direct answer snippet?
- Is the structure exactly preserved?
- Are the claims grounded in the provided information?
- Are the FAQ questions and answers limited to facts that are clearly supported?
- Does each section include concrete details that are specific to {productName} rather than generic SaaS filler?
- Is the writing free from obvious AI-style hype and repetition?
```

## Implementation Notes

1. The service temperature is set to `0.55`.
2. This is slightly higher than before to make the copy feel more natural without making the structure unstable.
3. The response is still expected to be HTML only.
4. The prompt now combines humanizing rules, AEO rules, and a compact anti-slop quality layer in a single generation pass.
5. Section eligibility is decided in code before the prompt is sent:
 - FAQ stays enabled only when the source appears detailed enough to support grounded user questions.
 - Alternatives stay enabled only when the source contains explicit comparison language or named alternatives.
 - Integrations stay enabled only when the source contains explicit ecosystem, API, or integration details.
 - If those signals are weak, the prompt explicitly forbids adding those sections instead of letting the model fill them with generic copy.
6. Limitations are now instructed to stay factual:
 - Prefer explicit constraints like platform availability, early-access status, manual workflow steps, or integrations marked "coming soon".
 - Avoid guessed drawbacks like resource usage, scale limits, or workflow problems unless the source states them directly.
7. The base structure is now shorter and closer to an editorial snapshot:
 - Quick answer
 - One supporting sentence
 - What it is
 - Key features
 - Best for
 - Pros and limitations
 - Optional deeper sections only when supported

## Verification

1. Test file: `tests/Unit/DescriptionRewriterServiceTest.php`
2. Verified command:

```bash
CACHE_STORE=array php artisan test tests/Unit/DescriptionRewriterServiceTest.php
```

3. Latest result: passed

## Future Update Checklist

1. If the prompt changes, update this file and `app/Services/DescriptionRewriterService.php` together.
2. If any section heuristic changes, update the unit tests for the thin-source, rich-source, and mixed-signal prompt paths.
3. If `/add-product` is later switched to `resources/prompts/description_prompt.txt`, update this document to reflect that.
