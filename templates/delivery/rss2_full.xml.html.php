<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet href="<?php echo $this->xsl ?>" type="text/xsl"?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
 <channel>
  <title><?php echo $this->channel_name ?></title>
  <link><?php echo $this->channel_official ?></link>
  <atom:link rel="self" type="application/rss+xml" title="<?php echo $this->channel_name ?>" href="<?php echo $this->channel_rss2 ?>" xmlns:atom="http://www.w3.org/2005/Atom"><?php echo $this->channel_rss ?></atom:link>
  <description><?php echo $this->channel_desc ?></description>
  <pubDate><?php echo $this->channel_updated ?></pubDate>
  <generator><?php echo $this->jonah ?></generator>
<?php foreach ($this->stories as $story): ?>
  <item>
     <title><?php echo $story['title'] ?></title>
     <link><?php echo $story['storylink'] ?></link>
     <description><?php echo $story['description'] ?></description>
     <author><?php echo $story['author'] ?></author>
     <content:encoded><![CDATA[<?php echo $story['body'] ?>
]]></content:encoded>
     <pubDate><?php echo $story['published'] ?></pubDate>
     <guid isPermaLink="true"><?php echo $story['permalink'] ?></guid>
  </item>
<?php endforeach ?>
 </channel>
</rss>
