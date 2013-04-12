<?php

include 'websocket.php';

class ws_webrtc extends WebSocket
{
    private $_delimeter = '|';
    private $_users = array(
	/*
	 * %room_id =>
	 *	$user->id =>
	 *	    name
	 *	    status
	 *  
	 */
	
    );
    private $_users_at_room;
    
    // обработка всех сообщений пользователя
    protected function process($user, $msg){
	$msg = explode($this->_delimeter, $msg);
	$action = $msg[0];unset($msg[0]);
	parse_str(join('&', $msg), $vars);
	
	switch($action){
	    default: // если комманда не определена, то ругаемся
		$this->send($user->socket, 'unknown command "'.  htmlspecialchars($action).'"');
	    break;
	    case 'join':
		
		$room_id = htmlspecialchars($vars['room']);
		
		$this->_users[$room_id][ $user->id ]   =	array(
		    'name'  =>	htmlspecialchars($vars['name']),
		    'status'=>	htmlspecialchars($vars['status']),
		);
		$this->_users_at_room[ $user->id ]['room']=$room_id;
		$this->_users_at_room[ $user->id ]['socket']=$user->socket;
		
		
		
		$msg = array(
		    'action'=>'msg',
		    'data'=> array('msg'=>'Добро пожаловать в чат!', 'time'=>date('H:i:s'), 'name'=>'Server')
		);
		$this->send($user->socket, json_encode($msg));
		
		
		
		
		// отправляем всем о том, что зашел новый человек
		$msg = array(
		    'action'=>'contact_list',
		    'data'=> $this->_users[$room_id]
		);
		$this->mass_send_by_room($room_id, $msg);
	    break;
	    case 'contact_list':
		
		$answer = array(
		    'action'=>'contact_list',
		    'data'=> $this->_users[htmlspecialchars($vars['room'])]
		);
		
		$this->send($user->socket, json_encode($answer));
	    break;
	    case 'msg':
		// отправляем всем о том, что зашел новый человек
		
		$room_id = $this->_users_at_room[ $user->id ]['room'];
		$name = $this->_users[$room_id][ $user->id ]['name'];
		$msg = array(
		    'action'=>'msg',
		    'data'=>array('msg'=>  htmlspecialchars($vars['text']), 'time'=>date('H:i:s'), 'name'=>$name)
		);
		$this->mass_send_by_room($room_id, $msg);
	    break;
	}
	//$this->send($user->socket, $msg);
    }
    
    private function mass_send_by_room($room_id, $msg){
	foreach($this->_users[ $room_id ] as $id=>$v){
	    $this->send(
		$this->_users_at_room[ $id ]['socket'],
		json_encode($msg)
	    );
	}
    }
    
    protected function disconnect($socket)
    {
	$resourceId = (string) $socket;
	$user_id = $this->users[$resourceId]->id;
	$room_id = $this->_users_at_room[ $user_id ]['room'];
	
	unset(
		$this->_users[ $room_id ][ $user_id ], 
		$this->_users_at_room[ $user_id ]
		
	);
	
	// отправляем всем о том, что ушел человек
	$msg = array(
	    'action'=>'contact_list',
	    'data'=> $this->_users[$room_id]
	);
	$this->mass_send_by_room($room_id, $msg);
	
	parent::disconnect($socket);
    }
}