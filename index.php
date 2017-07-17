<!DOCTYPE html>
<html>
	<head>
        <meta charset="utf-8" />
		<title>BBC News</title>
		<link rel="stylesheet" href="styles.css" type="text/css">
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
				  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
					  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-20878451-1', 'auto');
  ga('send', 'pageview');

</script>
    <script>
      <?php echo require('scripts.php') ?>
    </script>
	</head>
	<body id="js-body">
    <div class="news">
      <h1>Breaking News</h1>
      <p id="js-headline"></p>
    </div>
		<div class="clock">
			<h1 class="loading" id="js-clock">88:88:88</h1>
			<span id="js-unix"></span>
			<ul id="js-weather"></ul>
		</div>
	</body>
</html>
