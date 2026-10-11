# AVENZA WordPress Theme: Handover (v1.5.0)

Ye document batata hai ki theme mein kya hai, use kaise install ya update karna hai, aur launch se pehle kya kaam bacha hai.

**Install karne wali file:** `dist/avenza.zip`

**Preview:** `docs/preview-home-desktop.jpg`, `docs/preview-home-mobile.jpg`, `docs/preview-service-desktop.jpg`

---

## 0. Version 1.5.0 mein naya (108 services)

- **108 service pages**, 10 disciplines mein (WebFX jaisa structure): AI Search and GEO (11), SEO (24), Content (10), Digital PR and Reputation (7), Paid Media (11), Social (11), Websites (12), CRO and Analytics (10), Email and Automation (7), E-commerce and Marketplaces (5).
- Har discipline ka **hub page** `/expertise/<naam>/` par, aur Services page par poori A-Z directory.
- Menu mein Services ke neeche 10 disciplines. Footer mein bhi.
- Har naye service page par: hero, short answer (40-60 words), service ka intro, 6 deliverables, 5-step process, 3 FAQ (FAQ schema ke saath), same discipline ki related services, aur sources.
- Har service ki apni numbered engraved plate (N° 13 se N° 108).
- Setup ab 20-20 pages ke batches mein chalta hai, isliye bade content par bhi time-out nahi hota.
- Zip ab ~9 MB hai. Agar hosting upload limit kam ho, to zip ko FTP ya hosting file manager se `wp-content/themes/` mein extract kar dein.
- Note: naye 96 pages ke kuch hisse (why it matters, process, sources) discipline-level par shared hain. Agle phase mein inhe har service ke liye alag deep content se badla ja sakta hai.

## 1. Kya bana hai

| Cheez | Detail |
|---|---|
| Design | Lush emerald green aur white. Headings Cormorant Garamond (engraved serif) mein, body text Manrope mein. Magazine jaisa layout (left mein N° number aur heading, right mein text), square buttons, Roman numeral steps, double-frame CTA. |
| Pages | Home, About, Our Approach, Services, Insights (blog), FAQ, Contact, Privacy Policy, Terms of Service |
| Service pages | 12 alag pages, `/services/<naam>/` par: GEO, AEO, SEO, Technical SEO, Local SEO, E-commerce SEO, Content Marketing, Link Building and Digital PR, Google Ads, Social Media, Website Design, CRO |
| Articles | 6 deep, sourced guides: GEO guide, SEO vs AEO vs GEO, AI Overviews sources, llms.txt aur AI crawlers, Local SEO India, Core Web Vitals |
| Content | Lagbhag 28,000 words. Har page par 40 se 60 words ka "short answer" block, ek hi H1, aur primary sources ke links (Google, Pew Research, KDD 2024 GEO paper, IAMAI, Baymard, W3C, OpenAI, Anthropic). |
| Artwork | 26 original engraved images: har service ki numbered plate (N° 01 se N° 12), 6 page plates, 6 article covers, 2 diagrams, logo, share image, favicon |
| Templates | Page, service, article (table of contents ke saath), Insights listing, category, search, 404 |

Total public pages: 9 pages + 12 services + 6 articles = **27**, plus 404 aur search.

## 2. Features

