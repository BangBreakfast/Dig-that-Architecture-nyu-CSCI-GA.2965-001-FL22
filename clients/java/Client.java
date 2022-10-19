import java.net.*;
import java.io.*;
import java.util.*; 

public class Client
{
    // Initialize socket and input output streams
    private Socket socket            = null;
    private BufferedReader input   	 = null;
    private PrintWriter out     	 = null;

    // Track game variables 
    int grid_num;
    int number_of_phases;
    int length_of_path;
    char player_id;
    int res=0;
  
    // Constructor to put ip address and port
    public Client(String address, int port)
    {
        // Establish a connection
        try
        {
            socket = new Socket(address, port);
            System.out.println("Connected");
  
            // Client takes input from socket
            input = new BufferedReader(new InputStreamReader(socket.getInputStream()));
  
            // And also sends its output to the socket
            out = new PrintWriter(socket.getOutputStream(), true);
        }
        catch(UnknownHostException u)
        {
            System.out.println(u);
        }
        catch(IOException i)
        {
            System.out.println(i);
        }

    }

    public static void main(String args[]) throws IOException
    {
    	int portNumber;
    	if (args.length == 0) {
    		System.out.println("Connecting on default port 5000");
    		portNumber = 5000;
    	}
    	else {
    		portNumber = Integer.parseInt(args[0]);
    		System.out.println(String.format("Connecting on specified port %d", portNumber));
    	}
        Client client = new Client("127.0.0.1", portNumber);
        client.run();
    }

    public void run() throws IOException {
    	// Send our name to server
        out.println("Java Client");

        // Then wait for the ready messsage from the server once both clients are connected
        String readyMessage = input.readLine();

        player_id = (readyMessage.split(" ")[0]).charAt(0);
        grid_num = Integer.parseInt(readyMessage.split(" ")[1]);
        number_of_phases = Integer.parseInt(readyMessage.split(" ")[2]);
        length_of_path = Integer.parseInt(readyMessage.split(" ")[3]);

        System.out.println(String.format(
            " Initialized with %d*%d grid, %d phases and maximum  %d length of the path\n", 
            grid_num,grid_num,number_of_phases,length_of_path));
        Integer res;

        if (player_id == 'T') {
            ArrayList<int[]>  tunnel = dig_tunnel();
            String msg = "digtunnel";
            for(int i=0; i < tunnel.size(); ++i) {
                msg = msg + " " + String.valueOf(tunnel.get(i)[0]) + "," + String.valueOf(tunnel.get(i)[1]);
            }
            out.println(msg);
            String ret_msg = input.readLine();
            res = Integer.parseInt(ret_msg);
        } else {
            while(number_of_phases>0) {
                number_of_phases--;
                int end_flag = send_detector();
                if(end_flag == 1) {
                    break;
                }
                try {
                    Thread.sleep(100);
                } catch(InterruptedException ex) {
                    Thread.currentThread().interrupt();
                }
            }
            if(number_of_phases<=0) {
                // read the last edge_set
                String ret_msg = input.readLine();
                String[] inputs = ret_msg.split(" ");
                if(inputs[0].equals("path")) {
                    ArrayList<int[]> edge_set = new ArrayList<int[]>();;
                    int count = inputs.length;
                    for(int i=1;i<count;i+=4) {
                        int[] edge = new int[]{Integer.parseInt(inputs[i]),
                            Integer.parseInt(inputs[i+1]),Integer.parseInt(inputs[i+2]),Integer.parseInt(inputs[i+3])};
                        edge_set.add(edge);
                    }
                    handle_edge_set(edge_set);
                } else {
                    throw new IOException("If you did not stop the program yourself, please emal lg3405 and cc dw2691\n");
                }
            }
            String ret_msg = input.readLine();
            String[] inputs = ret_msg.split(" ");
            if(inputs[0].equals("guess")) {
                ArrayList<int[]>  tunnel = guess_path();
                String msg = "guess";
                for(int i=0; i < tunnel.size(); ++i) {
                    msg = msg + " " + String.valueOf(tunnel.get(i)[0]) + "," + String.valueOf(tunnel.get(i)[1]);
                }
                out.println(msg);
                ret_msg = input.readLine();
                res = Integer.parseInt(ret_msg);
            } else {
                throw new IOException("If you did not stop the program yourself, please emal lg3405 and cc dw2691\n");
            }
        }
        if(res == -2) {
            System.out.println(String.format(
                "The opponent do an invalid operation."));
        } else if(res == -1) {
            System.out.println(String.format(
                "You do an invalid operation."));
        } else if(res == -3) {
            System.out.println(String.format(
                "The detector failed to detect the tunnel."));
        }else {
            System.out.println(String.format(
                "The detector probe %d times.",res));
        }
    }

