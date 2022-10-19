
<template id = "gameArea">
  <div id="gridWrapper" >
    <div id="infoPanel">
      <h2> Score: {{score}} </h2>
      <h3> detector name : {{detector_name}}  </h3><h3>  tunneler name : {{tunneler_name}}</h3>
      <h3> {{information}}</h3>
      <button class="my_button" v-on:click=sendMessage()> next phase </button>
    </div>
    <div v-for = "m in size " :key=m class="vedgeWrapperwrapper">
        <div v-for="n in size " :key=n class="hedgeWrapper">
              <div v-bind:class=nodes[m-1][n-1] v-on:click="nodeClick(m-1,n-1,1)"></div>
              <div v-if="n < size " v-bind:class=hedges[m-1][n-1]  v-on:click="hedgeClick(m-1,n-1,1)"></div>
        </div>
        <div v-if="m < size " style="height: 70px;margin-top: -4.5px;">
            <div id="vedges" v-for="n in size " :key=n class="vedgeWrapper">
                <div v-bind:class=vedges[m-1][n-1] v-on:click="vedgeClick(m-1,n-1,1)"></div>
                <div v-if="n < size" class="left"></div>
            </div>
        </div>
      </div>
  </div>
</template>

<script>
export default {
  name: 'App',
  data() {
    return { 
      size: 2, 
      nodes: [["node"]],
      vedges: [["vedge"]],
      hedges: [["hedge"]],
      score: "Game Unfinished",
      detector_name: "undefined",
      tunneler_name: "undefined",
      number_of_phase: 1,
      connection: null,
      information : "",
    }
  },
  created: function() {
    //
    console.log("Starting connection to WebSocket Server")
    this.connection = new WebSocket("ws://localhost:5000/")
    this.connection.parent = this;
    this.connection.detect_cnt = 0;
    this.connection.onmessage = function(event) {
      console.log(event);
      if(event.data == null)
        return;
      let myArray = event.data.split(" ");
      console.log(myArray);
      if(myArray[0]=="terminated") {
        this.parent.score = myArray[1];
        this.parent.information = "successfully end the game";
      } else if(myArray[0] == "info") {
        this.parent.size = Number(myArray[1]);
        this.parent.number_of_phase = Number(myArray[2]);
        this.parent.update();
        console.log(this.parent.size);
      } else if (myArray[0] == "digtunnel") {
        let vertices = []
        for(let i = 1;i<myArray.length;++i) {
          let vertex = myArray[i].split(",");
          vertices.push([Number(vertex[0]),Number(vertex[1])]);
          if(i!=1) {
            let j = i-1;
            if(vertices[j][0]==vertices[j-1][0]&&vertices[j][1]==vertices[j-1][1]+1) {
              this.parent.vedgeClick(vertices[j-1][0]-1,vertices[j-1][1]-1,1);
            } else if(vertices[j][0]==vertices[j-1][0]&&vertices[j][1]==vertices[j-1][1]-1) {
              this.parent.vedgeClick(vertices[j][0]-1,vertices[j][1]-1,1);
            } else if (vertices[j][0]==vertices[j-1][0]+1&&vertices[j][1]==vertices[j-1][1]) {
              this.parent.hedgeClick(vertices[j-1][0]-1,vertices[j][1]-1,1);
            } else if(vertices[j][0]==vertices[j-1][0]+1&&vertices[j][1]==vertices[j-1][1]) {
              this.parent.hedgeClick(vertices[j][0]-1,vertices[j][1]-1,1);
            } 
          }
        }
        this.parent.information = "successfully dig the tunnel";
      }  else if(myArray[0] == "detect") {
        this.detect_cnt++;
        for(let i = 1;i<myArray.length;++i) {
          let vertex = myArray[i].split(",");
          this.parent.nodeClick(Number(vertex[0])-1,Number(vertex[1])-1,1);
        }
        this.parent.information = "successfully detect the tunnel:" + this.detect_cnt;
      } else if(myArray[0] == "guess") {
        let vertices = []
        for(let i = 1;i<myArray.length;++i) {
          let vertex = myArray[i].split(",");
          vertices.push([Number(vertex[0]),Number(vertex[1])]);
          if(i!=1) {
            let j = i-1;
            if(vertices[j][0]==vertices[j-1][0]&&vertices[j][1]==vertices[j-1][1]+1) {
              this.parent.vedgeClick(vertices[j-1][0]-1,vertices[j-1][1]-1,2);
            } else if(vertices[j][0]==vertices[j-1][0]&&vertices[j][1]==vertices[j-1][1]-1) {
              this.parent.vedgeClick(vertices[j][0]-1,vertices[j][1]-1,2);
            } else if (vertices[j][0]==vertices[j-1][0]+1&&vertices[j][1]==vertices[j-1][1]) {
              this.parent.hedgeClick(vertices[j-1][0]-1,vertices[j][1]-1,2);
              console.log("draw");
            } else if(vertices[j][0]==vertices[j-1][0]+1&&vertices[j][1]==vertices[j-1][1]) {
              this.parent.hedgeClick(vertices[j][0]-1,vertices[j][1]-1,2);
              console.log("draw");
            } 
          }
        }
        this.parent.information = "successfully guess the tunnel";
      } else if(myArray[0] == 'name') {
        this.parent.tunneler_name = myArray[1];
        this.parent.detector_name = myArray[2];
      }
    }

    this.connection.onopen = function(event) {
      console.log(event)
      console.log("Successfully connected to the echo websocket server...")
    }
   
  },
  methods: {
    update() {
      //data prepare
      let nodes = []
      let vedges = []
      let hedges = []
      for(let i=0; i<this.size;++i) {
        nodes.push([]);
        vedges.push([]);
        hedges.push([]);
        for(let j=0;j<this.size;++j) {
          nodes[i].push("node");
          vedges[i].push("vedge");
          hedges[i].push("hedge");
        }
      }
      this.nodes = nodes;
      this.vedges = vedges;
      this.hedges = hedges;
    },
    hedgeClick(n,m,flag) {
      if(flag==1){
        this.hedges[m][n] = 'hedgeSelected';
      } else if(flag ==2) {
        this.hedges[m][n] = 'hedgeGuess';
      } else{
        this.hedges[m][n] = 'hedge'
      }
      this.$forceUpdate();
    },
    vedgeClick(n,m,flag) {
      if(flag==1){
        this.vedges[m][n] = 'vedgeSelected';
      } else if(flag ==2) {
        this.vedges[m][n] = 'vedgeGuess';
      }
      else {
        this.vedges[m][n] = 'vedge'
      }
      this.$forceUpdate();
    },
    nodeClick(n,m,flag) {
      if(flag){
        this.nodes[m][n] = 'nodeSelected';
      } 
      else{
        this.nodes[m][n] = 'node'
      }
      this.$forceUpdate();
    },
    sendMessage: function() {
      let message = "next";
      this.connection.send(message);
    }
  }
}
</script>

