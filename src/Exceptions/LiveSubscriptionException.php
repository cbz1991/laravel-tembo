<?php

namespace Cabuz\Tembo\Exceptions;

/**
 * Tembo rejected a live subscription, or sent a frame that breaks the
 * protocol. The code is the HTTP-style status Tembo reported.
 */
class LiveSubscriptionException extends TemboException {}
