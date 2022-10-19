
#include <iostream>
#include <string>
#include <sstream>
#include <vector>
// these includes may need to be modified depending on your system
#include <sys/socket.h>
#include <arpa/inet.h>
#include <unistd.h>

// change this to whatever you want
std::string bot_name = "C++ Client";

// here you can define custom solver logic
struct Bot {
	int grid_num;
	int number_of_phases;
	int length_of_path;

	Bot(int _grid_num, int _number_of_phases,int _length_of_path) {
		grid_num =  _grid_num;
		number_of_phases = _number_of_phases;
		length_of_path = _length_of_path;
	}

	std::vector<std::pair<int,int> > dig_tunnel() {
		//TODO: Dig the tunnel
		//the pair<int,int> is the passing tunnel vertex
		//the first element [x,y] should be like [x,1]
		//the last element [x,y] should be like [x,grid_num]

		//test codes stared  here
		std::vector<std::pair<int,int> > res;
		for(int i=1;i<=grid_num;++i) {
			res.push_back(std::make_pair(1,i));
		}
		return res;
	}

	std::vector<std::pair<int,int> > select_detector() {
		//TODO: Select detector
		//the pair<int,int> is the detector vertex;
		std::vector<std::pair<int,int> > res;

		return res;
	}

	bool end_detect() {
		//TODO: This function will call before select detector.
		//Return a non-zero number if the detector believes that the information is enough
		//The server will jump into the guess mode

		return 0;
	}

	void handle_edge_set(std::vector<std::pair<std::pair<int,int>,std::pair<int,int> > > edge_set) {
		//TODO: This function will call after receive the edge set
		//Do what you want after receive the edge set
		//Note: the order is shuffled
		// 		the order of the vertices of each edge is also shuffled
		//		Two different probes may appear [1,1], [1,2] or [1,2][1,1]
		//		But they are the same edge
		return;
	}
	
	std::vector<std::pair<int,int> > guess_path() {
		//TODO: guess the path for the detector
		//the pair<int,int> is the passing tunnel vertex
		//the first element [x,y] should be like [x,1]
		//the last element [x,y] should be like [x,grid_num]
		//test codes stared  here
		std::vector<std::pair<int,int> > res;
		for(int i=1;i<=grid_num;++i) {
			res.push_back(std::make_pair(1,i));
		}

		return res;
	}

};

// Everything below here is game logic and socket handling

int socket_id;
struct sockaddr_in server_address;

int send_tunnel(std::vector<std::pair<int,int> > tunnel) {
	// send getstate request
	std::string send_message = "digtunnel";
	for(int i=0; i < tunnel.size(); ++i) {
		send_message += " " + std::to_string(tunnel[i].first) + "," + std::to_string(tunnel[i].second);
	}
	send(socket_id, send_message.c_str(), send_message.size(), 0);

	// read response
	char receive_message[1024]={};
	read(socket_id, receive_message, 1024);
	return std::stoi(receive_message);
}

int send_detector(Bot b) {
	char receive_message[1024]={};
	read(socket_id, receive_message, 1024);
	std::stringstream ss(receive_message);
	std::string input;
	ss >> input;
	if(input == "next_phase") {
		if(b.end_detect()){
			std::string send_message = "end";
			send(socket_id, send_message.c_str(), send_message.size(), 0);
			return 1;
		} else {
			std::string send_message = "detect";
			std::vector<std::pair<int,int> > detectors = b.select_detector();
			for(int i=0; i < detectors.size(); ++i) {
				send_message += " " + std::to_string(detectors[i].first) + "," + std::to_string(detectors[i].second);
			}
			send(socket_id, send_message.c_str(), send_message.size(), 0);
		}
	} else if(input == "path") {
		std::vector<std::pair<std::pair<int,int>,std::pair<int,int> > > edge_set;
		while(ss.peek()!='\n' && ss.peek()!=EOF) {
			std::pair<std::pair<int,int>,std::pair<int,int> > edge;
			ss>>edge.first.first>>edge.first.second>>edge.second.first>>edge.second.second;
			edge_set.push_back(edge);
		}
		b.handle_edge_set(edge_set);
		if(b.end_detect()){
			std::string send_message = "end";
			send(socket_id, send_message.c_str(), send_message.size(), 0);
			return 1;
		} else {
			std::string send_message = "detect";
			std::vector<std::pair<int,int> > detectors = b.select_detector();
			for(int i=0; i < detectors.size(); ++i) {
				send_message += " " + std::to_string(detectors[i].first) + "," + std::to_string(detectors[i].second);
			}
			send(socket_id, send_message.c_str(), send_message.size(), 0);
		}
	} else {
		std::cout << "If you did not stop the program yourself, please emal lg3405 and cc dw2691\n";
		exit(-1);
	}
	return 0;
}

