=======
PHP-Log-Analyzer-with-Endpoints (PLAE)
===============================

PLAE is PHP based log analyzer, it parses the log file based on the defined endpoints. Those can be changed in Parser.php file.

Against every matched request against the endpoints, PLAE calculates the following:

- The number of times the URL was called.
- The mean (average), median and mode of the response time (connect time + service time).
- The "dyno" that responded the most. 

Defined Endpoints
---
```
GET /api/users/{user_id}/count_pending_messages
GET /api/users/{user_id}/get_messages
GET /api/users/{user_id}/get_friends_progress
GET /api/users/{user_id}/get_friends_score
POST /api/users/{user_id}
GET /api/users/{user_id}
```

How to run
---
`php run.php path-to-log-file path-to-output-file` will generate take the data from target file and export the stats to the output file.

`php run.php` will generate the ouput to report.txt file.


Silent Features
---
- Don't use too many system resources.
- Releases memory as soon as completes processing.
- Only fetches the log entries which are based on the endpoints.

Generated sample report:
---
```
Generated on: 2014-11-27 01:05:43


-GET /api/users/{user_id}/count_pending_messages

	# URL was called: 2430, Response time Mean/Avg: 25.99670781893ms, Response time Median: 15ms, Response time Mode: 11ms, Dyno(s) that responded the most: web.2


-POST /api/users/{user_id}

	# URL was called: 2022, Response time Mean/Avg: 82.77546983185ms, Response time Median: 46ms, Response time Mode: 23ms, Dyno(s) that responded the most: web.11


-GET /api/users/{user_id}/get_friends_progress

	# URL was called: 1117, Response time Mean/Avg: 111.89704565801ms, Response time Median: 51ms, Response time Mode: 35ms, Dyno(s) that responded the most: web.5


-GET /api/users/{user_id}/get_friends_score

	# URL was called: 1533, Response time Mean/Avg: 228.76516634051ms, Response time Median: 142.5ms, Response time Mode: 67ms, Dyno(s) that responded the most: web.7


-GET /api/users/{user_id}/get_messages

	# URL was called: 652, Response time Mean/Avg: 62.170245398773ms, Response time Median: 32ms, Response time Mode: 23ms, Dyno(s) that responded the most: web.11

```
