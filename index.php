<?php header('Content-Type: text/html; charset=UTF-8');?>
<html>
    <head>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script src="assets/main.js"></script>
	<link type="text/css" rel="stylesheet" href="assets/style.css">
    </head>
    <body>
	<h1>WebSocket чат</h1>
	<table id="chat">
	    <tr>
		<td id="messages" valign="top"></td>
		<td id="contacts" valign="top">
		    <h3>Список контактов</h3>
		    <ul></ul>
		</td>
	    </tr>
	    <tr>
		<td id="form" colspan="2" valign="middle">
			<input type="hidden" name="name" value="<?php echo @$_GET['name']?>" /> 
			<input type="hidden" name="status" value="<?php echo @$_GET['status']?>" /> 
			<input type="hidden" name="room" value="<?php echo @$_GET['room']?>" />
			
			<input type="text" name="msg" style="width:400px;"/>
			<input type="button" value="отправить" />
		</td>
	    </tr>
	</table>
    </body>
</html>