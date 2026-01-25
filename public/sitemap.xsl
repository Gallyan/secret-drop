<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9">
<xsl:output method="html" encoding="UTF-8" indent="yes"/>

<xsl:template match="/">
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Sitemap - Secret Drop</title>
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --primary: #7c3aed;
            --primary-light: #ede9fe;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f172a;
                --card: #1e293b;
                --text: #f1f5f9;
                --text-muted: #94a3b8;
                --border: #334155;
                --primary: #a78bfa;
                --primary-light: #1e1b4b;
            }
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding: 2rem 1rem;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        header {
            text-align: center;
            margin-bottom: 2rem;
        }
        h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary), #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.75rem 1rem;
            text-align: left;
        }
        td {
            padding: 1rem;
            border-top: 1px solid var(--border);
        }
        td a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        td a:hover {
            text-decoration: underline;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .priority {
            font-weight: 600;
        }
        footer {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        footer a {
            color: var(--primary);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Sitemap XML</h1>
            <p class="subtitle">
                Ce fichier sitemap contient <strong><xsl:value-of select="count(sitemap:urlset/sitemap:url)"/></strong> URL(s)
            </p>
        </header>
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>Frequence</th>
                        <th>Priorite</th>
                    </tr>
                </thead>
                <tbody>
                    <xsl:for-each select="sitemap:urlset/sitemap:url">
                        <tr>
                            <td>
                                <a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a>
                            </td>
                            <td>
                                <span class="badge"><xsl:value-of select="sitemap:changefreq"/></span>
                            </td>
                            <td class="priority">
                                <xsl:value-of select="sitemap:priority"/>
                            </td>
                        </tr>
                    </xsl:for-each>
                </tbody>
            </table>
        </div>
        <footer>
            <p>Genere par <a href="/">Secret Drop</a></p>
        </footer>
    </div>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
