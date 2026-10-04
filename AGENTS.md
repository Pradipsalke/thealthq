# The ALT HQ website: rules for AI agents

This is the website for the book **The ALT HQ: GCC Beyond The Obvious** by Nilesh Biniwale and Nilesh Kulkarni (Diamond Publications). It is a plain static site hosted on GitHub Pages. The owner is not a developer, so keep every change simple, small and easy to review.

## Golden rules
1. **No build tools, frameworks or package managers.** Plain HTML and CSS only. Do not add React, Next.js, npm, Tailwind or a static site generator unless the owner explicitly asks.
2. **Do not redesign.** Keep the existing layout, colours, fonts and spacing. Copy the patterns already in the files.
3. **Never invent facts.** Do not make up quotes, statistics, endorsements, client names, events, dates or links. If something is missing, leave a visible placeholder in square brackets, e.g. `[Contact email]`, and list it in TODO.md.
4. **Keep the authors' wording.** When adding articles or quotes supplied by the authors, reproduce their text exactly. Only fix obvious typos and tell the owner what you fixed.
5. **Check every page on mobile** (390 px wide) and desktop (1440 px) after any change. Nothing may scroll sideways.
6. **Show the owner a summary of changes before committing**, then commit with a short, clear message.

## Project structure
```
index.html                 Homepage (all sections in one page)
insights/*.html            Article pages
css/site.css               Homepage styles and responsive breakpoints
css/article.css            Article page styles
assets/                    Images (book, authors, logo, seal, diagrams)
templates/article-template.html   Starting point for new articles
TODO.md                    Open items and placeholders
.agents/skills/add-insight Step-by-step skill for adding an article
```

## Brand
- Colours: deep blue `#005B94` (buttons, links, small text), cerulean `#0088CC` (large headings only; fails contrast for body text), sunflower yellow `#FFD200` (backgrounds, buttons and accents only, never text on white), charcoal `#1E2229`, slate `#4A5568`, surface `#F8FAFC`, border `#E2E8F0`.
- Fonts: Montserrat (headings, weights 700 to 900) and Inter (body). Loaded from Google Fonts.
- Spelling: British English in site copy (organisation, centre). Keep endorsers' and external authors' original spelling.
- Title hierarchy: THE ALT HQ, then "GCC Beyond The Obvious", then "Building Your GCC as an Alternate HQ", then "Product Mindset. Startup Speed. Enterprise Scale."

## How the pages are built
- Styles are mostly inline on each element. Shared rules and responsive breakpoints live in `css/site.css` and `css/article.css`.
- Responsive breakpoints: 1180 px, 960 px and 640 px. Grids use the classes `alt-g2`, `alt-g3`, `alt-g4`, which collapse on smaller screens. Reuse these classes for new grids.
- Hover effects use `alt-hov` plus `alt-hov-yellow`, `alt-hov-cerulean` or `alt-hov-deep`. They only apply on devices with a mouse.
- The mobile menu is controlled by the small script at the bottom of `index.html`. If you add a section to the desktop navigation, add the same link to `#alt-mobile-menu`.
- Article pages live in `insights/`, so their links to images and the homepage start with `../`.

## Homepage sections (in order)
Header and mobile menu, Hero, Journey (cost centre to alternate HQ), The ALT HQ model (three pillars), Inside the book (12 chapters), Who it is for (4 audiences), Endorsements, Authors, Work with us (consulting via EnterpriseJoy), Insights (3 article cards, Watch and listen videos, newsletter), Buy, Footer.

## Facts that are confirmed
- Price ₹799, hardcover, Diamond Publications. Amazon link: https://amzn.in/d/090Nqxgl
- Diamond Publications: https://dpbooks.in/ (publisher homepage; no product page yet)
- Consulting is delivered with EnterpriseJoy: https://www.enterprisejoy.com/
- LinkedIn: https://www.linkedin.com/in/nileshbiniwale/ and https://www.linkedin.com/in/nileshrk/
- Endorsements: J.A. Chowdary and Nitin Deshpande (text is final; do not edit).

## Common tasks
- **Add an article:** follow `.agents/skills/add-insight/SKILL.md`.
- **Change text:** edit the HTML directly; keep the surrounding tags and styles.
- **Replace an image:** keep the same file name in `assets/` where possible, compress to under 400 KB, and keep the `alt` text accurate.
- **Fix a placeholder:** replace the bracketed text, then remove it from TODO.md.

## Before committing
1. Open `index.html` and every changed page in a browser at desktop and phone widths.
2. Click every changed link.
3. Confirm no `[placeholder]` text was added without a matching TODO.md entry.
4. Commit and push to `main`. GitHub Pages publishes automatically within a few minutes.
