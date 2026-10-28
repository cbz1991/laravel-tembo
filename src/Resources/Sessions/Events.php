<?php

namespace Cabuz\Tembo\Resources\Sessions;

use Cabuz\Tembo\Resources\Resource;

/**
 * A session's output events. List past events with
 * Tembo::sessions()->listEvents(); follow new ones with live().
 */
class Events extends Resource
{
    public function live(): EventsLive
    {
        return new EventsLive($this->tembo);
    }
}
