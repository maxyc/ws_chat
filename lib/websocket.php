<?php

/**
 * Simple implementation of HTML5 WebSocket server-side.
 *
 * PHP versions 5
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    WebSocket
 * @author     George Nava <georgenava@gmail.com>
 * @author     Vincenzo Ferrari <wilk3ert@gmail.com>
 * @copyright  2010-2011
 * @license    http://www.gnu.org/licenses/gpl.txt GNU GPLv3
 * @version    1.1.0
 * @link       http://code.google.com/p/phpwebsocket/
 */

/**
 * @usage $master = new WebSocket ("localhost", 12345);
 */
class WebSocket
{

    protected $master;
    protected $sockets = array();
    protected $users = array();
    // true to debug
    protected $debug = false;
    // frame mask
    protected $masks;
    // initial frames
    protected $initFrame;

    public function __construct($address, $port)
    {
	error_reporting(E_ALL);
	set_time_limit(0);
	ob_implicit_flush();

	// Socket creation
	$this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("socket_create() failed");

	socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1) or die("socket_option() failed");
	socket_bind($this->master, $address, $port) or die("socket_bind() failed");
	socket_listen($this->master, 20) or die("socket_listen() failed");

	$this->sockets[(string) $this->master] = $this->master;
	$this->say("\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");
	$this->say("\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");
	$this->say("Старт сервера   : " . date('d F Y H:i:s'));
	$this->say("Прослушивание   : {$address} {$port}");
	$this->say("Основной сокет  : {$this->master}\n");

