<?php
/**
 * @file
 * WatchdogWriterSubscriber class: accumulate events, write them at end of page.
 *
 * @author: Frederic G. MARAND <fgm@osinet.fr>
 *
 * @copyright (c) 2013-2014 Ouest Systèmes Informatiques (OSInet).
 *
 * @license General Public License version 2 or later
 */

namespace OSInet\Heisencache;


class WatchdogWriterSubscriber extends BaseWriterSubscriber {

  public function onShutdown($channel) {
    if (!empty($this->history)) {
      watchdog('heisencache', 'Cache events: @events', array(
        '@events' => serialize($this->history),
      ), WATCHDOG_DEBUG);
    }
  }
}
