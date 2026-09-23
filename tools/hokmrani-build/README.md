# Hokmrani landing → Elementor plugin build

`original/` is the static landing page (v4) the plugin reproduces.

Rebuild the plugin stylesheet after changing `original/style.css` or `compat.css`:

```sh
python3 tools/hokmrani-build/build_css.py \
  tools/hokmrani-build/original/style.css \
  tools/hokmrani-build/compat.css \
  hokmrani-elementor/assets/css/landing.css
```

What the build does:

- scopes every selector under `.hk-page` (a body class the plugin adds), giving every rule the same
  +1 class so the original cascade order is unchanged and Elementor's generic rules lose;
- maps `body[data-design-version="2"]` / `body.is-version-4` to body classes and `main` to `.hk-main`
  (the Elementor container that replaces `<main>`);
- drops rules for the unused design version 3;
- rewrites asset URLs to the plugin's `assets/img` and `assets/fonts`.

`compat.css` (prepended) neutralises Elementor's wrappers (`display: contents` on the front end).

Package the plugin:

```sh
cd /path/to/repo && rm -f hokmrani-elementor-*.zip && zip -rq hokmrani-elementor-1.0.0.zip hokmrani-elementor
```
