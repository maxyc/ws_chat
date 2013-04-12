var ws;
var name, status, room;

$(function(){
    name = encodeURIComponent($('input[name=name]').val());
    status = encodeURIComponent($('input[name=status]').val());
    room = encodeURIComponent($('input[name=room]').val());
    
    
    var host = "ws://maxyc:8083";
    try{
	ws = new WebSocket(host);
	ws.onopen = start_chat;
	ws.onmessage = function(msg){
	    log("Received: "+msg.data);
	    receive_msg(msg.data);
	};
	ws.onclose   = function(msg){
	    log('disconnect');
	};
    }
    catch(ex){
	log(ex);
    }
    
    $('#form input[type=button]').click(function(e){
	f = $(this).parent();
	
	ws.send('msg|text=' + encodeURIComponent($('#form input[name=msg]').val()));
	$('#form input[name=msg]').val('');
    }); 
    
    $('#form input[name=msg]').keypress(function (e) {
	if (e.which == 13) {
	  $('#form input[type=button]').click();
	}
    });
    
    $('.contact').live('click', function(){
	 alert('123');
	
    });
});

function start_chat(){
    // отправляем информацию о себе
    ws.send('join|room='+room+'|name='+name+'|status='+status);
}

function receive_msg(msg){
    msg = JSON.parse(msg);
    
    if(msg.action)
	switch(msg.action){
	    default:console.log('Не знаю как обработать действие '+msg.action);break;
	    case 'contact_list': // обновление контакт листа
		update_contact_list(msg.data);
	    break;
	    case 'msg':
		update_message_list(msg.data);
	    break;
	}
    else
	console.log('Не знаю как обработать строку '+msg);
}

function update_contact_list(list){
    li = '';
    for(var i in list) {
	if (list.hasOwnProperty(i)) {
	    li += '<li class="contact" id="' + i + '" data-name="' + list[i].name + '">' + list[i].name + '<br/><small>' + list[i].status + '</small></li>';
	}
    }
    $('#contacts ul').html(li);
}

function update_message_list(data){
    $('#messages').append('<div class="message">' + 
	'<span class="time">' + data.time + '</span><span class="author">' + data.name + '</span><span class="msg">' + data.msg + '</span>'
    + '</div>');
}





function log(x){
    console.log(x);
}