- **One-click setup:** Appearance > AVENZA Setup. Chhote steps mein chalta hai, isliye sasti hosting par bhi time-out nahi hota.
- **Safe updates:** naya version aane par "Apply the AVENZA update" button. Jo page aapne khud edit kiya hai, wo bilkul nahi chhua jata, aur setup screen uska naam bata deti hai.
- **Native block editor:** har page normal WordPress editor mein edit hota hai. Koi page builder nahi. Test mein 27 pages ke saare blocks valid nikle.
- **Contact form:** nonce, honeypot, time trap, rate limit, server-side validation, DPDP consent checkbox. Har enquiry email bhi hoti hai aur Dashboard > Enquiries mein save bhi hoti hai.
- **WhatsApp aur call buttons:** +91 96809 51099 par. Customizer se band ya change kar sakte hain.
- **SEO:** meta title aur description (har page ke neeche "Search appearance" box), canonical, Open Graph, Twitter card. Yoast, Rank Math, AIOSEO ya SEOPress lagate hi theme ke meta tags apne aap band ho jaate hain.
- **Schema (JSON-LD):** Organization + ProfessionalService, WebSite, WebPage, BreadcrumbList, Service, ItemList, FAQPage, BlogPosting. Saare URL absolute, ek hi Organization @id.
- **GEO/AEO layer:** `/llms.txt` apne aap banta hai. `robots.txt` GPTBot, OAI-SearchBot, ClaudeBot, PerplexityBot, Google-Extended, Applebot-Extended, CCBot sabko allow karta hai aur sitemap aur llms.txt ka link deta hai.
- **Performance:** fonts theme ke andar (bahar se load nahi), jQuery nahi, JavaScript defer, images lazy, hero image high priority.

## 3. Install kaise karein (naya site)

1. WordPress admin > Appearance > Themes > Add New > Upload Theme > `avenza.zip` > Install > Activate.
2. Upar notice aayega: **"Set up the AVENZA website"**. Button dabayein, phir **"Set up the website"**.
3. Progress bar poora hone tak page band na karein (aam taur par 1 se 2 minute).
4. Appearance > Customize > **AVENZA Settings** mein apni details bharein (neeche section 5 dekhein).

## 4. Pehle wala version (1.1.0 se 1.3.0) laga hai to

1. Upload Theme se `avenza.zip` upload karein. Poocha jaye to **"Replace current with uploaded"** chunein.
2. Notice aayega: **"Apply the AVENZA 1.5.0 update"**. Button dabayein aur setup chalayein.
3. Jo pages aapne khud edit kiye the, wo waise hi rahenge, aur setup screen unke naam batayegi.
4. Purane version ke kuch pages ya images ke naam alag the, to wo Pages ya Media mein bache reh sakte hain. Unhe dekh kar Trash kar dein.

## 5. Customizer mein kya bharna hai (Appearance > Customize > AVENZA Settings)

| Setting | Abhi kya hai | Kya karna hai |
|---|---|---|
| Phone | +91 96809 51099 | Theek hai |
| WhatsApp | 919680951099 | Theek hai |
| Public email | Khaali | **Business email daalein.** Ye header, footer, schema aur llms.txt mein dikhega |
| Form enquiries kis email par | Khaali (abhi admin email par jaayengi) | Sales email daalein |
| Office address | Khaali | Address dena hai to daalein. Dete hi schema mein bhi aa jayega |
| Working hours | Khaali | Jaise: Monday to Saturday, 10:00 to 19:00 IST |
| Google Maps embed | Khaali | Address ho to Maps > Share > Embed ka src daalein |
| Social profiles | Khaali | LinkedIn, Instagram, Facebook, X, YouTube. Ye schema `sameAs` mein bhi jaate hain |
| GA4 ID | Khaali | G-XXXXXXXXXX |
| Search Console code | Khaali | Verification meta tag ka content |
| Logo | Built-in AVENZA logo | Chahein to Site Identity mein apna logo file daalein |

## 6. Launch se pehle bache hue kaam (open items)

Ye cheezein maine jaan-boojh kar khud nahi likhi, kyunki ye sirf aap hi de sakte hain. Ek bhi nakli testimonial, client count, award ya saal theme mein nahi hai.

