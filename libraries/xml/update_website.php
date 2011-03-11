<atom:entry xmlns:atom="http://www.w3.org/2005/Atom" 
xmlns:wt="http://schemas.google.com/webmasters/tools/2007">

	<atom:id><?php echo $website_id; ?></atom:id>

	<atom:category scheme='http://schemas.google.com/g/2005#kind' 
	term='http://schemas.google.com/webmasters/tools/2007#site-info'/>

	<wt:<?php echo $option_key; ?>><?php echo $option_value; ?></wt:<?php echo $option_key; ?>>

</atom:entry>
