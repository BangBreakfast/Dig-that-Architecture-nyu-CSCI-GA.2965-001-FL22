<?php
	// check command line arguments
	if(($argc < 5) || !is_numeric($argv[1]) || !is_numeric($argv[2]) || !is_numeric($argv[3]) || !is_numeric($argv[4])) {
		echo("[ERROR] Please provide grid number, number of phases and the length of the path\n");
		echo("[ERROR] Example command: php server.php 2222 20 5 35\n");
		// echo("[LOG] Starting server at localhost:$argv[1] with $argv[2]*$argv[2] grid, $argv[3] phases and maximum $argv[4] length of the path\n");
		exit(-1);
	}

	// start server
	echo("[LOG] Starting server at localhost:$argv[1] with $argv[2]*$argv[2] grid, $argv[3] phases and maximum $argv[4] length of the path\n");

	// open, bind, and begin listening on socket
	$socket = socket_create(AF_INET, SOCK_STREAM, 0);
	socket_bind($socket, 'localhost', $argv[1]);
	socket_listen($socket, 3);

	// initialize game
	$grid_num = $argv[2];
	$number_of_phases = $argv[3];
	$length_of_path = $argv[4];

	$connections;
	$observed = false;
	if($argc == 6 && $argv[5] == "-o") {
		// log status
		echo("[LOG] Waiting for Observer\n");

		// blocking call waiting for connection
		$connections[0] = socket_accept($socket);

		// extra communication to identify client (see comment below for more details on websocket exchange)
		$identification = socket_read($connections[0], 5000);
		
		if(strpos($identification, "Sec-WebSocket-Key:") !== false) {
			preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $identification, $matches);
			$key = base64_encode(pack('H*', sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
			$headers = "HTTP/1.1 101 Switching Protocols\r\n";
			$headers .= "Upgrade: websocket\r\n";
			$headers .= "Connection: Upgrade\r\n";
			$headers .= "Sec-WebSocket-Version: 13\r\n";
			$headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";
			socket_write($connections[0], $headers, strlen($headers));
			$observed = true;
		}
	}

	// send initial data to observer
	if($observed) {
		send_message($connections[0], "info $grid_num $number_of_phases\n", true);
	}

	// wait for two connections to continue
	$is_websocket;
	$name;
	for($i = 1; $i <= 2; $i++) {
		// log status
		echo("[LOG] Waiting for Player $i\n");

		// blocking call waiting for connection
		$connections[$i] = socket_accept($socket);

		// do extra communication to identify client
		// if a websocket is being used we need to do a handshake
		// all other clients can send whatever they want as long as it doesn't contain "Sec-WebSocket-Key:"
		// identification code based on https://medium.com/@cn007b/super-simple-php-websocket-example-ea2cd5893575
		$identification = socket_read($connections[$i], 5000);
		if(strpos($identification, "Sec-WebSocket-Key:") !== false) {
			preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $identification, $matches);
			$key = base64_encode(pack('H*', sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
			$headers = "HTTP/1.1 101 Switching Protocols\r\n";
			$headers .= "Upgrade: websocket\r\n";
			$headers .= "Connection: Upgrade\r\n";
			$headers .= "Sec-WebSocket-Version: 13\r\n";
			$headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";
			socket_write($connections[$i], $headers, strlen($headers));
			$is_websocket[$i] = true;
			$name[$i] = "Webclient $i";

			// log connection
			echo("[LOG] Player $i connected via websocket\n\n");
		} else {
			$is_websocket[$i] = false;

			$name[$i] = str_replace(array(" ", "\r", "\n"), '', $identification);


			// log connection
			echo("[LOG] Player $i connected via TCP\n\n");
		}
	}

	// send a message to a client over a socket or websocket
	function send_message($client, $message, $is_web) {
		if($is_web) {
			socket_write($client, chr(129) . chr(strlen($message)) . $message);
		} else {
			socket_send($client, $message, strlen($message), 0);
		}
	}

	// compliant masking and decoding based on https://gist.github.com/dg/6205452
	function web_decode($frame) {
		$decoded_frame = "";
		for ($i = 6; $i < strlen($frame); $i++) {
			$decoded_frame .= $frame[$i] ^ $frame[2 + ($i - 2) % 4];
		}
		return $decoded_frame;
	}

	function illegal_end($illegal_id,$connections,$observed,$socket) {
		// send messages to players and close socket
		if($illegal_id == 1) {
			echo($connections[1]);
			send_message($connections[1], "-1\n", $is_websocket[1]);
			send_message($connections[2], "-2\n", $is_websocket[2]);
		} else {
			send_message($connections[1], "-2\n", $is_websocket[1]);
			send_message($connections[2], "-1\n", $is_websocket[2]);
		}
		
		if($observed) {
			send_message($connections[0], "terminated infty", true);
		}
			

		socket_close($socket);

		// log results
		echo("[LOG] invalid input or timed out\n");
		if($illegal_id == 1) {
			echo("[INFO] PLAYER detector WINS!\n\n");
		} else {
			echo("[INFO] PLAYER tunneler WINS!\n\n");
		}
		// exit program
		exit;
	}


	// send initial data to both players
	send_message($connections[1], "T $grid_num $number_of_phases $length_of_path\n", $is_websocket[1]);
	// tunnel start here
	$time_start = hrtime(true);
	send_message($connections[2], "D $grid_num $number_of_phases $length_of_path\n", $is_websocket[2]);

	if($observed) {
		send_message($connections[0], "name $name[1] $name[2] \n", true);
	}

	// both players now have 2 minutes each remaining (120 seconds)
	$time_remaining[1] = 120 * 1000000000;
	$time_remaining[2] = 120 * 1000000000;
	
	
	/* 
		tunneler turn 
	*/


	// play game
	echo("[LOG] Waiting for tunneler to send a command\n");
	socket_set_option($connections[1], SOL_SOCKET, SO_RCVTIMEO,
											array('sec' => intval($time_remaining[1] / 1000000000),
														'usec'=> 0));
	$command = socket_read($connections[1], 20480);
	//check if the command 
	if(!$command) {
		illegal_end(1,$connections,$observed,$socket);
	}

	// if coming from a websocket, decode recieved packet
	if($is_websocket[1]) {
		$command = web_decode($command);
	}

	// split and interpret command
	// echo($command);
	$command = str_replace(array("\r", "\n"), '', $command);
	$command_parts = explode(" ", $command);
	$path;
	if($command_parts[0] == "digtunnel") {
		$time_remaining[1] -= hrtime(true) - $time_start;
		// send message
		if($time_remaining[2] < 0) {
			echo("[Game End] The tunneler timeout\n");
			echo("[INFO] PLAYER detector WINS!\n\n");
			illegal_end(1,$connections,$observed,$socket);
		}
		echo("[LOG] -- Digging STATE --\n");
		//get the tunnel
		if (count($command_parts)-1  > $argv[4]+1 ) {
			echo("[ERROR] The tunnel is much than $argv[4] blocks");
			illegal_end(1,$connections,$observed,$socket);
		}
		//check if the tunnel is valid
		for ($it = 1; $it < count($command_parts); $it++) {
			echo("[INFO] The tunnel vertex $it is: $command_parts[$it] \n");
			$axis = explode(",", $command_parts[$it]);
			$axis_x = (int) $axis[0];
			$axis_y = (int) $axis[1];
			if($it > $length_of_path+1) {
				echo("[ERROR] The tunnel vertex number is out of range\n");
				illegal_end(1,$connections,$observed,$socket);
			}
			if($axis_x < 1 || $axis_x > $grid_num) {
				echo("[ERROR] The tunnel vertex $it'x is out of range\n");
				illegal_end(1,$connections,$observed,$socket);
			} 
			if($axis_y < 1 || $axis_y > $grid_num) {
				echo("[ERROR] The tunnel vertex $it'y is out of range\n");
				illegal_end(1,$connections,$observed,$socket);
			} 
			if($it == 1 && $axis_y!=1) {
				echo("[ERROR] The tunnel start point's y-axis must be 1\n");
				illegal_end(1,$connections,$observed,$socket);
			}
			if($it == count($command_parts)-1 && $axis_y!=$grid_num) {
				echo("[ERROR] The tunnel end point's y-axis must be $grid_num\n");
				illegal_end(1,$connections,$observed,$socket);
			}
			if($it != 1) {
				$dif = abs($path[$it-1][0]-$axis_x)+abs($path[$it-1][1]-$axis_y);
				if($dif > 1) {
					echo("[ERROR] The path from $it-1 to $it is not valid\n");
					illegal_end(1,$connections,$observed,$socket);
				}
			}
			for( $j = 1; $j < $it; $j++) {
				if($path[$j][0] == $axis_x && $path[$j][1] == $axis_y) {
					echo("[ERROR] The vertex $it is same as vertex $j\n");
					illegal_end(1,$connections,$observed,$socket);
				}
			}
			$path[$it] = array($axis_x,$axis_y);
		} 
		// log results
		// send update to observer
		if($observed) {
			send_message($connections[0], $command, true);
			socket_read($connections[0],5000);
		}

		echo("[INFO] Move time remaining: $time_remaining[1] microseconds\n\n");
	} else {
		illegal_end(1,$connections,$observed,$socket);
	}

	$score = 0;
	//detector turn
	echo("\n\n[LOG] -- Detecting STATE --\n");

	$time_start = hrtime(true);
	send_message($connections[2], "next_phase\n", $is_websocket[2]);


	while($number_of_phases > 0) {

		echo("[INFO] Remain phases number is $number_of_phases\n");
		$number_of_phases=$number_of_phases-1;
		$command = socket_read($connections[2], 20480);

		//check if the command 
		if(!$command) {
			illegal_end(2,$connections,$observed,$socket);
		}

		// if coming from a websocket, decode recieved packet
		if($is_websocket[1]) {
			$command = web_decode($command);
		}

		// split and interpret command
		// echo($command);
		$command = str_replace(array("\r", "\n"), '', $command);
		$command_parts = explode(" ", $command);

		$ret_path=array();
		if($command_parts[0] == "end") {
			$time_remaining[2] -= hrtime(true) - $time_start;
			if($time_remaining[2] < 0) {
				echo("[Game End] The detector timeout\n");
				echo("[INFO] PLAYER tunneler WINS!\n\n");
				illegal_end(2,$connections,$observed,$socket);
			}
			echo("[INFO] The detector terminate the probe early");
			echo("[INFO] Move time remaining: $time_remaining[2] microseconds\n\n");
			break;
		} else if ($command_parts[0] == "detect"){
			$time_remaining[2] -= hrtime(true) - $time_start;
			if($time_remaining[2] < 0) {
				echo("[Game End] The detector timeout\n");
				echo("[INFO] PLAYER tunneler WINS!\n\n");
				illegal_end(2,$connections,$observed,$socket);
			}
			for ($it = 1; $it < count($command_parts); $it++) {
				echo("[INFO] The detector vertex $it is: $command_parts[$it] \n");
				$axis = explode(",", $command_parts[$it]);
				$axis_x = (int) $axis[0];
				$axis_y = (int) $axis[1];
				
				if($axis_x < 1 || $axis_x > $grid_num) {
					echo("[ERROR] The detect vertex $it'x is out of range\n");
					illegal_end(2,$connections,$observed,$socket);
				} 
				if($axis_y < 1 || $axis_y > $grid_num) {
					echo("[ERROR] The detect vertex $it'y is out of range\n");
					illegal_end(2,$connections,$observed,$socket);
				} 
				for($j = 1; $j <= count($path); $j++) {
					if($path[$j][0] ==  $axis_x && $path[$j][1] ==  $axis_y) {
						if($j!=1) {
							$tmp_edge = array($path[$j-1],$path[$j]);
							$tmp_edge_reverse = array($path[$j],$path[$j-1]);
							if(!in_array($tmp_edge,$ret_path) && !in_array($tmp_edge_reverse,$ret_path)) {
								$rand_num = mt_rand()%2;
								if($rand_num==1) {
									array_push($ret_path,$tmp_edge);
								} else {
									array_push($ret_path,$tmp_edge_reverse);
								}
								
							}
						}
						if($j!=count($path)) {
							$tmp_edge = array($path[$j],$path[$j+1]);
							$tmp_edge_reverse = array($path[$j+1],$path[$j]);
							if(!in_array($tmp_edge,$ret_path) && !in_array($tmp_edge_reverse,$ret_path)) {
								$rand_num = mt_rand()%2;
								if($rand_num==1) {
									array_push($ret_path,$tmp_edge);
								} else {
									array_push($ret_path,$tmp_edge_reverse);
								}
							}
						}
					}
				}
			} 
			echo("[INFO] Move time remaining: $time_remaining[2] microseconds\n\n");
			$score +=  count($command_parts) - 1;
		}
		//update observer
		if($observed) {
			send_message($connections[0], $command, true);
			socket_read($connections[0],5000);
		}
		//Disrupt the order
		shuffle($ret_path);
		//Send back the path
		$path_str = "path";
		//format <vertex[0].x> <vertex[0].y> <vertex[1].x> <vertex[1].y>
		for($it = 0;$it<count($ret_path);$it++) {
			$path_str = $path_str . " " . $ret_path[$it][0][0] . " " . $ret_path[$it][0][1];
			$path_str = $path_str . " " . $ret_path[$it][1][0] . " " . $ret_path[$it][1][1];
			$x0 = $ret_path[$it][0][0];
			$y0 = $ret_path[$it][0][1];
			$x1 = $ret_path[$it][1][0];
			$y1 = $ret_path[$it][1][1];
			echo("[INFO] Checked path is: ($x0,$y0) <-> ($x1,$y1)\n");
		}
	
		//send back the path to observer
		$time_start = hrtime(true);
		send_message($connections[2],"$path_str\n" , $is_websocket[2]);	
		if($number_of_phases==0) {
			break;
		}
	}

	sleep(1);
	echo("start guessing");
	send_message($connections[2],"guess\n", $is_websocket[2]);	

	$command = socket_read($connections[2], 20480);

	//check if the command 
	if(!$command) {
		illegal_end(2,$connections,$observed,$socket);
	}

	// if coming from a websocket, decode recieved packet
	if($is_websocket[1]) {
		$command = web_decode($command);
	}

	// split and interpret command
	// echo($command);
	$command = str_replace(array("\r", "\n"), '', $command);
	$command_parts = explode(" ", $command);
	$flag = true;
	if($command_parts[0] == "guess") {
		$time_remaining[2] -= hrtime(true) - $time_start;
		if($time_remaining[2] < 0) {
			echo("[Game End] The detector timeout\n");
			echo("[INFO] PLAYER tunneler WINS!\n\n");
			illegal_end(2,$connections,$observed,$socket);
		}

		for ($it = 1; $it < count($command_parts); $it++) {
			$axis = explode(",", $command_parts[$it]);
			echo("[INFO] The guess vertex $it is: $command_parts[$it] \n");
			$axis_x = (int) $axis[0];
			$axis_y = (int) $axis[1];
			
			if($it > $length_of_path+1) {
				echo("[ERROR] The detector vertex number is out of range\n");
				illegal_end(1,$connections,$observed,$socket);
			}
			if($axis_x < 1 || $axis_x > $grid_num) {
				echo("[ERROR] The detector vertex $it'x is out of range\n");
				illegal_end(2,$connections,$observed,$socket);
			} 
			if($axis_y < 1 || $axis_y > $grid_num) {
				echo("[ERROR] The detector vertex $it'y is out of range\n");
				illegal_end(2,$connections,$observed,$socket);
			} 
			if($it == 1 && $axis_y!=1) {
				echo("[ERROR] The detector start point's y-axis must be 1\n");
				illegal_end(2,$connections,$observed,$socket);
			}
			if($it == count($command_parts)-1 && $axis_y!=$grid_num) {
				echo("[ERROR] The detector end point's y-axis must be $grid_num\n");
				illegal_end(2,$connections,$observed,$socket);
			}
			if($it != 1) {
				$dif = abs($path[$it-1][0]-$axis_x)+abs($path[$it-1][1]-$axis_y);
				if($dif > 1) {
					echo("[ERROR] The path from $it-1 to $it is not valid,which is $dif\n");
					illegal_end(2,$connections,$observed,$socket);
				}
			}
			for( $j = 1; $j < $it; $j++) {
				if($path[$j][0] == $axis_x && $path[$j][1] == $axis_y) {
					echo("[ERROR] The vertex $it is same as vertex $j\n");
					illegal_end(2,$connections,$observed,$socket);
				}
			}
			if($path[$it] != array($axis_x,$axis_y)){
				$flag = false;
				break;
			}
		}
		if(count($command_parts)-1 != count($path)) {
			$flag = false;
		}
	} else {
		illegal_end(2,$connections,$observed,$socket);
	}

	//update observer
	if($observed) {
		send_message($connections[0], $command, true);
		socket_read($connections[0],5000);
	}

	$detector_name = $name[2];
	// print result
	if($flag == true) {
		send_message($connections[1], "$score\n", $is_websocket[1]);
		send_message($connections[2], "$score\n", $is_websocket[2]);
		echo("[INFO] The detector $detector_name got a score of $score\n\n");
		//update observer
		if($observed) {
			send_message($connections[0], "terminated $score\n", true);
		}
	} else {
		send_message($connections[1], "-3\n", $is_websocket[1]);
		send_message($connections[2], "-3\n", $is_websocket[2]);
		echo("[INFO] the detector $detector_name fail to get the path.\n\n");
		echo("[INFO] the detector $detector_name Got a score of infty.\n\n");
		//update observer
		if($observed) {
			send_message($connections[0], "terminated infty\n", true);
			socket_read($connections[0],5000);
		}
	}


	// close socket
	socket_close($socket);
	echo("socket_close\n\n");
?>