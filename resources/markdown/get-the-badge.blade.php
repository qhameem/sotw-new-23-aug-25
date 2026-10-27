Add the badge to a publicly accessible page of your website, link it back to **Software on the Web**, and keep it live so our verification can pass. The easiest place is usually your homepage footer, but your header or an "As Seen On" section also works.

Current Badge

## {!! app(\App\Services\Markdown\Format::class)->escape($badgeTitle) !!}

This is the current badge we expect to find at the public page URL you verify. Homepage placement is encouraged.

[Opens to homepage]({!! $destinationUrl !!})

[{!! app(\App\Services\Markdown\Format::class)->escape($badgeAltText) !!}]({!! $destinationUrl !!})

Default display size: `width="200"`

Step 1

## Copy the badge code

Paste this exact code into your public page HTML. The link must stay `rel="dofollow"`.

```
{!! $defaultSnippet !!}
```

Step 2

## Paste it into a public page

Any publicly accessible page is acceptable; homepage placement is encouraged. Submit the exact page URL for verification. These homepage placements work well:

### Best option

Inside your homepage `<footer>` area.

### Also good

Inside the homepage `<header>` or hero section.

### Also valid

Inside an "As Seen On", "Featured In", or trust/logo section on the homepage.

If you edit raw HTML

Footer example:

```
<footer>
...
{!! $defaultSnippet !!}
...
</footer>
```

Header or section example:

```
<section class="as-seen-on">
<h2>Featured On</h2>
{!! $defaultSnippet !!}
</section>
```

Step 3

## Find the right place in popular platforms

### WordPress

Open the homepage editor and add a **Custom HTML** block inside the footer section, homepage content, or a widget area.

If you use Elementor: open the homepage, drag in an **HTML** widget, and paste the code.

If you use a footer builder or theme options panel: look for **Footer Builder**, **Widgets**, or **Custom HTML**.

### Shopify

Go to **Online Store → Themes → Customize**, open your homepage, then add a **Custom Liquid** block or an HTML-capable section.

For footer placement, open the footer section inside the theme customizer and add the badge there if your theme supports custom content blocks.

### Webflow

Open the homepage in the Designer, add an **Embed** element where you want the badge, then paste the code.

Footer placement usually means dropping the embed inside your global footer component.

### Wix

Edit the homepage, then use **Add Elements → Embed Code → Embed HTML** and paste the code.

Place it in the homepage footer or a visible trust section, then publish.

### Squarespace

Edit the homepage and insert a **Code Block** in the section where you want the badge.

For footer placement, edit the footer area and add the code block there.

### Static HTML, cPanel, or aaPanel

Open your homepage file such as `index.html`, `index.php`, or the homepage template file.

Paste the badge code inside the homepage `<footer>`, `<header>`, or a visible section within the `<body>`, then save and publish.

Step 4

## Customize the badge size

Change the width value in the image tag. Keep the height automatic so the badge does not stretch.

Small

Use this if your footer has limited space.

```
{!! $smallSnippet !!}
```

Default

Recommended for most homepages.

```
{!! $defaultSnippet !!}
```

Large

Use this for hero or "Featured On" sections.

```
{!! $largeSnippet !!}
```

A safe rule: change only `width="200"` to another value like `160`, `220`, or `260`.