1. **Business email aur (agar dena hai) address.** Inke bina schema mein email aur address nahi jaata.
2. **Asli photos.** Free photo sites (Unsplash, Pexels, Wikimedia) is build environment ke network se blocked thi, isliye saari images original engraved artwork hain, placeholder nahi. Team ya office ki photos ho to dein, ya environment settings mein `images.unsplash.com` aur `images.pexels.com` allow kar dein, phir main free-license photos jod dunga.
3. **Testimonials, case studies, team ke naam aur credentials.** Ye milte hi About aur Home par sections jod sakte hain. Ye E-E-A-T ke liye sabse zaroori hain.
4. **In baaton ki pushti karein**, jo maine aapke "baaki aap decide karo" ke hisaab se likhi hain:
   - Contact page: "reply within one working day", strategy call free hai, English aur Hindi, Google Meet ya phone par call.
   - About page: AVENZA India se kaam karta hai aur dusre deshon ke clients ke saath bhi kaam ke liye taiyar hai.
   - Google Ads FAQ: ad account client ke naam par rehta hai, management fee alag se quote hoti hai.
   - Privacy Policy: jo enquiries kaam mein nahi badaltin, unka data 24 mahine mein delete.
5. **Privacy Policy aur Terms ko apne legal advisor se ek baar padhwa lein**, khaas kar grievance contact aur jurisdiction (abhi "competent courts in India" likha hai, aap shehar specify kar sakte hain).
6. **Domain live hone ke baad:** Search Console aur Bing Webmaster Tools mein site add karein, `https://<domain>/wp-sitemap.xml` submit karein, aur `https://<domain>/llms.txt` aur `/robots.txt` khol kar dekh lein.

## 7. Content kaise edit karein

- **Koi bhi page:** Pages > page kholein > normal block editor. Green hero, sections, FAQ, sab blocks hain.
- **Service:** Dashboard > Services. Card par dikhne wali image "Featured image" hai aur card ka text "Excerpt" hai.
- **Naya article:** Posts > Add New. Featured image 1600×900 rakhein. H2 headings se article mein "On this page" table of contents apne aap ban jaati hai.
- **FAQ:** "Details" block use karein. FAQPage schema apne aap banta hai.
- **Meta title aur description:** editor ke neeche "Search appearance" box mein.

## 8. Testing jo maine chalayi

Real WordPress 7.1.3 (PHP 8.3) par, theme zip se fresh install karke, admin screen ke button se setup chala kar:

- **Responsive aur accessibility:** 29 URL × 9 screen sizes (320px iPhone SE se 1920px tak) = 261 renders. Check kiya: page side mein scroll nahi hota, koi element bahar nahi nikalta, text contrast WCAG AA, buttons aur links kam se kam 44px, prose 15px se chhota nahi, har page par ek hi H1, har image par alt text, sahi template, koi JavaScript error nahi, mobile menu screen par khulta hai aur close button dabta hai.
- **Block editor:** 27 pages, services aur articles editor mein khole, ek bhi invalid block nahi.
- **Functional (31 checks):** form ki galti aur success dono, enquiry save hona, bot submission rukna, llms.txt, robots.txt, sitemap, har template ka schema, meta lengths, mobile menu ka keyboard (Escape, focus), Customizer, Menus, admin screens.
- **PHP:** WP_DEBUG on rakh kar har public URL, setup, form aur admin screens chalaye. Theme se ek bhi PHP warning ya notice nahi aaya.
- **Fresh install aur update dono:** zip ko bilkul naye WordPress par install karke notice ke button se setup chalaya, phir "Apply update" wala raasta bhi chala kar dekha.

## 9. Developer notes

Code repository mein:

- `avenza/`: theme
- `content-src/`: saare pages, services aur articles Markdown mein. `python3 tools/build_content.py` inhe Gutenberg blocks mein badalta hai aur em dash, banned words, answer block length, meta length, H1 aur broken links check karta hai.
- `tools/make_art.py`, `render_art.js`, `convert_art.py`: artwork dobara banane ke liye (rang badalna ho to yahin se).
- `tools/qa.js` aur `tools/functional.js`: upar wali testing.
- `bash tools/package.sh`: content build, PHP aur JS lint, version check, aur `dist/avenza.zip`.

Version badalte waqt teen jagah same rakhein: `avenza/style.css`, `avenza/functions.php` (`AVENZA_VERSION`) aur `content-src/site.json`.
