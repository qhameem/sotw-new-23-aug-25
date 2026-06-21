<?php

namespace App\Support;

class LaunchReadinessGuideLibrary
{
    private ?array $guides = null;

    private ?array $guidesBySlug = null;

    public function all(): array
    {
        if ($this->guides !== null) {
            return $this->guides;
        }

        $guides = [];
        $guidesBySlug = [];

        foreach ($this->definitions() as $key => $definition) {
            $guide = $this->buildGuide($key, $definition);
            $guides[$key] = $guide;
            $guidesBySlug[$guide['slug']] = $guide;
        }

        $this->guides = $guides;
        $this->guidesBySlug = $guidesBySlug;

        return $this->guides;
    }

    public function findByKey(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    public function findBySlug(string $slug): ?array
    {
        $this->all();

        return $this->guidesBySlug[$slug] ?? null;
    }

    public function urlForKey(string $toolSlug, string $key): ?string
    {
        $guide = $this->findByKey($key);

        if ($guide === null) {
            return null;
        }

        return route('launch-readiness.guides.show', [
            'toolSlug' => $toolSlug,
            'guideSlug' => $guide['slug'],
        ]);
    }

    private function buildGuide(string $key, array $definition): array
    {
        return [
            'key' => $key,
            'label' => $definition['label'],
            'slug' => $definition['slug'],
            'seo_title' => $definition['seo_title'],
            'meta_description' => $definition['meta_description'],
            'article_title' => $definition['article_title'],
            'markdown' => $this->buildMarkdown($definition),
        ];
    }

    private function buildMarkdown(array $definition): string
    {
        $lines = [];

        foreach ($definition['intro'] as $paragraph) {
            $lines[] = $paragraph;
            $lines[] = '';
        }

        $lines[] = '## What It Is';
        $lines[] = '';

        foreach ($definition['what_is']['paragraphs'] as $paragraph) {
            $lines[] = $paragraph;
            $lines[] = '';
        }

        if (! empty($definition['what_is']['code'] ?? null)) {
            $lines[] = '```'.($definition['what_is']['language'] ?? '');
            $lines[] = $definition['what_is']['code'];
            $lines[] = '```';
            $lines[] = '';
        }

        $lines[] = '## Why It Matters';
        $lines[] = '';

        foreach ($definition['why_it_matters'] as $item) {
            $lines[] = '- '.$item;
        }

        $lines[] = '';
        $lines[] = '## Best Practices';
        $lines[] = '';

        foreach (array_values($definition['best_practices']) as $index => $item) {
            $lines[] = ($index + 1).'. '.$item;
        }

        $lines[] = '';

        if (! empty($definition['example'] ?? null)) {
            $lines[] = '## Example';
            $lines[] = '';

            if (! empty($definition['example']['intro'] ?? null)) {
                $lines[] = $definition['example']['intro'];
                $lines[] = '';
            }

            if (! empty($definition['example']['code'] ?? null)) {
                $lines[] = '```'.($definition['example']['language'] ?? '');
                $lines[] = $definition['example']['code'];
                $lines[] = '```';
                $lines[] = '';
            }

            foreach ($definition['example']['points'] ?? [] as $item) {
                $lines[] = '- '.$item;
            }

            $lines[] = '';
        }

        $lines[] = '## Common Mistakes';
        $lines[] = '';

        foreach ($definition['common_mistakes'] as $item) {
            $lines[] = '- '.$item;
        }

        $lines[] = '';
        $lines[] = '## Quick Checklist';
        $lines[] = '';

        foreach ($definition['checklist'] as $item) {
            $lines[] = '- '.$item;
        }

        $lines[] = '';
        $lines[] = '## Final Takeaway';
        $lines[] = '';

        foreach ($definition['takeaway'] as $paragraph) {
            $lines[] = $paragraph;
            $lines[] = '';
        }

        return trim(implode("\n", $lines));
    }

    private function definitions(): array
    {
        return [
            'title_tag' => [
                'label' => 'Title Tag',
                'slug' => 'title-tag',
                'seo_title' => 'Title Tag Guide for Better Search Clicks',
                'meta_description' => 'Learn what a title tag does, why it matters, and how to write cleaner titles before launching a page.',
                'article_title' => 'Title Tag: Write Search Titles That Explain the Page Fast',
                'intro' => [
                    'A title tag is one of the first signals search engines and people use to understand a page.',
                    'If the title is vague, duplicated, or too long, you make ranking and click-through harder than it needs to be.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'The title tag is the `<title>` element inside the document head. It usually becomes the main title shown in browser tabs and search snippets.',
                        'It is separate from the visible page heading. Your H1 appears on the page. The title tag describes the page to external systems.',
                    ],
                    'code' => '<title>Website Launch Checker for SEO and QA Reviews</title>',
                    'language' => 'html',
                ],
                'why_it_matters' => [
                    'It helps search engines map the page to the right query intent.',
                    'It shapes the main clickable line users see in search results.',
                    'It improves clarity in tabs, bookmarks, and shared previews.',
                ],
                'best_practices' => [
                    'Lead with the primary topic instead of filler or brand-only wording.',
                    'Keep it readable at roughly 30 to 60 characters.',
                    'Make every important URL unique.',
                    'Match the wording to what the page actually delivers.',
                    'Append the brand at the end only if it adds trust or recognition.',
                ],
                'example' => [
                    'intro' => 'A clean title is specific, short, and aligned with the page intent.',
                    'code' => '<title>Free Website Launch Checker for SEO Audits | Software on the Web</title>',
                    'language' => 'html',
                    'points' => [
                        'The main topic appears early.',
                        'The wording is clear to both crawlers and humans.',
                        'The brand sits at the end instead of dominating the title.',
                    ],
                ],
                'common_mistakes' => [
                    'Using defaults like `Home` or `Untitled Page`.',
                    'Repeating keywords in a robotic way.',
                    'Reusing the same title across multiple URLs.',
                    'Writing a title that promises something the page does not cover.',
                ],
                'checklist' => [
                    'One unique title per URL.',
                    'Primary topic near the front.',
                    'Readable length.',
                    'Matches on-page content.',
                    'Strong enough to earn the click.',
                ],
                'takeaway' => [
                    'Small tag, high leverage. A stronger title improves both understanding and click potential.',
                ],
            ],
            'meta_description' => [
                'label' => 'Meta Description',
                'slug' => 'meta-description',
                'seo_title' => 'Meta Description Guide for Launch Pages',
                'meta_description' => 'Understand meta descriptions, snippet length, and how to write clearer search-result copy before launch.',
                'article_title' => 'Meta Description: Write Better Snippets for Search Results',
                'intro' => [
                    'A meta description is short copy written for the search snippet, not for the body of the page.',
                    'It does not usually move rankings directly, but it can materially change whether people choose your result.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'The meta description is a `<meta name="description">` tag placed in the document head.',
                        'Search engines may use it beneath the title when they need a short summary of the page.',
                    ],
                    'code' => '<meta name="description" content="Audit your homepage for launch blockers, SEO gaps, and trust signals before going live.">',
                    'language' => 'html',
                ],
                'why_it_matters' => [
                    'It sets expectations before the click.',
                    'It can improve click-through rate when the message is specific.',
                    'It helps your result look more polished against nearby competitors.',
                ],
                'best_practices' => [
                    'Keep it concise, usually around 120 to 160 characters.',
                    'Describe the actual value of the page instead of generic branding.',
                    'Use the main topic naturally inside a sentence.',
                    'Highlight the user outcome, not just the feature name.',
                    'Write a unique description for each meaningful page.',
                ],
                'example' => [
                    'intro' => 'A strong description tells the user what they get and why it matters.',
                    'code' => '<meta name="description" content="Check your site for title, meta, accessibility, trust, and launch-readiness issues in one free report.">',
                    'language' => 'html',
                    'points' => [
                        'The page benefit is obvious.',
                        'The language is natural.',
                        'The snippet does not waste space on filler.',
                    ],
                ],
                'common_mistakes' => [
                    'Leaving the tag empty.',
                    'Stuffing repeated keywords into one sentence.',
                    'Copying the same description onto many pages.',
                    'Writing a description that oversells or misleads.',
                ],
                'checklist' => [
                    'One description tag on the page.',
                    'Unique copy for that URL.',
                    'Matches the visible content.',
                    'Clear user benefit.',
                    'Reasonable snippet length.',
                ],
                'takeaway' => [
                    'Treat the meta description like ad copy for the organic result. Precision wins.',
                ],
            ],
            'canonical_url' => [
                'label' => 'Canonical URL',
                'slug' => 'canonical-url',
                'seo_title' => 'Canonical URL Guide for Duplicate Content Control',
                'meta_description' => 'Learn when to add canonical tags, what they signal, and how they reduce duplicate URL confusion.',
                'article_title' => 'Canonical URL: Tell Crawlers Which Version Should Count',
                'intro' => [
                    'Many pages can be reached through multiple URLs even when the content is effectively the same.',
                    'A canonical tag helps you point crawlers toward the preferred version.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'A canonical URL is declared with a `<link rel="canonical">` tag in the document head.',
                        'It signals which public URL should be treated as the primary version for indexing and consolidation.',
                    ],
                    'code' => '<link rel="canonical" href="https://example.com/pricing">',
                    'language' => 'html',
                ],
                'why_it_matters' => [
                    'It reduces ambiguity when parameters, trailing slashes, or alternate paths create duplicates.',
                    'It helps consolidate ranking signals to the preferred URL.',
                    'It gives your sitemap, internal links, and metadata a consistent destination.',
                ],
                'best_practices' => [
                    'Point the canonical tag to a live, public, indexable URL.',
                    'Use self-referencing canonicals on important pages.',
                    'Keep canonical targets consistent with redirects and sitemap entries.',
                    'Avoid pointing unrelated pages to the same destination.',
                ],
                'example' => [
                    'code' => '<link rel="canonical" href="https://example.com/features/launch-checker">',
                    'language' => 'html',
                    'points' => [
                        'The destination is clean and public.',
                        'The canonical points to the page users should actually share and link to.',
                    ],
                ],
                'common_mistakes' => [
                    'Canonicalizing to a redirected URL.',
                    'Pointing multiple distinct pages to one generic page.',
                    'Using relative or broken canonical links.',
                    'Leaving canonical logic inconsistent with internal linking.',
                ],
                'checklist' => [
                    'Canonical exists.',
                    'Destination returns a valid public page.',
                    'Destination matches the intended primary URL.',
                    'Redirects, sitemap, and internal links agree with it.',
                ],
                'takeaway' => [
                    'Canonical tags are not decoration. They are a consistency signal across your URL system.',
                ],
            ],
            'favicon' => [
                'label' => 'Favicon',
                'slug' => 'favicon',
                'seo_title' => 'Favicon Guide for Better Browser and Share Recognition',
                'meta_description' => 'Why favicons matter, how to declare them, and what to ship before a website launch.',
                'article_title' => 'Favicon: Give the Site a Recognizable Browser Identity',
                'intro' => [
                    'A favicon is a small asset, but it shows up in places users repeatedly see.',
                    'Missing or broken favicon links make a product feel unfinished.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'A favicon is the icon used for browser tabs, bookmarks, shortcuts, and some preview surfaces.',
                        'It is usually linked in the document head and backed by one or more image files.',
                    ],
                    'code' => "<link rel=\"icon\" href=\"/favicon.ico\" sizes=\"any\">\n<link rel=\"icon\" type=\"image/png\" href=\"/favicon-32x32.png\" sizes=\"32x32\">",
                    'language' => 'html',
                ],
                'why_it_matters' => [
                    'It improves recognition when users keep many tabs open.',
                    'It supports a more credible first impression.',
                    'It helps browsers and devices display the brand consistently.',
                ],
                'best_practices' => [
                    'Declare at least one valid favicon in the head.',
                    'Use clean, high-contrast artwork that survives small sizes.',
                    'Provide PNG variants for modern browsers when possible.',
                    'Keep the asset reachable and cacheable.',
                ],
                'example' => [
                    'code' => '<link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">',
                    'language' => 'html',
                    'points' => [
                        'The path is explicit.',
                        'The asset type and size are clear.',
                    ],
                ],
                'common_mistakes' => [
                    'Broken favicon URLs.',
                    'Uploading a detailed logo that becomes unreadable at 16px.',
                    'Relying on one oversized image for every device.',
                ],
                'checklist' => [
                    'Favicon link in the head.',
                    'File returns 200.',
                    'Readable at small sizes.',
                    'Matches current branding.',
                ],
                'takeaway' => [
                    'Users rarely praise a favicon, but they notice when it is missing. Ship it as part of launch polish.',
                ],
            ],
            'viewport_meta' => [
                'label' => 'Viewport Meta',
                'slug' => 'viewport-meta',
                'seo_title' => 'Viewport Meta Guide for Mobile-Friendly Rendering',
                'meta_description' => 'Understand the viewport meta tag and why responsive pages need it before launch.',
                'article_title' => 'Viewport Meta: Tell Mobile Browsers How to Size the Page',
                'intro' => [
                    'Without a viewport tag, mobile browsers may render the page as if it were built for desktop widths.',
                    'That usually creates zoomed-out layouts and poor usability on phones.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'The viewport meta tag gives the browser instructions about how the page width and initial scale should behave on mobile devices.',
                    ],
                    'code' => '<meta name="viewport" content="width=device-width, initial-scale=1">',
                    'language' => 'html',
                ],
                'why_it_matters' => [
                    'It is a baseline requirement for responsive rendering.',
                    'It improves readability and tap usability on smaller screens.',
                    'It reduces layout surprises between devices.',
                ],
                'best_practices' => [
                    'Use `width=device-width, initial-scale=1` unless you have a strong reason not to.',
                    'Test actual pages on narrow mobile widths after adding it.',
                    'Pair the tag with responsive CSS, not as a substitute for it.',
                ],
                'common_mistakes' => [
                    'Missing the tag entirely.',
                    'Using fixed widths that fight responsive layouts.',
                    'Assuming the tag alone makes a page mobile-friendly.',
                ],
                'checklist' => [
                    'Viewport tag present.',
                    'Standard responsive values used.',
                    'Layouts checked on mobile widths.',
                ],
                'takeaway' => [
                    'The viewport tag is a simple line with large impact on mobile rendering quality.',
                ],
            ],
            'html_lang' => [
                'label' => 'HTML Lang',
                'slug' => 'html-lang',
                'seo_title' => 'HTML Lang Attribute Guide for Accessibility and Language Clarity',
                'meta_description' => 'Why the HTML lang attribute matters for accessibility, translation, and page interpretation.',
                'article_title' => 'HTML Lang: Declare the Primary Language of the Document',
                'intro' => [
                    'Browsers, screen readers, and translation systems all benefit from knowing the page language up front.',
                    'The `lang` attribute is a small but foundational signal.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'The HTML `lang` attribute is placed on the root `<html>` element and identifies the primary language of the document.',
                    ],
                    'code' => '<html lang="en">',
                    'language' => 'html',
                ],
                'why_it_matters' => [
                    'Screen readers use it to choose pronunciation rules.',
                    'Browsers and translation tools use it for language-aware behavior.',
                    'It reduces ambiguity for multilingual or international content.',
                ],
                'best_practices' => [
                    'Set the root language on every page.',
                    'Use the right value, such as `en`, `en-US`, or `fr`.',
                    'Update the value when a page is served in another language.',
                ],
                'common_mistakes' => [
                    'Leaving the attribute out.',
                    'Using the wrong language code site-wide.',
                    'Forgetting to update it on localized pages.',
                ],
                'checklist' => [
                    'One `lang` attribute on `<html>`.',
                    'Correct language code.',
                    'Localized pages use localized values.',
                ],
                'takeaway' => [
                    'Language declaration is low effort and high value. There is little reason to ship without it.',
                ],
            ],
            'h1_tag' => [
                'label' => 'H1 Tag',
                'slug' => 'h1-tag',
                'seo_title' => 'H1 Tag Guide for Clear Page Topics',
                'meta_description' => 'What an H1 does, how many to use, and how to write one that clarifies the page instantly.',
                'article_title' => 'H1 Tag: Give the Page One Clear Main Heading',
                'intro' => [
                    'The H1 is usually the clearest visible statement of what the page is about.',
                    'When it is missing or unfocused, both scanning and comprehension suffer.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'An H1 is the primary heading in the page body. It usually names the product, topic, or promise of the page.',
                        'It is different from the title tag, which lives in the document head.',
                    ],
                    'code' => '<h1>Check if Your Website Is Ready to Launch</h1>',
                    'language' => 'html',
                ],
                'why_it_matters' => [
                    'It helps users confirm they landed on the right page.',
                    'It gives the content a top-level structure point.',
                    'It supports topic clarity for crawlers and assistive technology.',
                ],
                'best_practices' => [
                    'Use one strong H1 for the main page topic.',
                    'Keep it specific instead of generic.',
                    'Align it with the title tag and page copy without repeating them word for word.',
                ],
                'common_mistakes' => [
                    'No H1 at all.',
                    'Using the logo or menu label as the H1.',
                    'Making the H1 too vague, such as `Welcome`.',
                ],
                'checklist' => [
                    'One visible H1.',
                    'States the main topic clearly.',
                    'Matches the actual page intent.',
                ],
                'takeaway' => [
                    'A good H1 is fast clarity. Users should understand the page within a glance.',
                ],
            ],
            'heading_hierarchy' => [
                'label' => 'Heading Hierarchy',
                'slug' => 'heading-hierarchy',
                'seo_title' => 'Heading Hierarchy Guide for Scannable Content',
                'meta_description' => 'Learn how H1, H2, and H3 structure helps users, crawlers, and accessibility tools understand a page.',
                'article_title' => 'Heading Hierarchy: Structure the Page So It Scans Cleanly',
                'intro' => [
                    'Good pages are not walls of text. They are broken into sections with visible structure.',
                    'Heading hierarchy is the framework that makes long pages understandable.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'Heading hierarchy means using H1 for the main page topic, then H2 and H3 for supporting sections and subsections in a logical order.',
                    ],
                ],
                'why_it_matters' => [
                    'It improves scanability for humans.',
                    'It gives assistive technology a meaningful outline.',
                    'It helps content-heavy pages communicate scope and depth.',
                ],
                'best_practices' => [
                    'Start with a single H1.',
                    'Use H2s for major sections and H3s for nested detail when needed.',
                    'Keep headings descriptive instead of decorative.',
                    'Use heading levels to reflect structure, not visual styling alone.',
                ],
                'common_mistakes' => [
                    'Jumping from H1 straight to H4 for styling.',
                    'Using multiple unrelated H1s on one page.',
                    'Using headings that say nothing, such as `More` or `Stuff`.',
                ],
                'checklist' => [
                    'Single H1.',
                    'Clear H2 sections.',
                    'Subsections only where they add clarity.',
                    'Heading levels reflect content structure.',
                ],
                'takeaway' => [
                    'Hierarchy is how a page thinks out loud. If the outline is messy, the content usually feels messy too.',
                ],
            ],
            'image_alt_text' => [
                'label' => 'Image Alt Text',
                'slug' => 'image-alt-text',
                'seo_title' => 'Image Alt Text Guide for Accessibility and Context',
                'meta_description' => 'Why alt text matters, when to leave it empty, and how to write better descriptions for meaningful images.',
                'article_title' => 'Image Alt Text: Describe Meaningful Images Without Noise',
                'intro' => [
                    'Alt text is primarily an accessibility feature, but it also improves image context for systems that cannot see the page.',
                    'The goal is useful description, not keyword dumping.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'Alt text is the `alt` attribute on an image. It provides a text alternative for the meaning carried by that image.',
                    ],
                    'code' => '<img src="/dashboard.png" alt="Launch report dashboard showing 92 overall score and 3 warnings">',
                    'language' => 'html',
                ],
                'why_it_matters' => [
                    'Screen readers announce it when the image matters.',
                    'It preserves meaning if the image fails to load.',
                    'It improves content clarity for non-visual systems.',
                ],
                'best_practices' => [
                    'Describe the purpose or information the image adds.',
                    'Keep decorative images empty with `alt=""`.',
                    'Be concise and specific.',
                    'Do not repeat surrounding captions word for word unless needed.',
                ],
                'common_mistakes' => [
                    'Leaving meaningful images without alt text.',
                    'Stuffing keywords instead of describing content.',
                    'Writing `image of` when the image meaning is the important part.',
                ],
                'checklist' => [
                    'Meaningful images have alt text.',
                    'Decorative images use empty alt.',
                    'Descriptions are concise and useful.',
                ],
                'takeaway' => [
                    'Alt text should carry meaning, not marketing filler.',
                ],
            ],
            'page_status' => [
                'label' => 'Page Status',
                'slug' => 'page-status',
                'seo_title' => 'Page Status Guide for Launch Reliability',
                'meta_description' => 'Understand why a successful HTTP response matters and what to verify before launching a public page.',
                'article_title' => 'Page Status: Make Sure the URL Actually Loads Cleanly',
                'intro' => [
                    'Everything else on the page matters less if the URL does not return a stable, successful response.',
                    'Status failures break discovery, sharing, and trust immediately.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'Page status refers to the HTTP response code and whether the URL can be fetched successfully by browsers, crawlers, and tools.',
                    ],
                    'code' => "HTTP/1.1 200 OK",
                    'language' => 'text',
                ],
                'why_it_matters' => [
                    'Users cannot use the page if it errors or loops.',
                    'Search engines cannot reliably index failing pages.',
                    'Broken responses hide all other improvements behind a transport problem.',
                ],
                'best_practices' => [
                    'Serve launch pages with a successful 200 response.',
                    'Remove redirect chains and broken intermediate hops.',
                    'Test the public URL, not only the staging route.',
                ],
                'common_mistakes' => [
                    'Launching with 4xx or 5xx responses.',
                    'Leaving the page behind auth or IP restrictions.',
                    'Forgetting that a final redirect destination also needs to work.',
                ],
                'checklist' => [
                    'Public URL reachable.',
                    'Final response successful.',
                    'No broken redirect chain.',
                ],
                'takeaway' => [
                    'Availability is the first launch requirement. Everything else layers on top of that.',
                ],
            ],
            'https' => [
                'label' => 'HTTPS',
                'slug' => 'https',
                'seo_title' => 'HTTPS Guide for Secure Website Launches',
                'meta_description' => 'Why HTTPS matters for trust, security, and launch readiness, plus what to verify on the final URL.',
                'article_title' => 'HTTPS: Serve the Final Page Over an Encrypted Connection',
                'intro' => [
                    'Users expect secure transport by default. Plain HTTP now feels broken or risky.',
                    'HTTPS is a basic launch standard, not a bonus feature.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'HTTPS means the page is delivered over TLS so the connection between the browser and server is encrypted.',
                    ],
                    'code' => 'https://example.com',
                    'language' => 'text',
                ],
                'why_it_matters' => [
                    'It protects data in transit.',
                    'It improves trust because browsers flag insecure pages.',
                    'It aligns with modern search, platform, and browser expectations.',
                ],
                'best_practices' => [
                    'Use HTTPS for every public page and asset.',
                    'Install a valid certificate and renew it automatically.',
                    'Avoid mixed-content requests that pull insecure assets into a secure page.',
                ],
                'common_mistakes' => [
                    'Leaving the primary URL on HTTP.',
                    'Serving some assets over HTTP on an HTTPS page.',
                    'Forgetting to renew certificates.',
                ],
                'checklist' => [
                    'Final page uses HTTPS.',
                    'Certificate valid.',
                    'No mixed-content errors.',
                ],
                'takeaway' => [
                    'Secure transport is table stakes for a public launch.',
                ],
            ],
            'https_redirects' => [
                'label' => 'HTTPS Redirect',
                'slug' => 'https-redirect',
                'seo_title' => 'HTTPS Redirect Guide for Canonical URL Consistency',
                'meta_description' => 'Learn why HTTP-to-HTTPS redirects matter and how to keep one clean public version of every page.',
                'article_title' => 'HTTPS Redirect: Push Old HTTP Requests to the Secure Version',
                'intro' => [
                    'Even if HTTPS works, users and bots may still hit the old HTTP version.',
                    'A redirect closes that gap and keeps one canonical destination.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'An HTTPS redirect sends requests from `http://` URLs to the matching `https://` URLs, ideally with a permanent 301 response.',
                    ],
                    'code' => "HTTP/1.1 301 Moved Permanently\nLocation: https://example.com/",
                    'language' => 'text',
                ],
                'why_it_matters' => [
                    'It keeps users on the secure version.',
                    'It reduces duplicate URL paths between HTTP and HTTPS.',
                    'It aligns incoming links, canonicals, and analytics with one destination.',
                ],
                'best_practices' => [
                    'Redirect every HTTP request to the equivalent HTTPS URL.',
                    'Keep the chain short, ideally one hop.',
                    'Use permanent redirects for canonical moves.',
                ],
                'common_mistakes' => [
                    'Leaving HTTP pages live without a redirect.',
                    'Redirecting HTTP to an unrelated page.',
                    'Creating redirect loops or multi-hop chains.',
                ],
                'checklist' => [
                    'HTTP version redirects to HTTPS.',
                    'One clean hop.',
                    'Final destination resolves correctly.',
                ],
                'takeaway' => [
                    'HTTPS adoption is incomplete until the insecure version consistently points away.',
                ],
            ],
            'indexability' => [
                'label' => 'Indexability',
                'slug' => 'indexability',
                'seo_title' => 'Indexability Guide for Pages That Should Be Discoverable',
                'meta_description' => 'Understand noindex signals, robots directives, and when a page is accidentally blocking itself from discovery.',
                'article_title' => 'Indexability: Do Not Accidentally Hide the Page From Search',
                'intro' => [
                    'A page can look perfect and still stay invisible if indexing directives block it.',
                    'Launch reviews should always confirm the page is allowed to be discovered.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'Indexability refers to whether crawlers are allowed to store the page in search indexes. Tags like `noindex` and some response headers can block that.',
                    ],
                    'code' => '<meta name="robots" content="index,follow">',
                    'language' => 'html',
                ],
                'why_it_matters' => [
                    'A noindex page cannot earn visibility in normal search results.',
                    'Blocking directives often survive from staging by mistake.',
                    'AI and search discovery are harder when the page is intentionally hidden.',
                ],
                'best_practices' => [
                    'Use `index,follow` or omit restrictive directives on public launch pages.',
                    'Check both HTML meta robots tags and `X-Robots-Tag` headers.',
                    'Review staging and production separately.',
                ],
                'common_mistakes' => [
                    'Leaving `noindex` on production pages.',
                    'Blocking important routes in robots settings by accident.',
                    'Assuming the page is indexable just because it loads.',
                ],
                'checklist' => [
                    'No blocking robots directive.',
                    'No restrictive header on the final response.',
                    'Launch pages intended for discovery remain indexable.',
                ],
                'takeaway' => [
                    'Visibility starts with permission. Make sure the page is allowed to exist in the index.',
                ],
            ],
            'security_headers' => [
                'label' => 'Security Headers',
                'slug' => 'security-headers',
                'seo_title' => 'Security Headers Guide for Safer Public Launches',
                'meta_description' => 'What common security headers do and why launch pages should ship with a basic hardening baseline.',
                'article_title' => 'Security Headers: Add a Basic Response Hardening Layer',
                'intro' => [
                    'Security headers do not replace secure code, but they reduce common browser-side risks.',
                    'A public launch should include a sensible baseline.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'Security headers are HTTP response headers that instruct the browser how to handle framing, transport, MIME types, referrers, and related behavior.',
                    ],
                    'code' => "Strict-Transport-Security: max-age=31536000\nX-Content-Type-Options: nosniff\nReferrer-Policy: strict-origin-when-cross-origin",
                    'language' => 'text',
                ],
                'why_it_matters' => [
                    'They reduce avoidable exposure to common browser-level issues.',
                    'They help enforce safer defaults across public traffic.',
                    'They add trust and operational maturity to the deployment.',
                ],
                'best_practices' => [
                    'Ship a minimal baseline such as HSTS, X-Content-Type-Options, and Referrer-Policy.',
                    'Add X-Frame-Options or CSP framing rules where appropriate.',
                    'Test headers after CDN and proxy layers, not just locally.',
                ],
                'common_mistakes' => [
                    'No security headers on production responses.',
                    'Setting headers only on one route instead of the whole app.',
                    'Breaking behavior with untested restrictive policies.',
                ],
                'checklist' => [
                    'Baseline headers present.',
                    'Values tested on the public response.',
                    'No regressions introduced by policy rules.',
                ],
                'takeaway' => [
                    'Security headers are part of disciplined delivery. They should not be an afterthought.',
                ],
            ],
            'compression' => [
                'label' => 'Compression',
                'slug' => 'compression',
                'seo_title' => 'Compression Guide for Faster Text Response Delivery',
                'meta_description' => 'Why Brotli or gzip matters for HTML and other text assets before a site launch.',
                'article_title' => 'Compression: Send Text Responses in a Smaller Payload',
                'intro' => [
                    'HTML, CSS, JS, JSON, and XML compress well. Shipping them uncompressed wastes bandwidth and time.',
                    'Launch pages benefit from faster delivery on both strong and weak connections.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'Compression means the server encodes response bodies with formats such as Brotli or gzip so the browser downloads fewer bytes.',
                    ],
                ],
                'why_it_matters' => [
                    'It reduces transfer size for text-heavy responses.',
                    'It can improve perceived speed, especially on slower networks.',
                    'It lowers bandwidth waste at scale.',
                ],
                'best_practices' => [
                    'Enable Brotli where available, or gzip as a fallback.',
                    'Apply compression to HTML, CSS, JS, JSON, XML, and similar text formats.',
                    'Verify the CDN or server is not stripping compression unexpectedly.',
                ],
                'common_mistakes' => [
                    'Serving HTML uncompressed.',
                    'Compressing only static assets but not the main document.',
                    'Assuming local dev behavior matches production proxies.',
                ],
                'checklist' => [
                    'Compression enabled.',
                    'HTML response compressed.',
                    'Public stack preserves compression.',
                ],
                'takeaway' => [
                    'Compression is simple performance hygiene. Public pages should not skip it.',
                ],
            ],
            'robots_txt' => [
                'label' => 'robots.txt',
                'slug' => 'robots-txt',
                'seo_title' => 'robots.txt Guide for Crawl Access Basics',
                'meta_description' => 'What robots.txt does, what it does not do, and how to avoid blocking important pages by mistake.',
                'article_title' => 'robots.txt: Publish Clear Crawl Rules at the Site Root',
                'intro' => [
                    'Robots.txt is one of the first files crawlers look for when they evaluate a site.',
                    'It should be present, intentional, and free of launch-blocking mistakes.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'The `robots.txt` file lives at the site root and gives crawl guidance to well-behaved bots.',
                    ],
                    'code' => "User-agent: *\nAllow: /\nSitemap: https://example.com/sitemap.xml",
                    'language' => 'text',
                ],
                'why_it_matters' => [
                    'It communicates basic crawl rules early.',
                    'It can point crawlers to the sitemap.',
                    'It helps avoid accidental blocking of important public paths.',
                ],
                'best_practices' => [
                    'Publish the file at `/robots.txt`.',
                    'Keep rules simple unless you have a clear reason for complexity.',
                    'Review staging disallow rules before deploying to production.',
                ],
                'common_mistakes' => [
                    'No file at all.',
                    'Leaving `Disallow: /` from staging.',
                    'Using robots.txt as if it were a security control.',
                ],
                'checklist' => [
                    'File exists at root.',
                    'Important pages are not blocked.',
                    'Sitemap location included when useful.',
                ],
                'takeaway' => [
                    'Robots.txt should guide discovery, not accidentally suppress it.',
                ],
            ],
            'sitemap_xml' => [
                'label' => 'Sitemap.xml',
                'slug' => 'sitemap-xml',
                'seo_title' => 'Sitemap.xml Guide for URL Discovery',
                'meta_description' => 'Why XML sitemaps matter, which URLs to include, and what to avoid before launch.',
                'article_title' => 'Sitemap.xml: Give Crawlers a Clean List of Important URLs',
                'intro' => [
                    'A sitemap does not guarantee indexing, but it helps crawlers discover the URLs you care about.',
                    'It is especially useful for new sites, deep pages, and evolving content sets.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'An XML sitemap is a machine-readable file listing canonical URLs that should be crawled.',
                    ],
                    'code' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n  <url>\n    <loc>https://example.com/</loc>\n  </url>\n</urlset>",
                    'language' => 'xml',
                ],
                'why_it_matters' => [
                    'It helps new or less-linked pages get discovered faster.',
                    'It reinforces the canonical public URL set.',
                    'It supports operational clarity when many URLs exist.',
                ],
                'best_practices' => [
                    'Include only canonical, indexable, public URLs.',
                    'Keep the file reachable at `/sitemap.xml` or submit a sitemap index.',
                    'Update the file as meaningful URLs change.',
                ],
                'common_mistakes' => [
                    'Including redirected or noindex pages.',
                    'Listing staging or duplicate URLs.',
                    'Publishing an outdated sitemap.',
                ],
                'checklist' => [
                    'Sitemap exists.',
                    'URLs are canonical and public.',
                    'No broken or blocked entries.',
                ],
                'takeaway' => [
                    'A clean sitemap is a practical discovery aid and a signal of deployment discipline.',
                ],
            ],
            'form_labels' => [
                'label' => 'Form Labels',
                'slug' => 'form-labels',
                'seo_title' => 'Form Labels Guide for Accessible Inputs',
                'meta_description' => 'Why labels matter for forms and how to make inputs understandable to users and assistive tech.',
                'article_title' => 'Form Labels: Make Every Input Understandable',
                'intro' => [
                    'Inputs without labels force users to guess or rely on placeholder text that disappears.',
                    'Accessible forms start with explicit naming.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'A form label associates visible or programmatic text with a control so users and assistive technologies know what the field is for.',
                    ],
                    'code' => "<label for=\"email\">Work email</label>\n<input id=\"email\" name=\"email\" type=\"email\">",
                    'language' => 'html',
                ],
                'why_it_matters' => [
                    'Screen readers rely on labels to announce fields properly.',
                    'Visible labels improve completion speed and confidence.',
                    'They reduce ambiguity in signup, checkout, and contact flows.',
                ],
                'best_practices' => [
                    'Pair each visible input with a real label or accessible name.',
                    'Use placeholders as hints, not replacements for labels.',
                    'Keep label wording specific and user-facing.',
                ],
                'common_mistakes' => [
                    'No label, only placeholder text.',
                    'Using icons alone as field names.',
                    'Mismatching `for` and `id` values.',
                ],
                'checklist' => [
                    'Every visible input has a label or accessible name.',
                    'Label text is clear.',
                    'Associations work correctly.',
                ],
                'takeaway' => [
                    'If a user cannot identify an input instantly, the form is already harder than it should be.',
                ],
            ],
            'landmarks' => [
                'label' => 'Landmarks',
                'slug' => 'landmarks',
                'seo_title' => 'Landmarks Guide for Semantic Page Structure',
                'meta_description' => 'Learn how semantic landmarks like main, nav, and footer improve navigation and accessibility.',
                'article_title' => 'Landmarks: Use Semantic Regions So the Layout Makes Sense',
                'intro' => [
                    'Semantic landmarks help assistive technology and developers understand a page at a structural level.',
                    'They also encourage cleaner layout separation.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'Landmarks are semantic regions such as `header`, `nav`, `main`, `aside`, and `footer` that define the major areas of a page.',
                    ],
                    'code' => "<header>...</header>\n<nav>...</nav>\n<main>...</main>\n<footer>...</footer>",
                    'language' => 'html',
                ],
                'why_it_matters' => [
                    'Screen reader users can jump directly to major sections.',
                    'It clarifies page structure for maintainers.',
                    'It supports consistent layouts across the site.',
                ],
                'best_practices' => [
                    'Use one clear `main` region for the page content.',
                    'Wrap navigation in `nav` and footer content in `footer`.',
                    'Prefer semantic tags over generic containers when they match the purpose.',
                ],
                'common_mistakes' => [
                    'Everything inside nested `div`s with no semantic structure.',
                    'Multiple confusing main regions.',
                    'Using landmarks purely for styling instead of meaning.',
                ],
                'checklist' => [
                    'Main content lives in `main`.',
                    'Navigation uses `nav`.',
                    'Header and footer regions are clear.',
                ],
                'takeaway' => [
                    'Landmarks are quiet infrastructure. When they are missing, navigation gets harder.',
                ],
            ],
            'open_graph_basics' => [
                'label' => 'Open Graph Basics',
                'slug' => 'open-graph-basics',
                'seo_title' => 'Open Graph Basics Guide for Better Social Previews',
                'meta_description' => 'What Open Graph tags do and how to make shared links look complete across chat and social platforms.',
                'article_title' => 'Open Graph Basics: Control the Shared Preview of the Page',
                'intro' => [
                    'When someone shares your page, the preview often comes from Open Graph tags.',
                    'Missing or weak tags produce low-quality previews that hurt trust and click potential.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'Open Graph tags are metadata in the document head that define the title, description, type, and image used for social sharing previews.',
                    ],
                    'code' => "<meta property=\"og:title\" content=\"Website Launch Checker\">\n<meta property=\"og:description\" content=\"Audit your site before launch for SEO, trust, and accessibility gaps.\">\n<meta property=\"og:type\" content=\"website\">",
                    'language' => 'html',
                ],
                'why_it_matters' => [
                    'It improves how links look in social feeds and chat apps.',
                    'It lets you separate sharing copy from on-page copy when needed.',
                    'It reduces random preview generation by platforms.',
                ],
                'best_practices' => [
                    'Always provide `og:title`, `og:description`, and `og:type`.',
                    'Keep social copy specific and readable.',
                    'Make Open Graph content align with the destination page.',
                    'Use lengths that avoid obvious truncation where possible.',
                ],
                'common_mistakes' => [
                    'No Open Graph tags.',
                    'Copying the same weak text from an outdated page.',
                    'Using placeholder or generic descriptions.',
                ],
                'checklist' => [
                    'Core tags present.',
                    'Copy is clear.',
                    'Values match page intent.',
                    'Image handled separately with a real `og:image`.',
                ],
                'takeaway' => [
                    'Open Graph tags are your default shared-link packaging. Ship them intentionally.',
                ],
            ],
            'open_graph_image' => [
                'label' => 'Open Graph Image',
                'slug' => 'open-graph-image',
                'seo_title' => 'Open Graph Image Guide for Share Preview Quality',
                'meta_description' => 'Why `og:image` matters and how to choose a preview image that still looks good when links are shared.',
                'article_title' => 'Open Graph Image: Give Shared Links a Strong Visual Preview',
                'intro' => [
                    'A good preview image makes shared links look deliberate and more credible.',
                    'A missing or broken image often produces awkward previews or no image at all.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'The `og:image` tag points to the image platforms should use when generating link previews.',
                    ],
                    'code' => '<meta property="og:image" content="https://example.com/images/launch-checker-og.png">',
                    'language' => 'html',
                ],
                'why_it_matters' => [
                    'It helps your links stand out in crowded feeds.',
                    'It improves recognition and click confidence.',
                    'It prevents platforms from guessing a poor image from the page.',
                ],
                'best_practices' => [
                    'Use a branded image sized for sharing surfaces.',
                    'Keep important text large and minimal.',
                    'Host the image on a stable public URL.',
                    'Test a few share previews after deployment.',
                ],
                'common_mistakes' => [
                    'Broken image URLs.',
                    'Using screenshots with unreadable tiny text.',
                    'Letting platforms choose an arbitrary image from the page.',
                ],
                'checklist' => [
                    'Image tag present.',
                    'Image URL public and reachable.',
                    'Visual readable at social-preview size.',
                ],
                'takeaway' => [
                    'Shared links compete visually. A proper Open Graph image gives your page a fair chance.',
                ],
            ],
            'twitter_card' => [
                'label' => 'Twitter Card',
                'slug' => 'twitter-card',
                'seo_title' => 'Twitter Card Guide for X Link Previews',
                'meta_description' => 'What the twitter:card tag does and why it still matters for consistent link previews.',
                'article_title' => 'Twitter Card: Define the Card Type for Shared Links',
                'intro' => [
                    'Many platforms fall back to Open Graph, but the Twitter card tag is still a useful explicit signal.',
                    'A missing card type can reduce consistency in how the link is previewed.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'The `twitter:card` meta tag tells X which preview format to use, such as `summary` or `summary_large_image`.',
                    ],
                    'code' => '<meta name="twitter:card" content="summary_large_image">',
                    'language' => 'html',
                ],
                'why_it_matters' => [
                    'It gives a direct preview-format instruction.',
                    'It works well alongside your Open Graph tags.',
                    'It helps keep social previews more predictable.',
                ],
                'best_practices' => [
                    'Use `summary_large_image` when you have a quality preview image.',
                    'Keep related social metadata aligned.',
                    'Test one real share after deployment.',
                ],
                'common_mistakes' => [
                    'No twitter card tag.',
                    'Using a large-image card without a real preview image.',
                    'Letting card metadata drift away from actual page content.',
                ],
                'checklist' => [
                    'twitter:card present.',
                    'Card type matches available media.',
                    'Social preview tested.',
                ],
                'takeaway' => [
                    'Small tag, useful consistency. Add it when you care about clean shared-link presentation.',
                ],
            ],
            'structured_data' => [
                'label' => 'Structured Data',
                'slug' => 'structured-data',
                'seo_title' => 'Structured Data Guide for Clear Machine-Readable Context',
                'meta_description' => 'Learn what JSON-LD structured data does and when it helps search engines interpret a page more accurately.',
                'article_title' => 'Structured Data: Add Machine-Readable Context With JSON-LD',
                'intro' => [
                    'Structured data helps machines understand entities, page types, and relationships more directly.',
                    'It should clarify the page, not decorate it with inaccurate schema.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'Structured data is usually added as JSON-LD in the page head or body. It describes the page or entity in a schema vocabulary.',
                    ],
                    'code' => "{\n  \"@context\": \"https://schema.org\",\n  \"@type\": \"WebSite\",\n  \"name\": \"Software on the Web\",\n  \"url\": \"https://example.com\"\n}",
                    'language' => 'json',
                ],
                'why_it_matters' => [
                    'It can improve interpretation of the page and brand.',
                    'It supports eligibility for some enriched search experiences.',
                    'It gives clearer structure to non-human systems.',
                ],
                'best_practices' => [
                    'Use schema that matches the real page type.',
                    'Keep values consistent with visible content.',
                    'Prefer JSON-LD for maintainability.',
                    'Validate changes after deployment.',
                ],
                'common_mistakes' => [
                    'No schema where clear schema would help.',
                    'Using the wrong schema type just to chase features.',
                    'Publishing schema values that disagree with the page.',
                ],
                'checklist' => [
                    'Relevant schema exists.',
                    'Values are accurate.',
                    'Visible content and structured data agree.',
                ],
                'takeaway' => [
                    'Structured data works best when it is honest, simple, and tightly aligned with the page.',
                ],
            ],
            'internal_links' => [
                'label' => 'Internal Links',
                'slug' => 'internal-links',
                'seo_title' => 'Internal Links Guide for Discovery and Navigation',
                'meta_description' => 'Why internal links matter for both users and crawlers, and what good launch pages usually link to.',
                'article_title' => 'Internal Links: Connect the Page to the Rest of the Site',
                'intro' => [
                    'A homepage or landing page should not feel isolated.',
                    'Internal links help both people and crawlers move toward the next useful step.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'Internal links are links from one page on your site to another page on the same site.',
                    ],
                ],
                'why_it_matters' => [
                    'They help crawlers discover deeper pages.',
                    'They move users toward conversion, support, or exploration paths.',
                    'They show which destinations matter in your site structure.',
                ],
                'best_practices' => [
                    'Link to pages users are likely to need next.',
                    'Use descriptive anchor text instead of generic labels.',
                    'Keep the most important paths accessible from strong pages.',
                ],
                'common_mistakes' => [
                    'A homepage with almost no path into the product.',
                    'Only linking through vague buttons with no surrounding context.',
                    'Burying core pages several clicks deep with no prominent path.',
                ],
                'checklist' => [
                    'Links to key next-step pages exist.',
                    'Anchor text is understandable.',
                    'Important URLs are not orphaned.',
                ],
                'takeaway' => [
                    'Internal linking is how a site exposes its own priorities.',
                ],
            ],
            'external_links' => [
                'label' => 'External Links',
                'slug' => 'external-links',
                'seo_title' => 'External Links Guide for Trust and Supporting Context',
                'meta_description' => 'When outbound links help a page, and how to use them for trust without leaking users away carelessly.',
                'article_title' => 'External Links: Add Outside References When They Increase Trust',
                'intro' => [
                    'Not every page needs outbound links, but some pages become more credible when they reference important external destinations.',
                    'The point is useful context, not link clutter.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'External links point from your site to another domain, such as a documentation site, app store listing, social proof source, or status page.',
                    ],
                ],
                'why_it_matters' => [
                    'They can support claims with real references.',
                    'They help users verify trust signals like community presence or platform listings.',
                    'They reduce the feeling that the page is a closed marketing bubble.',
                ],
                'best_practices' => [
                    'Link out only when it adds verification or helpful context.',
                    'Prefer trusted, relevant destinations.',
                    'Be deliberate about where those links appear in the user journey.',
                ],
                'common_mistakes' => [
                    'No external trust references anywhere on the page.',
                    'Linking out to low-quality or irrelevant destinations.',
                    'Sending users away too early with distracting outbound links.',
                ],
                'checklist' => [
                    'Outbound links exist where they add trust.',
                    'Destinations are relevant and credible.',
                    'Links do not overwhelm the primary action path.',
                ],
                'takeaway' => [
                    'Use external links as supporting evidence, not as decoration.',
                ],
            ],
            'privacy_contact' => [
                'label' => 'Trust Links',
                'slug' => 'trust-links',
                'seo_title' => 'Trust Links Guide for Privacy, Contact, and Support Signals',
                'meta_description' => 'Why visible trust links matter on launch pages and which destinations users usually expect to find.',
                'article_title' => 'Trust Links: Show Users Where Privacy, Contact, and Support Live',
                'intro' => [
                    'People look for signs that a real team stands behind a site.',
                    'Visible trust links make that investigation easier.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'Trust links usually point to pages such as Privacy, Contact, Support, About, Terms, or Security.',
                    ],
                ],
                'why_it_matters' => [
                    'They reduce uncertainty before signup or purchase.',
                    'They show users where to go when they have concerns.',
                    'They are common trust signals on legitimate public products.',
                ],
                'best_practices' => [
                    'Include at least privacy and contact or support paths.',
                    'Make the links easy to find, usually in navigation or footer.',
                    'Keep those pages current and not thin placeholders.',
                ],
                'common_mistakes' => [
                    'No visible privacy or contact path.',
                    'Links that lead to empty or outdated pages.',
                    'Hiding trust links behind signup walls.',
                ],
                'checklist' => [
                    'Privacy path visible.',
                    'Contact or support path visible.',
                    'Trust pages are public and maintained.',
                ],
                'takeaway' => [
                    'Trust is easier to grant when users can verify who is behind the page and how to reach them.',
                ],
            ],
            'primary_cta' => [
                'label' => 'Primary CTA',
                'slug' => 'primary-cta',
                'seo_title' => 'Primary CTA Guide for Clear Conversion Paths',
                'meta_description' => 'Learn why a clear primary call to action matters and how to avoid burying the next step on your page.',
                'article_title' => 'Primary CTA: Make the Next Step Unambiguous',
                'intro' => [
                    'A good launch page helps the user decide what to do next without friction.',
                    'If the main action is hard to find, interest leaks away.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'A primary CTA is the main action you want the visitor to take, such as starting a trial, requesting a demo, or running a scan.',
                    ],
                ],
                'why_it_matters' => [
                    'It turns attention into action.',
                    'It reduces hesitation created by too many equal choices.',
                    'It helps the page feel directed instead of scattered.',
                ],
                'best_practices' => [
                    'Place the primary CTA near the top where it is easy to find.',
                    'Use action language that explains the result.',
                    'Support it with nearby context about what happens next.',
                ],
                'common_mistakes' => [
                    'No obvious main action.',
                    'Several competing CTAs with equal weight.',
                    'Vague button copy like `Submit` or `Continue`.',
                ],
                'checklist' => [
                    'One clear primary action.',
                    'Visible above the fold.',
                    'Button text specific.',
                    'Next-step expectations explained.',
                ],
                'takeaway' => [
                    'Users should not have to decode your intended next step.',
                ],
            ],
            'unique_viewpoint' => [
                'label' => 'Unique Viewpoint',
                'slug' => 'unique-viewpoint',
                'seo_title' => 'Unique Viewpoint Guide for Less Generic Product Messaging',
                'meta_description' => 'Why firsthand perspective and differentiated messaging matter on modern launch pages.',
                'article_title' => 'Unique Viewpoint: Give the Page a Perspective Only You Can Offer',
                'intro' => [
                    'Generic launch pages are easy to produce and easy to forget.',
                    'Original perspective is one of the strongest ways to make the page memorable and trustworthy.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'Unique viewpoint means the page includes firsthand perspective, clear differentiation, or evidence that could not be swapped with any other product.',
                    ],
                ],
                'why_it_matters' => [
                    'It separates your page from template-level marketing language.',
                    'It gives users a reason to believe you understand the problem deeply.',
                    'It creates stronger signals for AI and search systems looking for distinctiveness.',
                ],
                'best_practices' => [
                    'Include founder insight, customer lessons, or real constraints the product solves.',
                    'Use concrete claims instead of empty superlatives.',
                    'Show how your approach differs from common alternatives.',
                ],
                'common_mistakes' => [
                    'Copy that could describe any SaaS tool.',
                    'Vague adjectives with no proof.',
                    'No firsthand detail anywhere on the page.',
                ],
                'checklist' => [
                    'At least one firsthand insight.',
                    'Differentiation stated clearly.',
                    'Claims supported by specific context.',
                ],
                'takeaway' => [
                    'Distinct pages usually come from distinct thinking. Put some of that thinking on the page.',
                ],
            ],
            'content_clarity' => [
                'label' => 'Content Clarity',
                'slug' => 'content-clarity',
                'seo_title' => 'Content Clarity Guide for Readable Launch Pages',
                'meta_description' => 'How to make a launch page easier to understand with clearer sections, copy, and explanation depth.',
                'article_title' => 'Content Clarity: Explain the Product Without Making Users Work',
                'intro' => [
                    'Clarity is often the difference between curiosity and bounce.',
                    'Pages can fail not because the product is weak, but because the explanation is too thin or confusing.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'Content clarity means the page explains what the product does, who it is for, and why it matters in a way users can understand quickly.',
                    ],
                ],
                'why_it_matters' => [
                    'Clear copy reduces confusion and friction.',
                    'It helps users self-qualify faster.',
                    'It improves machine understanding because the page has stronger semantic signals.',
                ],
                'best_practices' => [
                    'State the product, audience, and outcome early.',
                    'Break the page into named sections.',
                    'Replace abstract claims with direct explanation.',
                    'Add enough detail to answer basic evaluation questions.',
                ],
                'common_mistakes' => [
                    'Only slogans, no explanation.',
                    'Dense text with no structure.',
                    'Assuming users already know the problem space.',
                ],
                'checklist' => [
                    'Core value clear in first screen.',
                    'Sections explain the offer logically.',
                    'Basic questions answered.',
                    'Copy readable without insider context.',
                ],
                'takeaway' => [
                    'If a smart first-time visitor still cannot explain your page back to you, clarity is missing.',
                ],
            ],
            'media_support' => [
                'label' => 'Media Support',
                'slug' => 'media-support',
                'seo_title' => 'Media Support Guide for Product Understanding',
                'meta_description' => 'Why screenshots, visuals, and short videos help users understand a product faster on launch pages.',
                'article_title' => 'Media Support: Use Visuals That Help the Product Click Faster',
                'intro' => [
                    'Some products are easier to understand when users can see them, not just read about them.',
                    'Useful media reduces interpretation effort.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'Media support means screenshots, diagrams, product visuals, or short videos that help explain the page and product.',
                    ],
                ],
                'why_it_matters' => [
                    'It shortens the path to understanding.',
                    'It adds proof that the product exists and works.',
                    'It can improve engagement on otherwise abstract pages.',
                ],
                'best_practices' => [
                    'Use visuals that explain real product states.',
                    'Keep videos short and task-oriented.',
                    'Add captions or surrounding context so the media is not ambiguous.',
                ],
                'common_mistakes' => [
                    'No visuals for a product that is hard to imagine.',
                    'Generic stock imagery instead of product evidence.',
                    'Decorative media that adds noise instead of clarity.',
                ],
                'checklist' => [
                    'At least one meaningful visual when helpful.',
                    'Media supports, not distracts from, the message.',
                    'Visuals match the current product.',
                ],
                'takeaway' => [
                    'Good media is explanatory evidence, not filler.',
                ],
            ],
            'crawlable_content' => [
                'label' => 'Crawlable Content',
                'slug' => 'crawlable-content',
                'seo_title' => 'Crawlable Content Guide for Discoverable Page Copy',
                'meta_description' => 'Why important page copy should exist in the HTML response and not depend entirely on client-side rendering.',
                'article_title' => 'Crawlable Content: Make Sure Key Text Exists in the HTML',
                'intro' => [
                    'If the useful copy is missing from the response HTML, some systems may never see it clearly.',
                    'That weakens search visibility, AI interpretation, and auditing.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'Crawlable content means the important page text is present in the HTML response and not only injected later by client-side JavaScript.',
                    ],
                ],
                'why_it_matters' => [
                    'It helps crawlers parse the page faster and more reliably.',
                    'It improves resilience when JavaScript fails or delays.',
                    'It gives AI and indexing systems stronger access to the real message.',
                ],
                'best_practices' => [
                    'Server-render critical headings, paragraphs, and links when possible.',
                    'Do not hide core value copy behind delayed hydration.',
                    'Test the raw HTML output, not only the rendered browser state.',
                ],
                'common_mistakes' => [
                    'Empty shells with content injected after load.',
                    'Relying on client-only rendering for primary product copy.',
                    'Assuming all bots execute the page the same way modern browsers do.',
                ],
                'checklist' => [
                    'Core copy exists in HTML source.',
                    'Important headings and links render server-side or are prerendered.',
                    'Useful content not blocked behind JS-only flows.',
                ],
                'takeaway' => [
                    'If the important text is invisible to the initial response, discovery becomes less reliable.',
                ],
            ],
            'javascript_dependency' => [
                'label' => 'JavaScript Dependency',
                'slug' => 'javascript-dependency',
                'seo_title' => 'JavaScript Dependency Guide for More Resilient Launch Pages',
                'meta_description' => 'Understand when a page depends too heavily on JavaScript and how to reduce risk for launch-critical content.',
                'article_title' => 'JavaScript Dependency: Do Not Make the Main Message Wait on the Client',
                'intro' => [
                    'JavaScript can power rich experiences, but over-dependence increases fragility.',
                    'Launch-critical pages should still communicate their purpose when scripts are slow or limited.',
                ],
                'what_is' => [
                    'paragraphs' => [
                        'JavaScript dependency refers to how much the visible and meaningful page experience relies on client-side scripts before the content becomes usable.',
                    ],
                ],
                'why_it_matters' => [
                    'Heavy client-side dependence can delay first understanding.',
                    'It creates failure points for crawlers, low-power devices, and flaky networks.',
                    'It weakens resilience when scripts fail, block, or time out.',
                ],
                'best_practices' => [
                    'Render essential content without waiting on large bundles.',
                    'Use JavaScript to enhance, not to reveal the entire page meaning.',
                    'Prerender or server-render launch-critical routes when possible.',
                ],
                'common_mistakes' => [
                    'Blank or near-blank HTML shells.',
                    'Hydrating the whole meaning of the page after long delays.',
                    'Treating the homepage like an app screen that can wait to become readable.',
                ],
                'checklist' => [
                    'Core content visible before heavy JS completes.',
                    'Critical routes resilient without perfect script execution.',
                    'Client-side logic enhances rather than replaces meaning.',
                ],
                'takeaway' => [
                    'Use JavaScript for capability, not as a gatekeeper for the page’s basic message.',
                ],
            ],
        ];
    }
}