	// Main loop
	while (true)
	{
	    $changed = $this->sockets;
	    socket_select($changed, $write = NULL, $except = NULL, NULL);

	    foreach ($changed as $socket)
	    {
		if ($socket == $this->master)
		{
		    $client = socket_accept($this->master);

		    if ($client < 0)
		    {
			$this->log("socket_accept() failed");
			continue;
		    } else
		    {
			// Connects the socket
			$this->connect($client);
		    }
		} else
		{
		    $bytes = @socket_recv($socket, $buffer, 2048, 0);
		    if ($bytes == 0)
		    {
			// On socket.close ();
			$this->disconnect($socket);
		    } else
		    {
			// Retrieve the user from his socket
			$user = $this->getuserbysocket($socket);

			if (!$user->handshake)
			{
			    $this->dohandshake($user, $buffer);
			} else
			{
			    $this->process($user, $this->decode($buffer));
			}
		    }
		}
	    }
	}
    }

    /**
     * @brief Echo incoming messages back to the client
     * @note Extend and modify this method to suit your needs
     * @param $user {User} : owner of the message
     * @param $msg {String} : the message to echo
     * @return void
     */
    protected function process($user, $msg)
    {
	$this->send($user->socket, $msg);
    }

    /**
     * @brief Send a message to a client
     * @param $client {Socket} : socket to send the message
     * @param $msg {String} : the message to send
     * @return void
     */
    protected function send($client, $msg)
    {
	$this->say("> {$msg}");
	$msg = $this->encode($msg);
	socket_write($client, $msg, strlen($msg));
    }

    /**
     * @brief Connect a new client (socket)
     * @param $socket {Socket} : socket to connect
     * @return void
     */
    protected function connect($socket)
    {
	$user = new User ();
	$user->id = uniqid();
	$user->socket = $socket;

	$resourceId = (string) $socket;

	$this->users[$resourceId] = $user;
	$this->sockets[$resourceId] = $socket;
	$this->say('Connected: '.$resourceId);

	$this->log("{$socket} подключен!");
	$this->log(date("d/n/Y ") . " в " . date("H:i:s T"));
    }

    /**
     * @brief Disconnect a client (socket)
     * @param $socket {Socket} : socket to disconnect
     * @return void
     */
    protected function disconnect($socket)
    {
	$resourceId = (string) $socket;

	unset($this->users[$resourceId], $this->sockets[$resourceId]);

	socket_close($socket);

	$this->log("{$socket} отключен!");
	
	$this->say('Disconnected: '.$resourceId);
    }

    /**
     * @brief Do the handshake between server and client
     * @param $user {User} : user to handshake
     * @param $buffer {String} : user's request
     * @return Boolean
     */
    protected function dohandshake($user, $buffer)
    {
	$this->log("\nRequesting handshake...");
	$this->log($buffer);

	list ($resource, $host, $connection, $version, $origin, $key, $upgrade) = $this->getheaders($buffer);

	$this->log("Handshaking...");
	$reply =
		"HTTP/1.1 101 Switching Protocols\r\n" .
		"Upgrade: {$upgrade}\r\n" .
		"Connection: {$connection}\r\n" .
		"Sec-WebSocket-Version: {$version}\r\n" .
		"Sec-WebSocket-Origin: {$origin}\r\n" .
		"Sec-WebSocket-Location: ws://{$host}{$resource}\r\n" .
		"Sec-WebSocket-Accept: " . $this->calcKey($key) . "\r\n" .
		"\r\n";

	// Closes the handshake
	socket_write($user->socket, $reply, strlen($reply));

	$user->handshake = true;
	$this->log($reply);
	$this->log("Done handshaking...");

	return true;
    }

    /**
     * @brief Calculate Sec-WebSocket-Accept
     * @note For more info look at: http://tools.ietf.org/html/draft-ietf-hybi-thewebsocketprotocol-17
     * @param $key {String} : key to calculate
     * @return Calculated key
     */
    protected function calcKey($key)
    {
	// Constant string as specified in the ietf-hybi-17 draft
	$key .= "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
	$key = sha1($key);
	$key = pack('H*', $key);
	$key = base64_encode($key);

	return $key;
    }

    /**
     * @brief Get the client request headers
     * @param $buffer {String} : buffer from which to draw the headers.
     * @return Array
     */
    protected function getheaders($buffer)
    {
	$resource = $host = $connection = $version = $origin = $key = $upgrade = null;

	preg_match('#GET (.*?) HTTP#', $buffer, $match) && $resource = $match[1];
	preg_match("#Host: (.*?)\r\n#", $buffer, $match) && $host = $match[1];
	preg_match("#Connection: (.*?)\r\n#", $buffer, $match) && $connection = $match[1];
	preg_match("#Sec-WebSocket-Version: (.*?)\r\n#", $buffer, $match) && $version = $match[1];
	preg_match("#(Sec-WebSocket-Origin|Origin): (.*?)\r\n#", $buffer, $match) && $origin = $match[1];
	preg_match("#Sec-WebSocket-Key: (.*?)\r\n#", $buffer, $match) && $key = $match[1];
	preg_match("#Upgrade: (.*?)\r\n#", $buffer, $match) && $upgrade = $match[1];

	return array($resource, $host, $connection, $version, $origin, $key, $upgrade);
    }

    /**
     * @brief Retrieve an user from his socket
     * @param $socket {Socket} : socket of the user to search
     * @return User or null
     */
    protected function getuserbysocket($socket)
    {
	$resourceId = (string) $socket;

	if (isset($this->users[$resourceId]))
	{
	    return $this->users[$resourceId];
	}

	return null;
    }

    /**
     * @brief Decode messages as specified in the ietf-hybi-17 draft
     * @param $msg {String} : message to decode
     * @return Message decoded
     */
    protected function decode($msg)
    {
	$len = $data = $decoded = $index = null;
	$len = $msg[1] & 127;

	if ($len === 126)
	{
	    $this->masks = substr($msg, 4, 4);
	    $data = substr($msg, 8);
	    $this->initFrame = substr($msg, 0, 4);
	} else if ($len === 127)
	{
	    $this->masks = substr($msg, 10, 4);
	    $data = substr($msg, 14);
	    $this->initFrame = substr($msg, 0, 10);
	} else
	{
	    $this->masks = substr($msg, 2, 4);
	    $data = substr($msg, 6);
	    $this->initFrame = substr($msg, 0, 2);
	}

	for ($index = 0; $index < strlen($data); $index++)
	{
	    $decoded .= $data[$index] ^ $this->masks[$index % 4];
	}

	return $decoded;
    }

    /**
     * @brief Encode messages
     * @param $msg {String} : message to encode
     * @return Message encoded
     */
    /* function encode ($msg, $masked = false) {
      $index = $encoded = null;

      for ($index = 0; $index < strlen ($msg); $index++) {
      $encoded .= $masked ? ($msg[$index] ^ $this->masks[$index % 4]) : $msg[$index];
      }

      if ($masked)
      {
      $encoded = $this->initFrame . $this->masks . $encoded;
      }

      return $encoded;
      } */

    // замиена функции на функцию hybi10Encode класса WebSocket\Connection
    protected function encode($payload, $type = 'text', $masked = false)
    {
	$frameHead = array();
	$frame = '';
	$payloadLength = strlen($payload);

	switch ($type)
	{
	    case 'text':
		// first byte indicates FIN, Text-Frame (10000001):
		$frameHead[0] = 129;
		break;

	    case 'close':
		// first byte indicates FIN, Close Frame(10001000):
		$frameHead[0] = 136;
		break;

	    case 'ping':
		// first byte indicates FIN, Ping frame (10001001):
		$frameHead[0] = 137;
		break;

	    case 'pong':
		// first byte indicates FIN, Pong frame (10001010):
		$frameHead[0] = 138;
		break;
	}

	// set mask and payload length (using 1, 3 or 9 bytes)
	if ($payloadLength > 65535)
	{
	    $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
	    $frameHead[1] = ($masked === true) ? 255 : 127;
	    for ($i = 0; $i < 8; $i++)
	    {
		$frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
	    }
	    // most significant bit MUST be 0 (close connection if frame too big)
	    if ($frameHead[2] > 127)
	    {
		$this->close(1004);
		return false;
	    }
	} elseif ($payloadLength > 125)
	{
	    $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
	    $frameHead[1] = ($masked === true) ? 254 : 126;
	    $frameHead[2] = bindec($payloadLengthBin[0]);
	    $frameHead[3] = bindec($payloadLengthBin[1]);
	} else
	{
	    $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
	}

	// convert frame-head to string:
	foreach (array_keys($frameHead) as $i)
	{
	    $frameHead[$i] = chr($frameHead[$i]);
	}
	if ($masked === true)
	{
	    // generate a random mask:
	    $mask = array();
	    for ($i = 0; $i < 4; $i++)
	    {
		$mask[$i] = chr(rand(0, 255));
	    }

	    $frameHead = array_merge($frameHead, $mask);
	}
	$frame = implode('', $frameHead);

	// append payload to frame:
	$framePayload = array();
	for ($i = 0; $i < $payloadLength; $i++)
	{
	    $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
	}

	return $frame;
    }

    /**
     * @brief Local echo messages
     * @param $msg {String} : message to echo
     * @return void
     */
    protected function say($msg = "")
    {
	echo "{$msg}\n";
    }

    /**
     * @brief Log function
     * @param $msg {String} : message to log
     * @return void
     */
    protected function log($msg = "")
    {
	if ($this->debug)
	{
	    echo "{$msg}\n";
	}
    }

}

class User
{

    var $id;
    var $socket;
    var $handshake;

}

?>
