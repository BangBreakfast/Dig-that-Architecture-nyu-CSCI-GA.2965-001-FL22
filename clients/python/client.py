import sys
import socket
import time

class Client():
    def __init__(self, port=5000):
        self.socket = socket.socket()
        self.port = port

        self.grid_num = 0
        self.number_of_phases = 0
        self.length_of_path = 0
        self.player_id = "T"

        self.socket.connect(("localhost", port))

        # Send over the name
        self.socket.send("Python Client".encode("utf-8"))
        

    def send_detector(self) :
        recv_msg = self.socket.recv(1024).decode().rstrip()
        inputs = recv_msg.split(" ")
        if inputs[0]== "next_phase": 
            if self.end_detect()!=0:
                msg = "end"
                self.socket.send(msg.encode("utf-8"))
                return 1
            else :
                msg = "detect"
                detectors = self.select_detector()
                for i in range(len(detectors)) :
                    msg = msg +  " " + str(detectors[i][0]) + "," + str(detectors[i][1])
                self.socket.send(msg.encode("utf-8"))
        elif inputs[0]=="path" :
            edge_set = []

            count = len(inputs)
            for i in range(1,count,4) :
                edge = (inputs[i],inputs[i+1],inputs[i+2],inputs[i+3])
                edge_set.append(edge)
                
            self.handle_edge_set(edge_set)
            if self.end_detect()!=0:
                msg = "end"
                self.socket.send(msg.encode("utf-8"))
                return 1
            else :
                msg = "detect"
                detectors = self.select_detector()
                for i in range(len(detectors)) :
                    msg = msg +  " " + str(detectors[i][0]) + "," + str(detectors[i][1])
                self.socket.send(msg.encode("utf-8"))
        else :
           raise "If you did not stop the program yourself, please emal lg3405 and cc dw2691\n"
        return 0

    def playgame(self):
        
        recv_msg = self.socket.recv(1024).decode().rstrip()

        input = recv_msg.split(' ')
        
        self.player_id = input[0]
        self.grid_num = int(input[1])
        self.number_of_phases = int(input[2])
        self.length_of_path = int(input[3])
        res = 0
        if self.player_id == 'T': 
            tunnel = self.dig_tunnel()
            msg = "digtunnel"
            for i in range(len(tunnel)) : 
                msg = msg + " " + str(tunnel[i][0]) + "," + str(tunnel[i][1])
            self.socket.send(msg.encode("utf-8"))
            ret_msg = self.socket.recv(1024).decode().rstrip()
            res = int(ret_msg)
        else :
            while self.number_of_phases>0 :
                self.number_of_phases-=1
                end_flag = self.send_detector()
                if end_flag == 1:
                    break
                time.sleep(0.1)
            if self.number_of_phases<=0: 
                recv_msg = self.socket.recv(1024).decode().rstrip()
                inputs = recv_msg.split(" ")
                if inputs[0]=="path" :
                    edge_set = []

                    count = len(inputs)
                    for i in range(1,count,4) :
                        edge = (inputs[i],inputs[i+1],inputs[i+2],inputs[i+3])
                        edge_set.append(edge)
                        
                    self.handle_edge_set(edge_set)
                else :
                    raise "If you did not stop the program yourself, please emal lg3405 and cc dw2691\n"
            recv_msg = self.socket.recv(1024).decode().rstrip()
            inputs = recv_msg.split(" ")
            if inputs[0]=="guess" :
                tunnel = self.guess_path()
                msg = "guess"
                for i in range(len(tunnel)) : 
                    msg = msg + " " + str(tunnel[i][0]) + "," + str(tunnel[i][1])
                self.socket.send(msg.encode("utf-8"))
                ret_msg = self.socket.recv(1024).decode().rstrip()
                res = int(ret_msg)
            else :
                raise "If you did not stop the program yourself, please emal lg3405 and cc dw2691\n"
        
        if res == -2 :
            print("The opponent do an invalid operation.")
        elif res == -1 :
            print("You do an invalid operation.")
        elif res == -3 :
            print("The detector failed to detect the tunnel.")
        else :
            print("The detector probe %d times.",res)
            

        self.socket.close()

    def dig_tunnel(self):
        '''
        //TODO: Dig the tunnel
		//the pair<int,int> is the passing tunnel vertex
		//the first element [x,y] should be like [x,1]
		//the last element [x,y] should be like [x,grid_num]

		//test codes stared  here
        '''
        res = []
        for i in range(1,self.grid_num+1) :
            res.append([1,i])
        return res
    
    def end_detect(self) :
        '''
        //TODO: This function will call before select detector.
		//Return a non-zero number if the detector believes that the information is enough
		//The server will jump into the guess mode
        '''
        return 1
    
    def select_detector(self) :
        '''
        //TODO: Select detector
		//the pair<int,int> is the detector vertex;
        '''
        res = []
        for i in range(1,self.grid_num+1) :
            res.append([1,i])
        return res
    
    def handle_edge_set(self,edge_set) :
        '''
        //TODO: This function will call after receive the edge set
		//Do what you want after receive the edge set
        //the input type is like edge_set = [(x1,y1,x2,y2),...];  
		//Note: the order is shuffled
		// 		the order of the vertices of each edge is also shuffled
		//		Two different probes may appear [1,1], [1,2] or [1,2][1,1]
		//		But they are the same edge
        '''
        pass
    
    def guess_path(self):
        '''
        //TODO: guess the path for the detector
		//the pair<int,int> is the passing tunnel vertex
		//the first element [x,y] should be like [x,1]
		//the last element [x,y] should be like [x,grid_num]
        //Test generation:
        '''
        res = []
        for i in range(1,self.grid_num+1) :
            res.append([1,i])
        return res
    

if __name__ == '__main__':
    if len(sys.argv) == 1:
        port = 5000
    else:
        port = int(sys.argv[1])

    # Change IncrementPlayer(port) to MyPlayer(port) to use your custom solver
    client = Client(port)
    client.playgame()

