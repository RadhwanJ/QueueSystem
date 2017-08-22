<?php

abstract class AbstractConsumer 
{
    abstract function waitFullBuffer($queueFull);
    abstract function processing($message);
}

abstract class AbstractConsumerGroup 
{
    abstract function attachToGroup(AbstractConsumer $consumer);
    abstract function detachFromGroup(AbstractConsumer $consumer);
    abstract function autoScaleComsumerGroup(AbstractQueue $queue, $criteria);
    abstract function partition(AbstractQueue $queue, $criteria);
}

abstract class AbstractQueue 
{   
    abstract function checkQueueSize($messagesNumber);
    abstract function addMessage($message);   
    abstract function getMessage();   
}

abstract class AbstractProducer 
{
    abstract function waitEmptyBuffer($queueEmpty);
    abstract function publish($message, AbstractQueue $queue); 
}

/**
 * Consume the published messages
 */
class Consumer extends AbstractConsumer
{
    /**
     * Identify the consumer]
     * @var integer
     */
    public $id;

    /**
     * Specify the id of the created consumer
     * @param integer $consumerId
     */
    public function __construct($consumerId) 
    {
        $this->id = $consumerId;
    }

    /**
     * Wait the producer to send message if the queue (buffer) is empty
     * @param  integer $queueFull indicates the length of queue
     * @return boolean 
     */
    function waitFullBuffer($queueFull)
    {
        $result = true;
        if($queueFull == 0) {
            $result = false;
        }
        return $result;
    }

    /**
     * Process the message, it is loop of a random computation
     * @param  [string] $message the recieved message of producer
     * @return 
     */
    function processing($message) 
    {
        for ($i=0; $i < 1000; $i++) { 
            for ($j=0; $j < 1000 ; $j++) { 
                $c = $i*$j;
            }
        }
    }
}

/**
 * Manage the auto scale consumers 
 */
class ConsumerGroup extends AbstractConsumerGroup
{
    /**
     * Initialize group of consumers
     * @var array
     */
    public $group  = array();

    /**
     * Add consumer to the consumer group
     * @param  AbstractConsumer $consumer
     */
    public function attachToGroup(AbstractConsumer $consumer) 
    {
        $this->group[] = $consumer;
        $this->group   = array_values($this->group);
    }

    /**
     * Delete consumer from the consumer group
     * @param  AbstractConsumer $consumer
     */
    public function detachFromGroup(AbstractConsumer $consumer)
    {
        foreach($this->group as $k => $value) {
            if ($value->id == $consumer->id) { 
                unset($this->group[$k]);
            }
        }
    }

    /**
     * Identify the required number of consumers (either increase, decrease or no change(0))
     * according to the criteria
     * @param  AbstractQueue $queue    
     * @param  integer       $criteria
     * @return integer 
     */
    public function autoScaleComsumerGroup(AbstractQueue $queue, $criteria)
    {
        $actualCounsumers   = count($this->group);
        $requiredConsumers  = ceil((count($queue->storage)/$criteria));
        $requiredScale      = $requiredConsumers - $actualCounsumers;

        return $requiredScale;
    }

    /**
     * Partition the messages according to the consumer group scale
     * @param  AbstractQueue $queue    
     * @param  integer       $criteria
     * @param  integer       $totalMessages     total number of messages
     * @param  integer       $actualCounsumers  number of current consumers in the group
     * @param  integer       $partitionLength   number of messages consumed by consumer
     * @param  integer       $messagesModulus   messages remaining after partition (less than partition length)
     * @return 
     */
    public function partition(AbstractQueue $queue, $criteria)
    {
        $totalMessages      = count($queue->storage);
        $actualCounsumers   = count($this->group);
        $partitionLength    = intval($totalMessages/$actualCounsumers);
        $messagesModulus    = $totalMessages%$actualCounsumers;

        for($i=0; $i<$actualCounsumers; $i++) {
            
            for($j=0; $j<$partitionLength; $j++) {
                $this->group[$i]->processing($queue->getmessage());
            }
                               
            if($messagesModulus > 0) {
                $this->group[$i]->processing($queue->getmessage());
                $messagesModulus--;
                $format = "%14d | %15d | %16d | %8d | %6s | %8d | %18s\n";
                printf($format, count($queue->storage), $actualCounsumers, $partitionLength+1, $i+1, "done", $criteria, "-");
            }
            else {
                $format = "%14d | %15d | %16d | %8d | %6s | %8d | %18s\n";
                printf($format, count($queue->storage), $actualCounsumers, $partitionLength, $i+1, "done", $criteria, "-");
            }          
        }       
    }
}

/**
 * Buffer the published messages until they are fetched by the consumer
 */
class QueueSystem extends AbstractQueue
{
    /**
     * @var array $storage store the published messages
     * @var integer $storageSize set the limit size of queue
     */
    public $storage = array();
    private $storageSize = 1000;
    
    /**
     * Set a new limit size of queue
     * @param integer $queueSize
     */
    public function __construct($queueSize) 
    {
        $this->storageSize  = $queueSize;
    }

    /**
     * Check the published messages with the queue size
     * @param  integer $messagesNumber
     * @return boolean 
     */
    public function checkQueueSize($messagesNumber)
    {
        $result = false;
        if($this->storageSize >= $messagesNumber) {
            $result = true;
        }

        return $result;
    }

    /**
     * Add message to queue
     * @param string $message
     */
    public function addMessage($message)
    {
        $this->storage[] = $message;
    }

    /**
     * Get message from queue
     * @return string
     */
    public function getmessage()
    {
        return array_shift($this->storage);
    }
}

/**
 * Publish the messages
 */
class Producer extends AbstractProducer 
{
    /**
     * indetify producer id
     * @var [type]
     */
    public $id;

    /**
     * Specify the id for the created producer
     * @param [type]
     */
    public function __construct($prducerId) 
    {
        $this->id  = $prducerId;
    }
    /**
     * Wait the consumer to consume the published message if the queue if full
     * @param  integer $queueEmpty 
     * @return 
     */
    public function waitEmptyBuffer($queueEmpty)
    {
        if($queueEmpty == 0) {
            sleep(5);
        }
    }
    
    /**
     * Pubish the message to the queue
     * @param  string        $message 
     * @param  AbstractQueue $queue  
     * @return 
     */
    public function publish($message, AbstractQueue $queue) 
    {
        $queue->addMessage($message);
    }
}