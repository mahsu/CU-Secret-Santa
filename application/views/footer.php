<html>
<head>
<!--<script src="/application/views/main.css" type="text/css"></script>-->
</head>
<body>
<div style="margin-top:10px;float:right;"><span class="footer"><?php echo (isset($app_disp) && $app_disp == 1) ? ('<a class="footer">clear all data</a> | '.($version).' | ') : ($version.' | ');?> rendered in <?php echo $this->benchmark->elapsed_time();?> secs</span>
</div>
</body>
</html>