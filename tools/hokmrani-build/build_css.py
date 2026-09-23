#!/usr/bin/env python3
"""Build the plugin stylesheet from the original static landing CSS.

The original stylesheet is written for a stand-alone HTML page. Inside WordPress
and Elementor the same rules must:

* only apply to pages that use the landing widgets (scoped under ``.hk-page``),
* keep exactly the same cascade between themselves (every selector gains the
  same +1 class of specificity, so their relative order never changes),
* beat Elementor's generic rules such as ``.elementor img``,
* resolve asset URLs from the plugin's ``assets`` folder.

Rules that only belong to unused design versions (``data-design-version="3"``)
are dropped because they can never match.

Usage: build_css.py <original style.css> <compat.css> <output.css>
"""
import re
import sys


def strip_comments(css):
    out, i, n = [], 0, len(css)
    quote = None
    while i < n:
        c = css[i]
        if quote:
            out.append(c)
            if c == '\\' and i + 1 < n:
                out.append(css[i + 1])
                i += 2
                continue
            if c == quote:
                quote = None
            i += 1
            continue
        if c in '"\'':
            quote = c
            out.append(c)
            i += 1
            continue
        if css.startswith('/*', i):
            end = css.find('*/', i + 2)
            i = n if end < 0 else end + 2
            continue
        out.append(c)
        i += 1
    return ''.join(out)


def parse_block(css, i=0):
    """Return (nodes, index). Nodes are ('rule', selector, body) or ('at', prelude, children|body, is_nested)."""
    nodes = []
    n = len(css)
    buf_start = i
    while i < n:
        c = css[i]
        if c == '}':
            return nodes, i + 1
        if c in '"\'':
            q = c
            i += 1
            while i < n and css[i] != q:
                i += 2 if css[i] == '\\' else 1
            i += 1
            continue
        if c == ';':
            # statement at-rule such as @charset / @import
            stmt = css[buf_start:i].strip()
            if stmt:
                nodes.append(('stmt', stmt))
            i += 1
            buf_start = i
            continue
        if c == '{':
            prelude = css[buf_start:i].strip()
            if prelude.startswith('@') and re.match(r'@(media|supports|layer|container|document)\b', prelude):
                children, i = parse_block(css, i + 1)
                nodes.append(('at', prelude, children, True))
            else:
                # plain rule / keyframes / font-face: capture raw body with nesting
                depth, j = 1, i + 1
                while j < n and depth:
                    if css[j] == '{':
                        depth += 1
                    elif css[j] == '}':
                        depth -= 1
                    elif css[j] in '"\'':
                        q = css[j]
                        j += 1
                        while j < n and css[j] != q:
                            j += 2 if css[j] == '\\' else 1
                    j += 1
                body = css[i + 1:j - 1]
                if prelude.startswith('@'):
                    nodes.append(('at', prelude, body, False))
                else:
                    nodes.append(('rule', prelude, body))
                i = j
            buf_start = i
            continue
        i += 1
    return nodes, i


def split_selectors(sel):
    parts, depth, cur = [], 0, []
    for ch in sel:
        if ch in '([':
            depth += 1
        elif ch in ')]':
            depth -= 1
        if ch == ',' and depth == 0:
            parts.append(''.join(cur))
            cur = []
        else:
            cur.append(ch)
    parts.append(''.join(cur))
    return [' '.join(p.split()) for p in parts if p.strip()]


DV = r'\[\s*data-design-version\s*=\s*"?(\d)"?\s*\]'


def transform_selector(s):
    s = re.sub(r'body:is\(\s*' + DV + r'\s*,\s*' + DV + r'\s*\)', lambda m: 'body.hk-dv2' if '2' in (m.group(1), m.group(2)) else 'body.hk-never', s)
    if re.search(r'data-design-version\s*=\s*"?[13]', s) or 'hk-never' in s:
        return None
    s = re.sub(DV, '.hk-dv2', s)
    # The page wrapper <main> becomes an Elementor container with the class hk-main.
    s = re.sub(r'(?<![\w.#\-])main(?![\w\-])', '.hk-main', s)
    if re.match(r'html(?![\w\-])', s):
        return s
    if s.startswith(':root'):
        return '.hk-page' + s[len(':root'):]
    if re.match(r'body(?![\w\-])', s):
        return 'body.hk-page' + s[4:]
    return '.hk-page ' + s


URLS = {
    'assets/DanaVF.woff2': '../fonts/DanaVF.woff2',
}


def fix_urls(body):
    def repl(m):
        q, path = m.group(1), m.group(2)
        if path.startswith('data:'):
            return m.group(0)
        if path in URLS:
            path = URLS[path]
        elif path.startswith('assets/'):
            path = '../img/' + path[len('assets/'):]
        return 'url(%s%s%s)' % (q, path, q)
    return re.sub(r'url\(\s*(["\']?)([^"\')]+)\1\s*\)', repl, body)


def minify_body(body):
    # Declarations are kept verbatim (strings such as `content` values must not change).
    return body.strip()


def render(nodes, stats):
    out = []
    for node in nodes:
        kind = node[0]
        if kind == 'stmt':
            out.append(node[1] + ';')
        elif kind == 'rule':
            sels = []
            for s in split_selectors(node[1]):
                t = transform_selector(s)
                stats['selectors'] += 1
                if t is None:
                    stats['dropped'] += 1
                    continue
                sels.append(t)
            if not sels:
                continue
            body = minify_body(fix_urls(node[2]))
            if body:
                out.append(','.join(sels) + '{' + body + '}')
        elif kind == 'at':
            prelude = ' '.join(node[1].split())
            if node[3]:
                inner = render(node[2], stats)
                if inner:
                    out.append(prelude + '{' + inner + '}')
            else:
                out.append(prelude + '{' + minify_body(fix_urls(node[2])) + '}')
    return '\n'.join(out)


def main():
    src, compat, dst = sys.argv[1:4]
    css = strip_comments(open(src, encoding='utf-8').read())
    nodes, _ = parse_block(css)
    stats = {'selectors': 0, 'dropped': 0}
    body = render(nodes, stats)
    header = open(compat, encoding='utf-8').read().strip()
    with open(dst, 'w', encoding='utf-8') as fh:
        fh.write(header + '\n/* ---- Landing styles (generated from the original page by tools/hokmrani-build/build_css.py) ---- */\n' + body + '\n')
    print('selectors: %(selectors)d, dropped: %(dropped)d' % stats, file=sys.stderr)


if __name__ == '__main__':
    main()
