<?php

require_once 'queuemodel.php';
ob_implicit_flush(1);

/**
 * @var array   $messagesNumber default total number of messages
 * @var integer $queueSize      default limit size of queue
 * @var integer $queueEmpty     total number of empty slots in the queue
 * @var integer $queueFull      total number of used slots in the queue
 * @var integer $criteria       default maximum number of messages that the consumer can process
 */
$messagesNumber = array(170,250);
$messagesinput  = null;
$queueSize      = 300; 
$queueEmpty     = $queueSize;
$queueFull      = 0;
$criteria       = 7;

/**
 * Parse the input of CLI
 * @var array options 
 */
$options = getopt("m:c:q:");
foreach ($options as $key => $value) {
    switch ($key) {
        case 'm':
            $value = trim($value);
            $messagesPackages = explode(",", $value);
            foreach ($messagesPackages as $package) {
                if((int)$package) {
                    $messagesinput[] = $package;
                }
            }
            if(!empty($messagesinput)) {
                $messagesNumber = $messagesinput;
            }
            break;
            case 'q':
            $value = trim($value);
            if((int)$value) {
                $queueSize = $value;
            }
            break;
            case 'c':
            $value = trim($value);
            if((int)$value) {
                $criteria = $value;
            }
            break;
    }
}

/**
 * verifying the criteria is not less than 1 
 */
if($criteria < 1) {
    echo "criteria (c) should be at least 1\r\n";
    exit();
}

/**
 * @var Producer        $producer       create a new producer 
 * @var ConsumerGroup   $consumerGroup  create a new consumer group 
 * @var QueueSystem     $queue          create a new queue storage 
 * @var Consumer        $consumer       create a new consumer 
 */
$producer               = new Producer(1);
$consumerGroup          = new ConsumerGroup();
$queue                  = new QueueSystem($queueSize);
$consumer[0]            = new Consumer(0);
$consumerGroup->attachToGroup($consumer[0]);

$format     = "%14d | %15d | %16d | %8d | %6s | %8d |%18s\n";
$format2    = "%14s | %15s | %16s | %8s | ";
$format3    = "%6s | %8s | %18s\n";

echo "\r\nSTART SIMULATION\r\n\r\n";
echo "  Queue Length | Total instances | Partition length | instance | Status | Criteria | Note \n";

foreach ($messagesNumber as $key => $messagePackage) {
    
    echo "-------------- + --------------- + ---------------- + -------- + ------ + -------- + ------------------\n";
    /**
     * Initialize a new message to publish it
     * @var array
     */
    $message = array();
    
    /**
     * Check the queue size if it is bigger than messages number 
     */
    if($queue->checkQueueSize($messagePackage)) {
            
        printf($format2, "Sending ".$messagePackage,"-", "-", "-");

        /**
         * Create and publish messages to queue after checking the queue if it is full
         * @var integer
         */
        for($i=0; $i<$messagePackage; $i++) {
            $producer->waitEmptyBuffer($queueEmpty);
            $message[$i] = "random task";
            $producer->publish($message[$i], $queue);
            $queueEmpty--;
            $queueFull++;
        }
    
        printf($format3, "done","-", "-");
    }
    else {
        printf($format, "-", "-", "-","-", "-", "-", "Error: Limit Q-Size");
    }

    /**
     * Check the queue if it is empty
     */
    if($consumer[0]->waitFullBuffer($queueFull)) {

        printf($format2, "".$messagePackage,"Scaling", "-", "-");
        $note           = "No change";
        $requiredScale  = $consumerGroup->autoScaleComsumerGroup($queue, $criteria);
        
        /**
         * Auto scale the consumer group according to the criteria
         */
        while($requiredScale != 0) {

            $actualCounsumers = count($consumerGroup->group);
            
            /**
             * Increase the consumer number
             */
            if($requiredScale > 0) {
            
                $consumer[$actualCounsumers] = new Consumer($actualCounsumers);
                $consumerGroup->attachToGroup($consumer[$actualCounsumers]);
                $note = "Increased";
            }
            /**
             * Decrease the consumer number 
             */
            else {
                $consumerGroup->detachFromGroup($consumer[$actualCounsumers-1]);
                $note = "Decreased";
            }
            
            $requiredScale = $consumerGroup->autoScaleComsumerGroup($queue, $criteria);
        }
        
        /**
         * Partition the messages to each consumer and consume them
         * @var integer $requiredScale
         */
        if($requiredScale == 0) {
            printf($format3, "done","-", $note);
            $consumerGroup->partition($queue, $criteria);
            $queueFull = 0;
        }
    }
    else {
        printf($format, "-", "-", "-","-", "-", "-", "Error: Empty Queue");
    }
}

echo "\r\nEND SIMULATION\r\n\r\n";
