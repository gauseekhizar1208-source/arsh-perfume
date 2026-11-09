# Arsh Perfume storefront design system

Direction: immersive midnight and amber fragrance house. This revision follows the user's request for a complete 3D visual direction. Typography remains Cormorant and Montserrat, paired with dark textured surfaces, amber glass, metallic brass and warm scene lighting.

Skill research: ui-ux-pro-max `luxury fragrance immersive 3d` design-system search supports the luxury typography, neutral/gold palette, showcase hierarchy and prominent discovery actions. Its Apple-platform Liquid Glass recommendation does not fit this web storefront; the native CSS 3D sculpture and illuminated materials are a deliberate product-specific direction. Motion guidance requires reduced-motion support and limits decorative movement.

Tokens: background #100f0d; panel #1b1915; text #f1eadc; secondary text #b2aa9b; accent #dcb477; borders #39332a. Tokens stay scoped to `.storefront`. Administration shares these tokens and uses the `.admin-app` layout extensions documented in `pages/admin.md`.

Hero: an original CSS 3D amber bottle with separate glass faces, a ribbed metallic cap, neck, label and pedestal. This is explicitly marked as a brand illustration. A native range slider supports touch and keyboard rotation; mouse users can drag the bottle. Actual catalogue/banner imagery appears in the adjacent spotlight and product cards.

Collections: three illuminated cards with spherical sculptural objects, metallic plinths and orbital details. Categories use actual names, links and product counts. Product sections avoid repeating the same products across homepage edits.

Layout: 1360px container, 50px desktop and 22px phone gutters. Split desktop hero and stacked phone scene. Product grid uses four desktop columns and two phone columns; collection panels stack on phones. Story, newsletter, catalogue, product, cart, authentication and account views inherit the new material and colour system.

Motion: Framer Motion's DOM API provides a staggered hero-copy entrance, a perspective bottle entrance, once-per-view product and section reveals, and staggered collection/service reveals. It uses `framer-motion/dom/mini` for animation and `framer-motion/dom` for `inView` and `stagger`; React is not required. Animation cleanup restores CSS transforms so the bottle rotation and card tilt keep working. Pointer feedback runs only on active cards. No perpetual render loop or automatic bottle spin. Reduced-motion preference cancels active entrances and skips new animations. Content is visible without JavaScript. Admin screens retain their separate chart and navigation behavior.

Implementation: resources/css/storefront.css; resources/js/storefront-motion.js; resources/views/components/fragrance-sculpture.blade.php; resources/views/pages/home.blade.php; resources/views/layouts/app.blade.php; resources/views/components/product-card.blade.php.
