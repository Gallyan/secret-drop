<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9">
<xsl:output method="html" encoding="UTF-8" indent="yes"/>

<xsl:template match="/">
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Sitemap XML - Secret Drop</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml"/>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-gradient-from: #f9fafb;
            --bg-gradient-via: #f3f4f6;
            --bg-gradient-to: #f9fafb;
            --card-bg: rgba(255, 255, 255, 0.8);
            --card-border: #e5e7eb;
            --text: #111827;
            --text-muted: #6b7280;
            --text-light: #9ca3af;
            --violet-500: #8b5cf6;
            --violet-600: #7c3aed;
            --indigo-600: #4f46e5;
            --emerald-500: #10b981;
            --input-bg: #f9fafb;
            --input-border: #d1d5db;
        }

        .dark {
            --bg-gradient-from: #0f172a;
            --bg-gradient-via: #1e293b;
            --bg-gradient-to: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.5);
            --card-border: rgba(51, 65, 85, 0.5);
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --text-light: #64748b;
            --input-bg: rgba(15, 23, 42, 0.5);
            --input-border: rgba(71, 85, 105, 0.5);
        }

        html {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(to bottom right, var(--bg-gradient-from), var(--bg-gradient-via), var(--bg-gradient-to));
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            padding-bottom: 4rem;
        }

        .container {
            width: 100%;
            max-width: 56rem;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--card-border);
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .header {
            padding: 2rem 2rem 1.5rem;
            border-bottom: 1px solid var(--card-border);
            background: linear-gradient(to bottom right, rgba(124, 58, 237, 0.05), rgba(79, 70, 229, 0.05));
        }

        .dark .header {
            background: linear-gradient(to bottom right, rgba(124, 58, 237, 0.1), rgba(79, 70, 229, 0.1));
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .icon-box {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 1rem;
            background: linear-gradient(to bottom right, var(--violet-500), var(--indigo-600));
            box-shadow: 0 10px 15px -3px rgba(139, 92, 246, 0.25);
            flex-shrink: 0;
        }

        .icon-box svg {
            width: 1.75rem;
            height: 1.75rem;
            color: white;
        }

        h1 {
            font-size: 1.875rem;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .subtitle strong {
            color: var(--text);
            font-weight: 600;
        }

        .content {
            padding: 1.5rem 2rem 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            position: sticky;
            top: 0;
        }

        th {
            background: var(--input-bg);
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--card-border);
        }

        th:first-child {
            border-radius: 0.75rem 0 0 0.75rem;
        }

        th:last-child {
            border-radius: 0 0.75rem 0.75rem 0;
            text-align: center;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--card-border);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        td a {
            color: var(--violet-600);
            text-decoration: none;
            font-weight: 500;
            word-break: break-all;
        }

        .dark td a {
            color: var(--violet-500);
        }

        td a:hover {
            text-decoration: underline;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            background: rgba(139, 92, 246, 0.1);
            color: var(--violet-600);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .dark .badge {
            color: var(--violet-500);
        }

        .priority {
            text-align: center;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .priority-high {
            color: var(--emerald-500);
        }

        footer {
            text-align: center;
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--card-border);
            color: var(--text-light);
            font-size: 0.875rem;
        }

        footer a {
            color: var(--violet-600);
            text-decoration: none;
            font-weight: 500;
        }

        .dark footer a {
            color: var(--violet-500);
        }

        footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            .header { padding: 1.5rem; }
            .content { padding: 1rem 1.5rem 1.5rem; }
            th, td { padding: 0.75rem; }
        }
    </style>
    <script>
        (function() {
            if (localStorage.getItem('theme') === 'light') {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="header-content">
                    <div class="icon-box">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h1>Sitemap XML</h1>
                </div>
                <p class="subtitle">
                    <strong><xsl:value-of select="count(sitemap:urlset/sitemap:url)"/></strong> URL(s)
                </p>
            </div>

            <div class="content">
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
                                <td class="priority priority-high">
                                    <xsl:value-of select="sitemap:priority"/>
                                </td>
                            </tr>
                        </xsl:for-each>
                    </tbody>
                </table>

            </div>

            <footer>
                <a href="/">Secret Drop</a>
            </footer>
        </div>
    </div>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
