<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">
  <xsl:output method="xml" encoding="utf-8" indent="no" omit-xml-declaration="yes"/>

  <xsl:template name="FormatDate">
    <xsl:param name="DateTime" />
    <xsl:value-of select="php:functionString('hg_reader_XSLDateTimeXfer','fullDate',$DateTime)" />     
  </xsl:template>
  
  <xsl:template name="FormatICS">
    <xsl:param name="DateTime" />
    <xsl:variable name="mo" select="substring($DateTime,6,2)" />
    <xsl:variable name="day-temp" select="substring($DateTime,9)" />
    <xsl:variable name="day" select="substring-before($day-temp,' ')" />
    <xsl:variable name="year" select="substring($DateTime,1,4)" />
    <xsl:variable name="time" select="substring-after($day-temp, ' ')" />
    <xsl:variable name="hour" select="substring($time,1,2)" />
    <xsl:variable name="min" select="substring($time,4,2)" />
    <xsl:variable name="sec" select="substring($time,7,2)" />
    <xsl:value-of select="$year" />
    <xsl:value-of select="$mo" />
    <xsl:value-of select="$day" />
    <xsl:text>T</xsl:text>
    <xsl:value-of select="$hour" />
    <xsl:value-of select="$min" />
    <xsl:value-of select="$sec" />    
  </xsl:template>  
  
  <xsl:template name="FormatTime">
    <xsl:param name="DateTime" />
    <xsl:value-of select="php:functionString('hg_reader_XSLDateTimeXfer','fullTime',$DateTime)" />
  </xsl:template>  
  
  <xsl:template name="FormatMonth">
    <xsl:param name="mon" />
    <xsl:param name="format" />
    <xsl:if test="$format = 'longNumber'">
      <xsl:value-of select="$mon" />
    </xsl:if>
    <xsl:if test="$format = 'shortNumber'">
      <xsl:if test="starts-with($mon, '0')">
        <xsl:value-of select="substring-after($mon,'0')" />
      </xsl:if>
      <xsl:if test="not(starts-with($mon, '0'))">
       <xsl:value-of select="$mon" />
      </xsl:if>
    </xsl:if>
    <xsl:if test="$format = 'shortMonth'">
      <xsl:value-of select="php:functionString('hg_reader_XSLDateTimeXfer','shortMonth',$mon)" />
    </xsl:if>
    <xsl:if test="$format = 'longMonth'">
      <xsl:value-of select="php:functionString('hg_reader_XSLDateTimeXfer','longMonth',$mon)" />
    </xsl:if>
  </xsl:template>
  
  <xsl:template name="FormatDay">
    <xsl:param name="day" />
    <xsl:param name="format" />
    <xsl:if test="$format = 'longNumber'">
      <xsl:value-of select="$day" />
    </xsl:if>
    <xsl:if test="$format = 'shortNumber'">
      <xsl:if test="starts-with($day, '0')">
        <xsl:value-of select="substring-after($day,'0')" />
      </xsl:if>
      <xsl:if test="not(starts-with($day, '0'))">
       <xsl:value-of select="$day" />
      </xsl:if>
    </xsl:if>
    <xsl:if test="$format = 'shortDay'">
      <xsl:value-of select="php:functionString('hg_reader_XSLDateTimeXfer','shortDay',$day)" />
    </xsl:if>
    <xsl:if test="$format = 'longDay'">
      <xsl:value-of select="php:functionString('hg_reader_XSLDateTimeXfer','longDay',$day)" />
    </xsl:if>
  </xsl:template>
  
</xsl:stylesheet>