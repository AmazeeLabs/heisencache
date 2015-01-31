<?php

namespace OSInet\Heisencache;

/**
 * The cache-read-log subscriber/writer.
 */
class CacheReadLogWriterSubscriber extends BaseEventSubscriber {

  /**
   * Database table name.
   */
  const DB_TABLE = 'heisencache_cache_read_log';

  /**
   * Indicates whether we allowed to monitor cache_get() calls.
   *
   * @var bool
   */
  protected static $isActive = TRUE;

  /**
   * An array representing cache_get() calls grouped by channel/cache_id.
   *
   * @var array
   *   array(
   *     {channel} => array(
   *       {cache ID} => NULL,
   *       ...,
   *     ),
   *     ...,
   *   )
   */
  protected $hits = array();

  /**
   * An array of channels to log.
   *
   * @var array|null
   */
  protected $channels;

  /**
   * {@inheritdoc}
   */
  protected $subscribedEvents = array(
    'beforeGet' => 1,
    'onShutdown' => 1,
  );

  /**
   * Constructor.
   *
   * @param array $channels
   *   An array of channels (cache bins) to log. If empty, all channels will be
   *   logged.
   */
  public function __construct(array $channels = NULL) {
    if (!empty($channels)) {
      foreach ($channels as $channel) {
        $this->channels[$channel] = TRUE;
      }
    }
  }

  /**
   * Triggered before each cache_get() call.
   *
   * @param string $channel
   *   Channel (cache bin).
   * @param string $cid
   *   Cache Id.
   */
  public function beforeGet($channel, $cid) {
    if (self::$isActive && (empty($this->channels) || isset($this->channels[$channel]))) {
      $this->hits[$channel][$cid] = NULL;
    }
  }

  /**
   * Triggered before shutdown. Saves cache_get() hits in the database.
   */
  public function onShutdown() {
    if (empty($this->hits)) {
      return;
    }
    $transaction = db_transaction();
    $delete_query = db_delete(self::DB_TABLE);
    $delete_or_conditions = db_or();
    $insert_query = db_insert(self::DB_TABLE)
      ->fields(array(
        'channel',
        'cache_id',
        'timestamp',
      ));
    foreach ($this->hits as $channel => $cache_ids) {
      $cache_ids = array_keys($cache_ids);
      $delete_or_conditions->condition(
        db_and()
          ->condition('channel', $channel)
          ->condition('cache_id', $cache_ids)
      );
      foreach ($cache_ids as $cache_id) {
        $insert_query->values(array(
          $channel,
          $cache_id,
          REQUEST_TIME,
        ));
      }
    }
    try {
      $delete_query->condition($delete_or_conditions)->execute();
      $insert_query->execute();
    }
    catch (\Exception $e) {
      $transaction->rollback();
    }
    $this->hits = array();
  }

  /**
   * Returns current writer state.
   *
   * @return bool
   */
  public static function getState() {
    return self::$isActive;
  }

  /**
   * Sets writer state.
   *
   * @param bool $is_active
   */
  public static function setState($is_active) {
    self::$isActive = $is_active;
  }
}
