<?php

namespace OSInet\Heisencache;

/**
 * The cache-read-log reader.
 */
class CacheReadLogReader {

  /**
   * The query result.
   *
   * @var \DatabaseStatementBase|null
   */
  protected $result;

  /**
   * The channel (cache bin) on which the query() was run last time.
   *
   * @var string|null
   */
  protected $channel;

  /**
   * Queries cache IDs from the log.
   *
   * @param string $channel
   *   The channel (cache bin) to fetch.
   * @param int|null $max_age
   *   The time in seconds representing the maximum age of log records to fetch.
   * @param bool $wipe_out
   *   Indicates whether to delete old log records, or just skip them. Only used
   *   if $max_age is passed.
   *
   * @return $this
   */
  public function query($channel, $max_age = NULL, $wipe_out = FALSE) {
    $this->channel = $channel;
    $query = db_select(CacheReadLogWriterSubscriber::DB_TABLE, 't')
      ->fields('t', array('cache_id'))
      ->condition('t.channel', $channel)
      ->orderBy('t.timestamp', 'DESC');
    if ($max_age) {
      if ($wipe_out) {
        db_delete(CacheReadLogWriterSubscriber::DB_TABLE)
          ->condition('channel', $channel)
          ->condition('timestamp', REQUEST_TIME - $max_age, '<')
          ->execute();
      }
      else {
        $query->condition('t.timestamp', REQUEST_TIME - $max_age, '>');
      }
    }
    $this->result = $query->execute();
    return $this;
  }

  /**
   * Returns the next cache ID from the executed query.
   *
   * @param bool $filter_existing
   *   Indicates whether to skip currently existing caches.
   *
   * @return string|bool
   *   The cache ID, or FALSE.
   */
  public function getNextCacheId($filter_existing) {
    if (!isset($this->result)) {
      return FALSE;
    }
    $log_writer_state = CacheReadLogWriterSubscriber::getState();
    CacheReadLogWriterSubscriber::setState(FALSE);
    while (($cache_id = $this->result->fetchColumn()) !== FALSE) {
      if ($filter_existing) {
        if (cache_get($cache_id, $this->channel)) {
          continue;
        }
      }
      CacheReadLogWriterSubscriber::setState($log_writer_state);
      return $cache_id;
    }
    CacheReadLogWriterSubscriber::setState($log_writer_state);
    return FALSE;
  }
}
