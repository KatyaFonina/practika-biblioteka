<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    
    <xsl:output method="html" encoding="UTF-8" indent="yes"/>
    
    <xsl:template match="/report">
        <html>
        <head>
            <title>Библиотечный отчет</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    background-color: #f5f5f5;
                }
                .container {
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                    max-width: 1200px;
                    margin: 0 auto;
                }
                h1 {
                    color: #333;
                    border-bottom: 2px solid #007bff;
                    padding-bottom: 10px;
                }
                .info {
                    background-color: #e9f7fe;
                    padding: 15px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                    border-left: 4px solid #007bff;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                th {
                    background-color: #007bff;
                    color: white;
                    padding: 12px;
                    text-align: left;
                }
                td {
                    padding: 10px;
                    border-bottom: 1px solid #ddd;
                }
                tr:hover {
                    background-color: #f5f5f5;
                }
                .overdue-high {
                    color: #dc3545;
                    font-weight: bold;
                }
                .overdue-medium {
                    color: #ffc107;
                    font-weight: bold;
                }
                .no-data {
                    text-align: center;
                    padding: 40px;
                    color: #6c757d;
                    font-size: 18px;
                }
                .stats {
                    display: flex;
                    gap: 20px;
                    margin-bottom: 20px;
                }
                .stat-card {
                    background: #007bff;
                    color: white;
                    padding: 15px;
                    border-radius: 5px;
                    flex: 1;
                    text-align: center;
                }
                .stat-value {
                    font-size: 24px;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>📊 Отчет библиотеки</h1>
                
                <div class="info">
                    <p><strong>Тип отчета:</strong> Просроченные книги</p>
                    <p><strong>Дата формирования:</strong> <xsl:value-of select="generated_date"/></p>
                </div>
                
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-value"><xsl:value-of select="count"/></div>
                        <div>Всего просрочено</div>
                    </div>
                </div>
                
                <xsl:choose>
                    <xsl:when test="count > 0">
                        <table>
                            <thead>
                                <tr>
                                    <th>Инв. номер</th>
                                    <th>Название книги</th>
                                    <th>Автор</th>
                                    <th>Читатель</th>
                                    <th>Дата выдачи</th>
                                    <th>Дней просрочки</th>
                                </tr>
                            </thead>
                            <tbody>
                                <xsl:for-each select="books/overdue_book">
                                    <tr>
                                        <td><xsl:value-of select="inventory_number"/></td>
                                        <td><xsl:value-of select="title"/></td>
                                        <td><xsl:value-of select="author"/></td>
                                        <td><xsl:value-of select="reader_card"/></td>
                                        <td><xsl:value-of select="date_taken"/></td>
                                        <td>
                                            <xsl:attribute name="class">
                                                <xsl:choose>
                                                    <xsl:when test="days_overdue > 60">overdue-high</xsl:when>
                                                    <xsl:when test="days_overdue > 30">overdue-medium</xsl:when>
                                                    <xsl:otherwise>overdue-low</xsl:otherwise>
                                                </xsl:choose>
                                            </xsl:attribute>
                                            <xsl:value-of select="days_overdue"/> дней
                                        </td>
                                    </tr>
                                </xsl:for-each>
                            </tbody>
                        </table>
                    </xsl:when>
                    <xsl:otherwise>
                        <div class="no-data">
                            <h2>✅ Отлично!</h2>
                            <p>Просроченных книг нет.</p>
                        </div>
                    </xsl:otherwise>
                </xsl:choose>
                
                <div style="margin-top: 30px; text-align: center; color: #6c757d; font-size: 14px;">
                    <p>Отчет сгенерирован автоматически. © Библиотечная система</p>
                </div>
            </div>
        </body>
        </html>
    </xsl:template>
    
</xsl:stylesheet>