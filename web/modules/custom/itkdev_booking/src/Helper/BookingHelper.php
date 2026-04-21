<?php

namespace Drupal\itkdev_booking\Helper;

use Drupal\Core\Site\Settings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Booking helper.
 */
class BookingHelper {
  protected string $bookingApiEndpoint;

  protected string $bookingApiKey;

  protected array $headers;

  protected UserHelper $userHelper;

  public function __construct() {
    $this->bookingApiEndpoint = Settings::get('itkdev_booking_api_endpoint');
    $this->bookingApiKey = Settings::get('itkdev_booking_api_key');
    $this->userHelper = new UserHelper();

    $this->headers = [
      'accept' => 'application/ld+json',
      'Authorization' => 'Apikey ' . $this->bookingApiKey,
    ];
  }

  /**
   * Get locations.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getLocations(Request $request): array {
    try {
      $endpoint = $this->bookingApiEndpoint;
      $client = new Client();

      $query = [];

      // Attach user query parameters if user is logged in.
      $query = $this->userHelper->attachPermissionQueryParameters($request, $query);

      $response = $client->get("{$endpoint}v1/locations", [
        'query' => $query,
        'headers' => $this->headers,
      ]);

      $statusCode = $response->getStatusCode();
      $content = $response->getBody()->getContents();

      $data = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);

      return [
        'statusCode' => $statusCode,
        'data' => $data,
      ];
    }
    catch (ClientException $e) {
      throw new HttpException($e->getCode(), 'Get locations failed.');
    }
    catch (\JsonException $e) {
      throw new HttpException($e->getCode(), 'Get locations failed. Could not decode response.');
    }
  }

  /**
   * Get resources by query.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getResources(Request $request): array {
    try {
      $endpoint = $this->bookingApiEndpoint;
      $client = new Client();

      $query = $request->query->all();

      // Attach permission query parameters if user is logged in.
      $query = $this->userHelper->attachPermissionQueryParameters($request, $query);

      $response = $client->get("{$endpoint}v1/resources", [
        'query' => $query,
        'headers' => $this->headers,
      ]);

      $statusCode = $response->getStatusCode();
      $content = $response->getBody()->getContents();

      $data = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);

      return [
        'statusCode' => $statusCode,
        'data' => $data,
      ];
    }
    catch (ClientException $e) {
      throw new HttpException($e->getCode(), 'Get resources failed.');
    }
    catch (\JsonException $e) {
      throw new HttpException($e->getCode(), 'Get resources failed. Could not decode response.');
    }
  }

  /**
   * Get all resources.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getAllResources(Request $request): array {
    try {
      $endpoint = $this->bookingApiEndpoint;
      $client = new Client();

      $query = [];

      // Attach permission query parameters if user is logged in.
      $query = $this->userHelper->attachPermissionQueryParameters($request, $query);

      $headers = $this->userHelper->attachUserToHeaders($request, $this->headers);

      $response = $client->get("{$endpoint}v1/resources-all", [
        'query' => $query,
        'headers' => $headers,
      ]);

      $statusCode = $response->getStatusCode();
      $content = $response->getBody()->getContents();

      $data = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);

      return [
        'statusCode' => $statusCode,
        'data' => $data,
      ];
    }
    catch (ClientException $e) {
      throw new HttpException($e->getCode(), 'Get all resources failed.');
    }
    catch (\JsonException $e) {
      throw new HttpException($e->getCode(), 'Get all resources failed. Could not decode response.');
    }
  }

  /**
   * Get resource by id.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $resourceEmail
   *
   * @return array
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getResourceByEmail(Request $request, string $resourceEmail): array {
    try {
      $endpoint = $this->bookingApiEndpoint;
      $client = new Client();

      $query = [];

      // Attach user query parameters if user is logged in.
      $query = $this->userHelper->attachPermissionQueryParameters($request, $query);

      $response = $client->get("{$endpoint}v1/resource-by-email/$resourceEmail", [
        'query' => $query,
        'headers' => $this->headers,
      ]);

      $statusCode = $response->getStatusCode();
      $content = $response->getBody()->getContents();

      $data = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);

      return [
        'statusCode' => $statusCode,
        'data' => $data,
      ];
    }
    catch (ClientException $e) {
      throw new HttpException($e->getCode(), 'Get resource by id failed.');
    }
    catch (\JsonException $e) {
      throw new HttpException($e->getCode(), 'Get resource by id failed. Could not decode response.');
    }

  }

  /**
   * Get busy intervals.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getBusyIntervals(Request $request): array {
    try {
      $endpoint = $this->bookingApiEndpoint;
      $client = new Client();

      $query = $request->query->all();

      // Attach user query parameters if user is logged in.
      $query = $this->userHelper->attachPermissionQueryParameters($request, $query);

      $response = $client->get("{$endpoint}v1/busy-intervals", [
        'query' => $query,
        'headers' => $this->headers,
      ]);

      $statusCode = $response->getStatusCode();
      $content = $response->getBody()->getContents();

      $data = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);

      return [
        'statusCode' => $statusCode,
        'data' => $data,
      ];

    }
    catch (ClientException $e) {
      throw new HttpException($e->getCode(), 'Get busy intervals failed.');
    }
    catch (\JsonException $e) {
      throw new HttpException($e->getCode(), 'Get busy intervals failed. Could not decode response.');
    }
  }

  /**
   * Get user bookings.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getUserBookings(Request $request): array {
    try {
      $endpoint = $this->bookingApiEndpoint;
      $client = new Client();

      $query = $request->query->all();

      $headers = $this->userHelper->attachUserToHeaders($request, $this->headers);

      $response = $client->get("{$endpoint}v1/user-booking-cache-entries", [
        'query' => $query,
        'headers' => $headers,
      ]);

      $statusCode = $response->getStatusCode();
      $content = $response->getBody()->getContents();

      $data = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);

      return [
        'statusCode' => $statusCode,
        'data' => $data,
      ];
    }
    catch (ClientException $e) {
      throw new HttpException($e->getCode(), 'Get user bookings failed.');
    }
    catch (\JsonException $e) {
      throw new HttpException($e->getCode(), 'Get user bookings failed. Could not decode response.');
    }
  }

  /**
   * Get updated value of pending user bookings.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getPendingBookings(Request $request): array {
    try {
      $endpoint = $this->bookingApiEndpoint;
      $client = new Client();
      $headers = $this->userHelper->attachUserToHeaders($request, $this->headers);
      $headers['content-type'] = 'application/json';
      $response = $client->request('POST', "{$endpoint}v1/status-by-ids", [
        'headers' => $headers,
        'json' => json_decode($request->getContent()),
      ]);

      $statusCode = $response->getStatusCode();
      $content = $response->getBody()->getContents();

      $data = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);

      return [
        'statusCode' => $statusCode,
        'data' => $data,
      ];
    }
    catch (ClientException $e) {
      throw new HttpException($e->getCode(), 'Get pending bookings failed.');
    }
    catch (\JsonException $e) {
      throw new HttpException($e->getCode(), 'Get pending user bookings failed. Could not decode response.');
    }
  }

  /**
   * Delete user booking.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $bookingId
   *
   * @return array
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function deleteUserBooking(Request $request, string $bookingId): array {
    try {
      $endpoint = $this->bookingApiEndpoint;
      $client = new Client();

      $headers = $this->userHelper->attachUserToHeaders($request, $this->headers);

      $response = $client->delete("{$endpoint}v1/user-bookings/$bookingId", [
        'headers' => $headers,
      ]);

      $statusCode = $response->getStatusCode();

      return [
        'statusCode' => $statusCode,
        'data' => NULL,
      ];
    }
    catch (ClientException $e) {
      throw new HttpException($e->getCode(), 'Delete booking failed.');
    }
    catch (\JsonException $e) {
      throw new HttpException($e->getCode(), 'Delete booking failed. Could not decode response.');
    }
  }

  /**
   * Delete user booking.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $bookingId
   *
   * @return array
   */
  public function patchUserBooking(Request $request, string $bookingId): array {
    try {
      $endpoint = $this->bookingApiEndpoint;
      $client = new Client();

      $headers = $this->userHelper->attachUserToHeaders($request, $this->headers);

      $headers['content-type'] = 'application/merge-patch+json';
      $response = $client->patch("{$endpoint}v1/user-bookings/$bookingId", [
        'json' => json_decode($request->getContent()),
        'headers' => $headers,
      ]);

      $statusCode = $response->getStatusCode();
      $content = $response->getBody()->getContents();

      $data = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);

      return [
        'statusCode' => $statusCode,
        'data' => $data,
      ];
    }
    catch (ClientException $e) {
      throw new HttpException($e->getCode(), 'Booking patch failed.');
    }
    catch (\JsonException $e) {
      throw new HttpException($e->getCode(), 'Booking patch failed. Could not decode response.');
    }
  }

  /**
   * Get booking details.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $bookingId
   *
   * @return array
   */
  public function getUserBookingDetails(Request $request, string $bookingId): array {
    try {
      $endpoint = $this->bookingApiEndpoint;
      $client = new Client();

      $query = [];

      $headers = $this->userHelper->attachUserToHeaders($request, $this->headers);

      $response = $client->get("{$endpoint}v1/user-bookings/$bookingId", [
        'query' => $query,
        'headers' => $headers,
      ]);

      $statusCode = $response->getStatusCode();
      $content = $response->getBody()->getContents();

      $data = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);

      return [
        'statusCode' => $statusCode,
        'data' => $data,
      ];
    }
    catch (ClientException $e) {
      throw new HttpException($e->getCode(), 'Get booking details failed.');
    }
    catch (\JsonException $e) {
      throw new HttpException($e->getCode(), 'Get booking details failed. Could not decode response.');
    }
  }

}
