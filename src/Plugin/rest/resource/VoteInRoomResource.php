<?php

namespace Drupal\academy\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\media\OEmbed\ResourceException;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "vote_in_room_resource",
 *   label = @Translation("Vote in room resource"),
 *   uri_paths = {
 *     "create" = "/api/voteroom/{entity}/vote"
 *   }
 * )
 */
class VoteInRoomResource extends ResourceBase {

    /**
     * A current user instance.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;

    protected $entityTypeManager;

    /**
     * Constructs a new VoteInRoomResource object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param array $serializer_formats
     *   The available serialization formats.
     * @param \Psr\Log\LoggerInterface $logger
     *   A logger instance.
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   A current user instance.
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        array $serializer_formats,
        LoggerInterface $logger,
        AccountProxyInterface $current_user,
        EntityTypeManagerInterface $entityTypeManager
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

        $this->currentUser = $current_user;
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->getParameter('serializer.formats'),
            $container->get('logger.factory')->get('academy'),
            $container->get('current_user'),
            $container->get('entity_type.manager')
        );
    }

    /**
     * Responds to POST requests.
     *
     * @param string $payload
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function post($id, $payload) {

        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        /** @var \Drupal\node\Entity\Node $room */
        $room = $this->entityTypeManager->getStorage('node')->load($id);
        if ($room) {
          $user = $payload['user'];
          if ($room->field_user->isEmpty()) {
            $position = 0;
          }
          else {
            $users = array_column(
              $room->field_user->getValue(),
              'value'
            );
            $position = array_search($user, $users);
            $position = $position === FALSE? count($users) : $position;
          }
          $this->saveVoteInPosition($room, $user, $payload['vote'], $position);
          return new ModifiedResourceResponse($payload, 200);
        }

        return new ResourceResponse($id, 404);
    }

  /**
   * @param \Drupal\node\Entity\Node $room
   */
  protected function saveVoteInPosition(\Drupal\node\Entity\Node $room, $user, $vote, $position) {
    $room->get('field_user')->set($position, $user);
    $room->get('field_vote')->set($position, $vote);
    $room->save();
  }
}