<style>
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
  margin-top: 60px;
}

.node {
  position: relative;
  float: left;
  width: 10px;
  height: 10px;
  border-radius: 2px;
  background-color: #7f7f7f;
  -webkit-transition: width 2s ease, height 2s ease;
  -moz-transition: width 2s ease, height 2s ease;
  -o-transition: width 2s ease, height 2s ease;
  transition: width 2s ease, height 2s ease;
  z-index: 0;
}

#gameArea {
  text-align: center;
  margin-top: 20px;
}

.vedgeWrapperwrapper{
  height: 80px;
}

.nodeSelected {
    display: inline-block;
    position: relative;
    float: left;
    width: 10px;
    height: 10px;
    border-radius: 2px;
    background-color:  #880000;
    -webkit-transition: width 2s ease, height 2s ease;
    -moz-transition: width 2s ease, height 2s ease;
    -o-transition: width 2s ease, height 2s ease;
    transition: width 2s ease, height 2s ease;
    z-index: 0;
}

#gridWrapper {
  margin: 0 auto;
  text-align: center;
  padding: 0px;
  overflow: scroll;
  background-color: transparent;
}

.hedge {
    display: inline-block;
    position: relative;
    float: left;
    width: 70px;
    height: 10px;
    background-color: #262626;
    border-radius: 3px;
    -webkit-transition: width 2s ease, height 2s ease;
    -moz-transition: width 2s ease, height 2s ease;
    -o-transition: width 2s ease, height 2s ease;
    transition: width 2s ease, height 2s ease;
    z-index: 0;
    box-sizing: inherit;
}

.left {
    display: inline-block;
    position: relative;
    float: left;
    width: 70px;
    height: 70px;
    background-color: transparent;
    border-radius: 3px;
    z-index: 0;
}

