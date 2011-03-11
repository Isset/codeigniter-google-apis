<atom:entry xmlns:atom="http://www.w3.org/2005/Atom" 
xmlns:wt="http://schemas.google.com/webmasters/tools/2007">

	<atom:id><?php echo $sitemap; ?></atom:id>

	<atom:category scheme='http://schemas.google.com/g/2005#kind'
	term='http://schemas.google.com/webmasters/tools/2007#sitemap-regular'/>
	
	<wt:sitemap-type><?php echo $sitemap_type; ?></wt:sitemap-type>

</atom:entry>
