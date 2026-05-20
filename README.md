# Bulk Slug Adder — WordPress Plugin

> Append any suffix to post slugs — all at once or hand-pick exactly which ones.
> Built by **Shohidul Dev** · [#shohiduldev](https://github.com/shohiduldev)

---

## What It Does

**Bulk Slug Adder** is a WordPress admin plugin that lets you append a custom suffix (like a phone number, keyword, or location code) to the slugs of any post type — in bulk or selectively.

Built for large sites (10,000+ posts) with batch processing to avoid timeouts.

---

## Features

- 🔗 **Works with any post type** — Pages, Posts, or any Custom Post Type (CPT)
- ⚡ **All Posts mode** — append suffix to every matching post in batches of 500
- ☑️ **Select Specific Posts mode** — checkbox list to hand-pick exactly which posts to update
- 🔍 **Search & filter** posts in the picker
- 👀 **Preview** before running — see old slug vs new slug
- ✅ **Duplicate-safe** — posts already having the suffix are automatically skipped
- 📊 **Live progress bar** — watch updates happen in real time
- 🔄 **Auto flushes rewrite rules** after completion
- 💯 **No timeout** — batch processing handles 10,000+ posts safely

---

## Installation

1. Download the latest release ZIP from the [Releases](../../releases) page
2. In your WordPress admin go to **Plugins → Add New → Upload Plugin**
3. Upload the ZIP file and click **Install Now**
4. Click **Activate Plugin**

You'll find the plugin at **Admin Sidebar → Bulk Slug Adder**

---

## How to Use

### All Posts Mode

1. Select your **Post Type** from the dropdown
2. Enter the **Suffix** you want to append (e.g. `-Add your new Suffix`)
3. Check the **SQL Preview** to confirm the query
4. Click **👀 Preview (first 10)** to verify slugs look correct
5. Click **⚡ Run Now** → confirm the dialog → watch the progress bar

### Select Specific Posts Mode

1. Select your **Post Type** and enter your **Suffix**
2. Click the **☑️ Select Specific Posts** tab
3. Use the search box to filter, then check/uncheck posts
4. Use **Select All** / **Select None** as needed
5. Click **⚡ Run Now** — only checked posts will be updated

---

## Example

| Old Slug | New Slug |
|---|---|
| `mobile-tyre-fitting-london` | `mobile-tyre-fitting-london-07510635870` |
| `mobile-tyre-fitting-cambridge` | `mobile-tyre-fitting-cambridge-07510635870` |
| `mobile-tyre-fitting-oxford` | `mobile-tyre-fitting-oxford-07510635870` |

---

## SQL That Runs (All Mode)

```sql
UPDATE wp_posts
SET post_name = CONCAT(post_name, '-07510635870')
WHERE post_type = 'location'
AND post_status = 'publish'
AND post_name NOT LIKE '%-07510635870';
```

Posts already ending with the suffix are **skipped automatically**.

---

## Requirements

| Requirement | Version |
|---|---|
| WordPress | 5.5 or higher |
| PHP | 7.4 or higher |
| MySQL | 5.6 or higher |

---

## Safety

- Always **backup your database** before running bulk operations
- Use **Preview mode** first to verify changes
- The **Dry Run** approach (preview before run) prevents mistakes
- Double-add protection: suffix is never applied twice to the same slug

---

## Changelog

### v3.0.0
- Added **Select Specific Posts** mode with checkbox list
- Added search/filter inside post picker
- Added Select All / Select None controls
- Plugin renamed to **Bulk Slug Adder**
- Full English UI

### v2.0.0
- Dynamic post type dropdown (all public CPTs)
- Dynamic suffix input (not hardcoded)
- Live SQL preview updates as you type
- Live count stats per post type

### v1.0.0
- Initial release
- Batch processing for 10,000+ posts
- Live progress bar
- Auto rewrite rule flush

---

## Author

**Shohidul Dev** — Shopify & WordPress Developer  
GitHub: [@shohiduldev](https://github.com/shohiduldev)  

---

## License

[GPL-2.0+](https://www.gnu.org/licenses/gpl-2.0.html)