.vedge {
    display: inline-block;
    position: relative;
    float: left;
    left: 0px;
    width: 10px;
    height: 70px;
    background-color: #262626;
    border-radius: 3px;
    -webkit-transition: width 2s ease, height 2s ease;
    -moz-transition: width 2s ease, height 2s ease;
    -o-transition: width 2s ease, height 2s ease;
    transition: width 2s ease, height 2s ease;
    z-index: 0;
}


.hedgeSelected{
  display: inline-block;
  position: relative;
  float: left;
  width: 70px;
  height: 10px;
  background-color: #00dab2;
  border-radius: 3px;
  -webkit-transition: width 2s ease, height 2s ease;
  -moz-transition: width 2s ease, height 2s ease;
  -o-transition: width 2s ease, height 2s ease;
  transition: width 2s ease, height 2s ease;
  z-index: 0;
}

.vedgeSelected {
    display: inline-block;
    position: relative;
    float: left;
    left: 0px;
    width: 10px;
    height: 70px;
    background-color: #00dab2;
    border-radius: 3px;
    -webkit-transition: width 2s ease, height 2s ease;
    -moz-transition: width 2s ease, height 2s ease;
    -o-transition: width 2s ease, height 2s ease;
    transition: width 2s ease, height 2s ease;
    z-index: 0;
}

.hedgeGuess{
  display: inline-block;
  position: relative;
  float: left;
  width: 70px;
  height: 10px;
  background-color: #008ada;
  border-radius: 3px;
  -webkit-transition: width 2s ease, height 2s ease;
  -moz-transition: width 2s ease, height 2s ease;
  -o-transition: width 2s ease, height 2s ease;
  transition: width 2s ease, height 2s ease;
  z-index: 0;
}

.vedgeGuess {
    display: inline-block;
    position: relative;
    float: left;
    left: 0px;
    width: 10px;
    height: 70px;
    background-color: #008ada;
    border-radius: 3px;
    -webkit-transition: width 2s ease, height 2s ease;
    -moz-transition: width 2s ease, height 2s ease;
    -o-transition: width 2s ease, height 2s ease;
    transition: width 2s ease, height 2s ease;
    z-index: 0;
}

#infoPanel {
  text-align: center;
  border: gray solid 1px;
  position: relative;
  margin-top: 0px;
  margin-bottom: 10px;
  margin-left: 10px;
  display: inline-block;
  padding: 10px;
  font-family: "Helvetica", Helvetica, sans-serif;
  font-size: 12pt;
  line-height: 1.2;
  width: 700px;
}

.hedgeWrapper {
  display: inline-block;
  text-align: center;
  background-color: transparent;
  width: auto;
}

.vedgeWrapper {
    display: inline-block;
    text-align:center;
    background-color: transparent;
    width: auto;
}

body {
  font-family: Lato,'Helvetica Neue',Arial,Helvetica,sans-serif;
  font-size: 14px;
  line-height: 1.4285em;
  color: rgba(0,0,0,.87);
  box-sizing: inherit;
}

.my_button {
  background: 0 0 !important;
  box-shadow: 0 0 0 1px #21BA45 inset !important;
  color: #21BA45 !important;
  font-weight: 400;
  border-radius: .28571429rem;
  text-transform: none;
  text-shadow: none !important;
  cursor: pointer;
  display: inline-block;
  min-height: 1em;
  outline: 0;
  border: none;
  vertical-align: baseline;
  background: #E0E1E2;
  color: rgba(0,0,0,.6);
  font-family: Lato,'Helvetica Neue',Arial,Helvetica,sans-serif;
  margin: 0 .25em 0 0;
  padding: .78571429em 1.5em;
  text-transform: none;
  text-shadow: none;
  font-weight: 700;
  line-height: 1em;
  font-style: normal;
  text-align: center;
  text-decoration: none;
  border-radius: .28571429rem;
  box-shadow: 0 0 0 1px transparent inset,0 0 0 0 rgba(34,36,38,.15) inset;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
  -webkit-transition: opacity .1s ease,background-color .1s ease,color .1s ease,box-shadow .1s ease,background .1s ease;
  transition: opacity .1s ease,background-color .1s ease,color .1s ease,box-shadow .1s ease,background .1s ease;
}
</style>
