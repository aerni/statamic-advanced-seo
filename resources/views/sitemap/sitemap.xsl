<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:html="http://www.w3.org/TR/REC-html40" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
    <xsl:template match="/">
        <html>
            <head>
                <title>XML Sitemap</title>
                <style>
                    body {
                        font-family: 'Roboto', 'Helvetica', 'Arial', sans-serif;
                        font-size: 16px;
                        color: #fff;
                        background-color: #0C001F;
                        -webkit-font-smoothing: antialiased;
                        -moz-osx-font-smoothing: grayscale;
                    }

                    .sitemap__outer {
                        width: 95%;
                        max-width: 750px;
                        margin: 20px auto;
                    }

                    h1 {
                        font-size: 2.5rem;
                    }

                    th {
                        text-align: left;
                        border-bottom: 2px solid #fff;
                    }

                    table {
                        font-size: 13px;
                        border-collapse: collapse;
                        width: 100%;
                        margin: 0 0 30px;
                    }

                    tbody > tr:nth-child(even) {
                        background-color: rgba(4, 216, 249, .1);
                    }

                    td > a {
                        color: #fff;
                    }

                    a {
                        color: #fff;
                    }

                    .smol {
                        display: block;
                        padding: 10px 0;
                        font-size: 10px;
                        border-top: 1px solid #fff;
                    }
                </style>
            </head>
            <body>
                <div class="sitemap__outer">
                    <h1>XML Sitemap</h1>
                    <p>This is the sitemap for your website, the sitemap's purpose is to inform search engines of the pages on your website that can be indexed.</p>

                    <xsl:if test="count(sitemap:sitemapindex/sitemap:sitemap) &gt; 0">
                        <p>This sitemap index consists of <strong><xsl:number value="count(sitemap:sitemapindex/sitemap:sitemap)" /></strong> sitemap(s).</p>
                        <table class="sitemap__table" cellpadding="6">
                            <thead>
                                <tr>
                                    <th width="66%">Sitemap</th>
                                    <th width="33%">Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">
                                    <tr>
                                        <td>
                                            <xsl:variable name="sitemapLink">
                                                <xsl:value-of select="sitemap:loc" />
                                            </xsl:variable>
                                            <a href="{$sitemapLink}"><xsl:value-of select="sitemap:loc" /></a>
                                        </td>
                                        <td>
                                            <xsl:value-of select="concat(substring(sitemap:lastmod,0,11),concat(' ', substring(sitemap:lastmod,12,5)))" />
                                        </td>
                                    </tr>
                                </xsl:for-each>
                            </tbody>
                        </table>
                    </xsl:if>

                    <xsl:if test="count(sitemap:urlset/sitemap:url) &gt; 0">
                        <p>This sitemap consists of <strong><xsl:number value="count(sitemap:urlset/sitemap:url)" /></strong> link(s).</p>
                        <table class="sitemap__table" cellpadding="4">
                            <thead>
                                <tr>
                                    <th width="60%">URL</th>
                                    <th width="10%">Priority</th>
                                    <th width="10%">Change Freq.</th>
                                    <th width="20%">Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <xsl:for-each select="sitemap:urlset/sitemap:url">
                                    <tr>
                                        <td>
                                            <xsl:variable name="pageLink">
                                                <xsl:value-of select="sitemap:loc" />
                                            </xsl:variable>
                                            <a target="_blank" rel="noopener nofollow" href="{$pageLink}"><xsl:value-of select="sitemap:loc" /></a>
                                        </td>
                                        <td>
                                            <xsl:value-of select="sitemap:priority" />
                                        </td>
                                        <td>
                                            <xsl:value-of select="sitemap:changefreq" />
                                        </td>
                                        <td>
                                            <xsl:value-of select="concat(substring(sitemap:lastmod,0,11),concat(' ', substring(sitemap:lastmod,12,5)))" />
                                        </td>
                                    </tr>
                                </xsl:for-each>
                            </tbody>
                        </table>
                    </xsl:if>

                    <xsl:if test="sitemap:sitemapindex">
                        <p class="smol">Generated by Advanced SEO v<xsl:value-of select="sitemap:sitemapindex/@advanced-seo-version" />, a <a target="_blank" rel="noopener nofollow" href="https://statamic.com/addons/aerni/advanced-seo">Statamic addon</a> by <a target="_blank" rel="noopener nofollow" href="https://michaelaerni.ch">Michael Aerni</a></p>
                    </xsl:if>
                    <xsl:if test="sitemap:urlset">
                        <p class="smol">Generated by Advanced SEO v<xsl:value-of select="sitemap:urlset/@advanced-seo-version" />, a <a target="_blank" rel="noopener nofollow" href="https://statamic.com/addons/aerni/advanced-seo">Statamic addon</a> by <a target="_blank" rel="noopener nofollow" href="https://michaelaerni.ch">Michael Aerni</a></p>
                    </xsl:if >
                </div>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>
