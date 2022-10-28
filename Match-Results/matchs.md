# Match Result for all
## Anouncement
- I noticed that the websocket used in the original card-nim php server is not the correct form of websocket. So it can have unpredictable problems when sending long data. Considering the excessive effort required to refactor the php server, I currently have no plans to fix the GUI. If anyone is particularly interested in seeing the GUI results for particular matches, I will refactor the php server and run the corresponding match results in the future after I have free time.

- Also we did not receive the code froms Shuttlers, so the results of their matches in the table are indicates as "-".

- I noticed that many colleagues use random numbers in their codes, so the results may be a little different from the matches in lecture.


| ↓ tunneler : -> detector | ACE | Adult-Onset Diabetes | Coffee_Monster | Checkmate | Dexter's LabRats | HelloWorld | Infancywolf | Kitkat_Addicts | Mad_tacos | NULL |    Pratham     | PTO | Timeout | Truman | Shuttlers |
| ------------------------ | --- | -------------------- | -------------- | --------- | ---------------- | ---------- | ----------- | -------------- | --------- | ---- | -------------- | --- | ------- | ------ | --------- |
| ACE                      | -   | 96                   | 185            | 275       | infty            | infty      | infty       | infty          | infty     | 120  | timeout       | 133 | 183     | 361    | -         |
| Adult-Onset Diabetes     | 82  | -                    | 193            | infty     | infty            | timeout    | infty       | infty          | 190       | 88   | std::bad_alloc | 133 | 183     | 361    | -         |
| Coffee_Monster           | 101 | 99                   | -              | 257       | infty            | timeout    | infty       | infty          | infty     | 70   | time_out       | 133 | 183     | 361    | -         |
| Checkmate                | 100 | 88                   | 185            | -         | infty            | infty      | infty       | infty          | infty     | 19   | 283            | 133 | 183     | 361    | -         |
| Dexter's LabRats         | 110 | 92                   | 207            | 247       | -                | infty      | infty       | infty          | infty     | 84   | 192            | 133 | 183     | 361    | -         |
| HelloWorld               | 115 | 98                   | 193            | 319       | infty            | -          | infty       | infty          | 190       | 112  | timeout        | 133 | 182     | 361    | -         |
| Infancywolf              | 88  | 118                  | 177            | 301       | infty            | infty      | -           | infty          | 190       | 148  | timeout        | 133 | 183     | 361    | -         |
| Kitkat_Addicts           | 83  | 112                  | 183            | infty     | infty            | infty      | infty       | -              | 190       | 46   | 184            | 133 | 183     | 361    | -         |
| Mad_tacos                | 83  | 118                  | 186            | 256       | infty            | timeout    | infty       | infty          | -         | 46   | timeout        | 133 | 183     | 361    | -         |
| NULL                     | 117 | 102                  | 180            | infty     | infty            | infty      | infty       | infty          | infty     | -    | 271            | 133 | 182     | 361    | -         |
| Pratham                  | 90  | 96                   | 207            | 280       | infty            | infty      | infty       | infty          | infty     | 117  | -              | 133 | 183     | 361    | -         |
| PTO                      | 112 | 92                   | 173            | infty     | infty            | timeout    | infty       | infty          | infty     | 49   | timeout        | -   | 183     | 361    | -         |
| Timeout                  | 122 | 102                  | 196            | 256       | infty            | infty      | infty       | infty          | infty     | 55   | 336            | 133 | -       | 361    | -         |
| Truman                   | 82  | 106                  | 177            | 343       | infty            | infty      | infty       | infty          | 190       | 124  | timeout        | 133 | 183     | -      | -         |
| Shuttlers                | -   | -                    | -              | -         | -                | -          | -           | -              | -         | -    | -              | -   | -       | -      | -         |


| Rank |       Teamname       | WIN:TIE:LOSE |
| ---- | -------------------- | ------------ |
| 1    | ACE                  | 13:0:0       |
| 2    | NULL                 | 12:0:1       |
| 3    | Adult-Onset Diabetes | 11:0:2       |
| 4    | PTO                  | 10:0:3       |
| 5    | Timeout              | 9:0:4        |
| 6    | Coffee_Monster       | 8:0:5        |
| 7    | Checkmate            | 6:1:6        |
| 8    | Truman               | 5:0:8        |
| 9    | Mad_tacos            | 4:2:7        |
| 10   | Pratham              | 2:3:8        |
| 11   | Dexter's LabRats     | 0:4:9        |
| 11   | HelloWorld           | 0:4:9        |
| 11   | Infancywolf          | 0:4:9        |
| 11   | Kitkat_Addicts       | 0:4:9        |
| 15   | Shuttlers            | 0:0:0        |
