<?php

namespace Drupal\endpoint_status\Constants;


/**
 * Constant class for the status of endpoints
 * @internal
 */
class EndpointStatusConstants {
  const NEUTRAL = "neutral";
  const UP = "up";
  const DOWN = "down";
  const MALFORMED = "malformed";
  const UNPROCESSABLE = "unprocessable";

  const ALL_STATUSES = [
    EndpointStatusConstants::NEUTRAL,
    EndpointStatusConstants::UP,
    EndpointStatusConstants::DOWN,
    EndpointStatusConstants::MALFORMED,
    EndpointStatusConstants::UNPROCESSABLE,
  ];
}
