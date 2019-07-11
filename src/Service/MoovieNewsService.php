<?php

namespace Drupal\academy\Service;

use GuzzleHttp\Client;
use Symfony\Component\Serializer\Serializer;

/**
 * Class MoovieNewsService.
 */
class MoovieNewsService implements MoovieNewsServiceInterface {

  private $client;
  private $serializer;
  /**
   * Constructs a new MoovieNewsService object.
   */
  public function __construct(Client $client, Serializer $serializer) {
    $this->client = $client;
    $this->serializer = $serializer;
  }

  public function getNews( $url, $latest = false) {
    $data = $this->client->get($url);

    return $this->serializer->decode($data->getBody(), 'xml');
  }

  public function getLatestNews($url) {
    return $this->getNews($url, true);
  }

}
