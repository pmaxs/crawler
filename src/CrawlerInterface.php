<?php
namespace Pmaxs\Crawler;

/**
 * Interface CrawlerInterface
 */
interface CrawlerInterface
{
    /**
     * Adds task to crawler queue.
     *
     * @param Request $request
     * @param callable $callback
     * @return Task
     */
    public function request(Request $request, $callback);

    /**
     * Removes task from crawler queue.
     *
     * @param Task $task
     */
    public function remove(Task $task);

    /**
     * Processes crawler queue.
     */
    public function process();
}
