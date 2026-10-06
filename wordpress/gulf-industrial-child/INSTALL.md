# Gulf Industrial Solutions website (version 2.0)

Hello Elementor child theme. Works with Elementor Free. No extra plugins.

## Update on staging

1. Appearance → Themes → Add New → Upload Theme → choose `gulf-industrial-child.zip` → **Replace current with uploaded**.
2. Open any page of the WordPress dashboard once. The theme creates and publishes these pages automatically
   (existing pages are never changed):
   About Us · Engineering Services · Pulp & Paper · Corrugated Packaging · Water & Wastewater · Rental Services · Contact Us
3. The homepage keeps working as before. If you inserted the Elementor template earlier, re-import
   `elementor-homepage.json` to get the two new homepage sections (Why Choose Us, Technology Partners).
4. Settings → Permalinks → click **Save** once (refreshes the page links).

## Add your photos

Appearance → Customize → **Gulf Website Images**. Each page has its own section; every image area shows the
recommended size. Until you upload a photo, the website shows a striped box saying "Image area" with the size.
Full list: `IMAGES.md`.

## Enquiry form

On the Contact Us page. Each enquiry is saved under **Enquiries** in the dashboard and emailed to
info@gulfindustrialsols.com (change it in Customize → Gulf Website Images → Enquiry form).
Email sending depends on your host. If emails do not arrive, the enquiries are still saved in the dashboard.

## Editing text

All text is in `languages/en.json` and `languages/ar.json` (homepage) and `languages/pages/*.json` (inner pages).
English and Arabic are kept side by side. Switch language with the English / العربية link or `?lang=ar`.

## Country-based language (optional, off by default)

Needs Cloudflare in front of the site. Then add `define('GULF_TRUST_CF_COUNTRY', true);` to wp-config.php.
GCC visitors (SA, AE, QA, KW, BH, OM) get Arabic first; a visitor's manual choice always wins.
Bypass caching for `/wp-json/gulf/v1/language`.

## Please confirm before going live

- Brand name now follows your Word document: Gulf Industrial Solutions / الخليج للحلول الصناعية.
- The CEO's name is not in the document; the About page shows only "Chief Executive Officer".
- Technology Partners and Group Companies show logo placeholders until you upload logos.