    public int send_detector() throws IOException {
        String ret_msg = input.readLine();
        String[] inputs = ret_msg.split(" ");
        if(inputs[0].equals("next_phase")) {
            if(end_detect()!=0){
                String msg = "end";
                out.println(msg);
                return 1;
            } else {
                String msg = "detect";
                ArrayList<int[]>  detectors = select_detector();
                for(int i=0; i < detectors.size(); ++i) {
                    msg = msg +  " " + String.valueOf(detectors.get(i)[0]) + "," 
                        + String.valueOf(detectors.get(i)[1]);
                }
                out.println(msg);
            }
        } else if(inputs[0].equals("path")) {
            ArrayList<int[]> edge_set = new ArrayList<int[]>();

            int count = inputs.length;
            for(int i=1;i<count;i+=4) {
                int[] edge = new int[]{Integer.parseInt(inputs[i]),
                    Integer.parseInt(inputs[i+1]),Integer.parseInt(inputs[i+2]),Integer.parseInt(inputs[i+3])};
                edge_set.add(edge); 
            }
            handle_edge_set(edge_set);
            if(end_detect()!=0){
                String msg = "end";
                out.println(msg);
                return 1;
            } else {
                String msg = "detect";
                ArrayList<int[]>  detectors = select_detector();
                for(int i=0; i < detectors.size(); ++i) {
                    msg = msg +  " " + String.valueOf(detectors.get(i)[0]) + "," 
                        + String.valueOf(detectors.get(i)[1]);
                }
                out.println(msg);
            }
        } else {
           throw new IOException("If you did not stop the program yourself, please emal lg3405 and cc dw2691\n");
        }
        return 0;
    }

    public ArrayList<int[]> dig_tunnel() throws IOException {
        //TODO: Dig the tunnel
		//the the passing tunnel vertex
		//the first element [x,y] should be like [x,1]
		//the last element [x,y] should be like [x,grid_num]
        //Test generation:
       
        ArrayList<int[]> res = new ArrayList<int[]>();
        for(int x = 1; x <= grid_num; x = x+1) {
            int[] tmp = new int[]{1,x};
            res.add(tmp);
        }
        //
        return res;
    
    }

    public int end_detect() throws IOException {
        //TODO: This function will call before select detector.
		//Return a non-zero number if the detector believes that the information is enough
		//The server will jump into the guess mode
       
        return 0;
    }

    public ArrayList<int[]> select_detector() throws IOException {
		//TODO: Select detector
		//the pair<int,int> is the detector vertex;
       
        ArrayList<int[]> res = new ArrayList<int[]>();
        for(int x = 1; x <= grid_num; x = x+1) {
            int[] tmp = new int[]{1,x};
            res.add(tmp);
        }
        //
        return res;
    
    }

    public void handle_edge_set( ArrayList<int[]> edge_set) throws IOException {
		//TODO: This function will call after receive the edge set
		//Do what you want after receive the edge set
        //the input type is like int[] = {x1,y1,x2,y2};  
		//Note: the order is shuffled
		// 		the order of the vertices of each edge is also shuffled
		//		Two different probes may appear [1,1], [1,2] or [1,2][1,1]
		//		But they are the same edge

    }
    
    public ArrayList<int[]> guess_path() throws IOException {
        //TODO: guess the path for the detector
		//the pair<int,int> is the passing tunnel vertex
		//the first element [x,y] should be like [x,1]
		//the last element [x,y] should be like [x,grid_num]
        //Test generation:
       
        ArrayList<int[]> res = new ArrayList<int[]>();
        for(int x = 1; x <= grid_num; x = x+1) {
            int[] tmp = new int[]{1,x};
            res.add(tmp);
        }
        //
        return res;
    
    }
    
}