int guess_path(Bot b) {
	// send getstate request
	std::string send_message = "guess";
	std::vector<std::pair<int,int> > tunnel = b.guess_path();

	for(int i=0; i < tunnel.size(); ++i) {
		send_message += " " + std::to_string(tunnel[i].first) + "," + std::to_string(tunnel[i].second);
	}
	send(socket_id, send_message.c_str(), send_message.size(), 0);
	// read response
	char receive_message[1024]={};
	read(socket_id, receive_message, 1024);
	return std::stoi(receive_message);
}

void send_move(int move) {
	std::string s = "sendmove " + std::to_string(move);
	send(socket_id, s.c_str(), s.length(), 0);
}

// this function contains the main game loop
void play_game() {
	int grid_num;
	int number_of_phases;
	int length_of_path;
	char player_id;
	int res=0;

	// read initial data
	char message[1024]={};
	read(socket_id, message, 1024);
	std::stringstream ss(message);
	ss >> player_id >> grid_num >> number_of_phases >> length_of_path;

	// create bot
	Bot b(grid_num, number_of_phases, number_of_phases);

	// the first player can make a move without getting state first
	// the second needs to make an initial request
	if(player_id == 'T') {
		std::vector<std::pair<int,int> > tunnel =  b.dig_tunnel();
		res = send_tunnel(tunnel);
	} else {
		while(number_of_phases>0) {
			number_of_phases--;
			int end_flag = send_detector(b);
			if(end_flag == 1) {
				break;
			}
		}
		if(number_of_phases<=0) {
			// read the last edge_set
			char receive_message[1024]={};
			read(socket_id, receive_message, 1024);
			std::stringstream ss(receive_message);
			std::string input;
			ss >> input;
			if(input == "path") {
			std::vector<std::pair<std::pair<int,int>,std::pair<int,int> > > edge_set;
				while(ss.peek()!='\n' && ss.peek()!=EOF) {
					std::pair<std::pair<int,int>,std::pair<int,int> > edge;
					ss>>edge.first.first>>edge.first.second>>edge.second.first>>edge.second.second;
					edge_set.push_back(edge);
				}
				b.handle_edge_set(edge_set);
			} else {
				std::cout << "If you did not stop the program yourself, please emal lg3405 and cc dw2691\n";
				exit(-1);
			}
		}
		//guess the number
		char receive_message[1024]={};
		read(socket_id, receive_message, 1024);
		std::stringstream ss(receive_message);
		std::string input;
		ss >> input;
		if(input == "guess") {
			res = guess_path(b);
		} else {
			std::cout << "If you did not stop the program yourself, please emal lg3405 and cc dw2691\n";
			exit(-1);
		}
	}

	if(res == -2) {
		std::cout << "The opponent do an invalid operation." <<std::endl;
	} else if(res == -1) {
		std::cout << "You do an invalid operation." <<std::endl;
	} else if(res == -3) {
		std::cout << "The detector failed to detect the tunnel." <<std::endl;
	}else {
		std::cout << "The detector probe " << res << " times." <<std::endl;
	}
	
}

// this function handles the socket connection process
int socket_connect(int port) {
	// create socket
	socket_id = socket(AF_INET, SOCK_STREAM, 0);
	if(socket_id < 0) {
		std::cout << "Error creating socket\n";
		exit(-1);
	}
	// set additional required connection info
	server_address.sin_family = AF_INET;
  	server_address.sin_port = htons(port);
	// convert ip address to correct form
	inet_pton(AF_INET, "localhost", &server_address.sin_addr);
	// attempt connection
	if(connect(socket_id, (struct sockaddr*) &server_address, sizeof(server_address)) < 0) {
		std::cout << "Connection failed\n";
		exit(-1);
	}
	// send greeting
	send(socket_id, bot_name.c_str(), bot_name.length(), 0);
	return socket_id;
}

int main(int argc, char* argv[]) {
	// port is an optional command line argument
	int socket_id =  socket_connect(argc == 2 ? std::stoi(argv[1]) : 5000);

	// main game loop
	play_game();
	close(socket_id);
	return 0;
}
