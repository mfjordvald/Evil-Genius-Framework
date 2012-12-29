<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>Cache Test :: News</title>
	<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
</head>
<body>
<strong><?php echo $this->news_post['title']; ?></strong><br />
<p><?php echo $this->news_post['content']; ?></p>
<p><a href="/news/">Returns to news.</a></p>
<hr />
<?php
if ( !empty($this->comments) )
{
	foreach($this->comments as $comment)
	{
		?>
		<p><?php echo $comment['content']; ?></p>
		<hr />
		<?php
	}
}
?>
<strong>Post A Comment</strong>
<p>
	<form action="/news/comment/" method="post" enctype="application/x-www-form-urlencoded">
	<input type="hidden" value="<?php echo $this->news_post['id']; ?>" name="id" />
	<div style="margin-bottom: 3px;"><span style="float: left; width: 70px;">Comment:</span><textarea name="content" cols="40" rows="5"></textarea></div>
	<div style="margin-bottom: 3px;"><span style="float: left; width: 70px;">&nbsp;</span><input type="submit" value="Post Comment" />
	</form>
</p>
</body>
</html>