<?php

namespace Drupal\endpoint_status\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\endpoint_status\Constants\EndpointStatusConstants;

/**
 * Provides a listing of EndpointStatus.
 */
class EndpointStatusListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Endpoint Status');
    $header['id'] = $this->t('Machine name');
    $header['uri'] = $this->t('URI');
    $header['enabled'] = $this->t('Enabled');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['uri'] = $entity->get('uri');
    $row['enabled'] = $entity->get('enabled') ? $this->t('true') : $this->t('false');
    $row['status'] = $entity->get('status') ?? EndpointStatusConstants::NEUTRAL;

    // You probably want a few more properties here...

    return $row + parent::buildRow($entity);
  }

}
