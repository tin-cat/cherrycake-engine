<?php

namespace Cherrycake\Cache;

/**
 * Additional interface for all cache providers that additionally implement Queueing functionalities
 * Queues act as FIFO or LIFO queues, you can add items to the end or to the begginning of a queue, and you can only get items from the beggining or from the end of the queue. When an item is read from the queue, it is also removed from it.
 */
interface CacheProviderInterfaceQueue {
	/**
	 * Puts a value to the end of a queue
	 * @param string $queueName The name of the queue
	 * @param mixed $value The value to store
	 * @return boolean True if everything went ok, false otherwise
	 */
	function queueRPush($queueName, $value);

	/**
	 * Puts a value to the beggining of a queue
	 * @param string $queueName The name of the queue
	 * @param mixed $value The value to store
	 * @return boolean True if everything went ok, false otherwise
	 */
	function queueLPush($queueName, $value);

	/**
	 * Returns the element at the end of a queue, and removes it
	 * @param string $queueName The name of the queue
	 * @return mixed The stored value, or null if the queue was empty
	 */
	function queueRPop($queueName);

	/**
	 * Returns the element at the beggining of a queue, and removes it
	 * @param string $queueName The name of the queue
	 * @return mixed The stored value, or null if the queue was empty
	 */
	function queueLPop($queueName);
}
