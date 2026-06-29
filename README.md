# Auto JSON-LD Schema v2.0 - WordPress Plugin

A powerful, agency-focused WordPress plugin that automatically injects a complete **JSON-LD `@graph` schema** into every page for maximum Google visibility, rich results, and E-E-A-T signals.

## Features

### Schema Types
- `WebSite` with SearchAction (Sitelinks Searchbox)
- `Organization` / `LocalBusiness` with address, hours, social profiles
- `Article` / `BlogPosting` with full author E-E-A-T signals
- `WebPage`, `AboutPage`, `ContactPage`
- `FAQPage` - auto-detected from `id="faq"` HTML section
- `Service` with area served and pricing
- `BreadcrumbList` on all inner pages
- `ItemList` for portfolio/case study pages
- Custom JSON-LD schema editor per page

### SEO & Meta
- Per-page SEO title, meta description, canonical URL override
- Robots meta (noindex / nofollow) per page
- Open Graph tags (Facebook, LinkedIn)
- Twitter Card tags
- Full `@graph` output - Google's preferred format
- `@id` linking between schema nodes (schema graph)
- `inLanguage` on all content schemas
- Image dimensions (`width`, `height`) in `ImageObject`

### E-E-A-T Signals
- Author `sameAs` (LinkedIn, Twitter/X)
- Organization `sameAs` (all social profiles)
- `knowsAbout` on Organization
- `foundingDate`, `numberOfEmployees`
- Opening hours specification

### Editor Experience
- Tabbed meta box in every post/page editor
- Visual schema type selector (card-based checkboxes)
- Per-schema field forms (Service details, ItemList URLs, etc.)
- Custom JSON-LD editor with live JSON validator
- Schema preview panel (loads live output from published page)

## Installation

```bash
git clone https://gitlab.com/bitshive-inc-group/auto-jsonld.git wp-content/plugins/auto-jsonld
```

Then activate from **Plugins > Installed Plugins**.

## Configuration

1. Go to **Settings > Auto JSON-LD** to configure organization info, social profiles, business hours, and default schemas.
2. On any post/page, use the **JSON-LD Schema & SEO** meta box to:
   - Set custom SEO title, description, canonical URL
   - Select which schema types to inject
   - Add custom JSON-LD schema
   - Preview the generated output

## FAQ Auto-Detection

Add `id="faq"` to any section on your page:

```html
<section id="faq">
  <h2>What services do you offer?</h2>
  <p>We offer web design, development, and SEO services.</p>

  <h2>How long does a project take?</h2>
  <p>Typically 4-8 weeks depending on scope.</p>
</section>
```

The plugin will automatically extract these as `FAQPage` schema - no manual input needed.

## File Structure

```
auto-jsonld/
|-- auto-jsonld-schema.php        # Main plugin loader
|-- includes/
|   |-- class-settings.php        # Admin settings page
|   |-- class-meta-box.php        # Editor meta box
|   |-- class-content-parser.php  # FAQ & image extraction
|   |-- class-schema-types.php    # All schema type builders
|   |-- class-schema-engine.php   # Schema output engine
|   `-- class-opengraph.php       # Open Graph & Twitter Card
|-- admin/
|   |-- css/meta-box.css
|   `-- js/meta-box.js
`-- README.md
```

## Requirements

- WordPress 5.8+
- PHP 7.4+

## License

GPL-2.0+
