<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet href="<?php echo $this->xsl ?>" type="text/xsl"?>
<!DOCTYPE rss PUBLIC "-//Netscape Communications//DTD RSS 0.91//EN" "http://my.netscape.com/publish/formats/rss-0.91.dtd">
<rss version="0.91" xmlns:content="http://purl.org/rss/1.0/modules/content/">
 <channel>
  <title><?php echo $this->channel_name ?></title>
  <description><?php echo $this->channel_desc ?></description>
  <link><?php echo $this->channel_official ?></link>
  <atom:link rel="self" type="application/rss+xml" title="<?php echo $this->channel_name ?>" href="<?php echo $this->channel_rss ?>" xmlns:atom="http://www.w3.org/2005/Atom"><?php echo $this->channel_rss ?></atom:link>
  <pubDate><?php echo $this->channel_updated ?></pubDate>
<?php foreach ($this->stories as $story): ?>
  <item>
   <title><?php echo $story['title'] ?></title>
   <description><?php echo $story['description'] ?></description>
   <content:encoded><![CDATA[<?php echo $story['body'] ?>
]]></content:encoded>
   <link><?php echo $story['storylink'] ?></link>
  </item>
<?php endforeach ?>
 </channel>
</rss>
