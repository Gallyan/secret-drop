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
    <title>Sitemap XML - Secret Drop</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml"/>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #1e293b;
            background: #f8fafc;
            margin: 0;
            padding: 20px;
        }

        #sitemap {
            max-width: 980px;
            margin: 0 auto;
        }

        #sitemap__header {
            background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
            color: white;
            padding: 30px 40px;
            border-radius: 8px 8px 0 0;
        }

        #sitemap__header h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 8px 0;
        }

        #sitemap__header p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }

        #sitemap__header a {
            color: white;
            text-decoration: underline;
        }

        #sitemap__table {
            background: white;
            border: 1px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 8px 8px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f1f5f9;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 20px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th:last-child {
            text-align: center;
            width: 100px;
        }

        td {
            padding: 12px 20px;
            border-bottom: 1px solid #f1f5f9;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:nth-child(even) {
            background: #fafafa;
        }

        tr:hover {
            background: #f5f3ff;
        }

        td a {
            color: #7c3aed;
            text-decoration: none;
            font-weight: 500;
        }

        td a:hover {
            text-decoration: underline;
        }

        td a:visited {
            color: #6d28d9;
        }

        .badge {
            display: inline-block;
            background: #ede9fe;
            color: #7c3aed;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .priority {
            text-align: center;
            font-weight: 600;
            color: #059669;
        }

        #sitemap__footer {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
            font-size: 13px;
        }

        #sitemap__footer a {
            color: #7c3aed;
            text-decoration: none;
        }

        #sitemap__footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div id="sitemap">
        <div id="sitemap__header">
            <h1>Sitemap XML</h1>
            <p>
                <xsl:value-of select="count(sitemap:urlset/sitemap:url)"/> URL(s) -
                <a href="/">Secret Drop</a>
            </p>
        </div>
        <div id="sitemap__table">
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
        <div id="sitemap__footer">
            Genere par <a href="/">Secret Drop</a>
        </div>
    </div>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
