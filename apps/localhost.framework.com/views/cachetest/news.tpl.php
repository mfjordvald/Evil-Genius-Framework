<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>Cache Test :: News</title>
	<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
</head>
<body>
<?php
if ( !empty($this->news) )
{
	foreach($this->news as $post)
	{
		?>
		<strong><?php echo $post['title']; ?></strong><br />
		<p><?php echo $post['content']; ?></p>
		<p><a href="/news/<?php echo $post['id']; ?>/">Read Comments</a></p>
		<hr />
		<?php
	}
}
?>
<strong>Post Some News</strong>
<p>
	<form action="/news/" method="post" enctype="application/x-www-form-urlencoded">
	<div style="margin-bottom: 3px;"><span style="float: left; width: 60px;">Title:</span><input type="text" size="25" name="title" /></div>
	<div style="margin-bottom: 3px;"><span style="float: left; width: 60px;">Content:</span><textarea name="content" cols="40" rows="5"></textarea></div>
	<div style="margin-bottom: 3px;"><span style="float: left; width: 60px;">&nbsp;</span><input type="submit" value="Post News" />
	</form>
</p>
</body>
</html>