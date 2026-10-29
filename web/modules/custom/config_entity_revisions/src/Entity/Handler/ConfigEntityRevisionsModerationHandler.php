<?php

namespace Drupal\config_entity_revisions\Entity\Handler;

use Drupal\content_moderation\Entity\Handler\ModerationHandler;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Customizations for config_entity_revisions entities.
 */
class ConfigEntityRevisionsModerationHandler extends ModerationHandler {
  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * NodeModerationHandler constructor.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation information service.
   */
  public function __construct(ModerationInformationInterface $moderation_info) {
    $this->moderationInfo = $moderation_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function onPresave(ContentEntityInterface $entity, $default_revision, $published_state) {

    // Updating an existing revision?
    if (!$entity->isNewRevision()) {
      return;
    }

    $entity->setNewRevision(TRUE);
    $entity->isDefaultRevision($default_revision);

    // Update publishing status if it can be updated and if it needs updating.
    if (($entity instanceof EntityPublishedInterface) && $entity->isPublished() !== $published_state) {
      $published_state ? $entity->setPublished() : $entity->setUnpublished();
    }
  }

}
