---
name: add-insight
description: Add a new article to The ALT HQ website. Use when the owner shares an article, PDF, LinkedIn post or book excerpt to publish in the Insights section.
---

# Add an Insights article

1. **Get the text.** Use the owner's text exactly. Do not rewrite or summarise it. Fix only obvious typos and list them for the owner.
2. **Choose a file name.** Short, lowercase, hyphenated, based on the title, e.g. `insights/future-of-gcc-talent.html`.
3. **Copy the template.** Copy `templates/article-template.html` to the new file in `insights/`.
4. **Fill in the header.** Replace ARTICLE TITLE (in `<title>` and `<h1>`), ARTICLE SUBTITLE, CATEGORY, the meta description, the author photo and name, and the reading time (about 200 words per minute, rounded).
   - Author photos: `../assets/author-biniwale.jpg` or `../assets/author-kulkarni.jpg`.
5. **Add the body.** Each paragraph in its own `<p>`. Section headings as `<h2>` in sentence case. Optional: one pull quote, copied word for word from the article, using the `<figure>` pattern from an existing article. Do not place the pull quote right next to the paragraph it repeats.
6. **Images.** Save diagrams in `assets/` (JPG or WebP, under 400 KB) and add them with a descriptive `alt` text and caption.
7. **Update the homepage.** In `index.html`, in the Insights section, either replace the oldest of the three article cards or ask the owner which card to replace. Copy an existing card's markup exactly and change the link, header text, category, title, author and reading time. Keep three cards in the row.
8. **External articles** (published elsewhere, e.g. Thoughtworks): do not copy their text. Add a homepage card that links out with `target="_blank" rel="noopener"` and a one-line summary in your own words.
9. **Check** the new page and the homepage at 1440 px and 390 px wide, and click every new link.
10. **Summarise** the change for the owner, then commit: `Add insight: <title>`.
