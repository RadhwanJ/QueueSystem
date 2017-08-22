# Queue System


The simulation stands on three inputs:

* `m`: the total number of messages that should be published by producer and consumed by the consumers in consumer group
* `q`: the maximum limit size of queue
* `c`: the number of messages that the consumer can process. 

Note: All of these variables have a default value, so the script can be running without inserting any value.

# Commands

Run the script based on default values:

	php simulationmodel.php -f 

Run the script with a different numbers of messages:

	php simulationmodel.php -f  -m value,value,value...etc

Run the script with a different queue size:
	
	php simulationmodel.php -f  -q value

Run the script with other criteria:
	
	php simulationmodel.php -f  -c value

Example, all together 
	
	$ php simulationmodel.php -f -m -q -c
	$ php simulationmodel.php -f -m 14,7,8,5,13 -c 3 -q 100
	
# Attachment

* `simulationIdea.pdf` describes the idea of solution
* `classDiagram.png` the diagram of the simulation classes
* `example.png` screenshot of my running example 
* `queuemodel.php` includes all the classes of queueing system (i.e. the solution)
* `simulationmodel.php` parses the user inputs and runs the solution



Looking forward to your comments

Have Fun!
