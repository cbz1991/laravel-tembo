<?php

namespace Cabuz\Tembo\Events;

/**
 * The session was still running when the watch timeout expired. It may still finish later.
 */
class SessionWatchTimedOut extends SessionEvent {}
