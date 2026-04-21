<?php

namespace Drupal\itkdev_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\itkdev_booking\Helper\BookingHelper;
use Drupal\itkdev_booking\Helper\SampleDataHelper;
use Drupal\itkdev_booking\Helper\UserHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * UserBooking controller.
 */
class BookingController extends ControllerBase {
  protected BookingHelper $bookingHelper;
  protected bool $bookingApiSampleData;
  protected UserHelper $userHelper;

  /**
   * UserBookingsController constructor.
   *
   * @param \Drupal\itkdev_booking\Helper\BookingHelper $bookingsHelper
   */
  public function __construct(BookingHelper $bookingsHelper) {
    $this->bookingHelper = $bookingsHelper;
    $this->bookingApiSampleData = Settings::get('itkdev_booking_api_sample_data', FALSE);
    $this->userHelper = new UserHelper();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): BookingController {
    return new static($container->get('itkdev_booking.booking_helper'));
  }

  /**
   * Get locations.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \JsonException
   */
  public function getLocations(Request $request): JsonResponse {
    if ($this->bookingApiSampleData) {
      $data = SampleDataHelper::getSampleData('locations');
      return new JsonResponse($data, 200);
    }

    $response = $this->bookingHelper->getLocations($request);

    return new JsonResponse($response['data'], $response['statusCode']);
  }

  /**
   * Get resources.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \JsonException
   */
  public function getResources(Request $request): JsonResponse {
    if ($this->bookingApiSampleData) {
      $data = SampleDataHelper::getSampleData('resources');
      return new JsonResponse($data, 200);
    }

    $response = $this->bookingHelper->getResources($request);

    return new JsonResponse($response['data'], $response['statusCode']);
  }

  /**
   * Get all resources.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \JsonException
   */
  public function getAllResources(Request $request): JsonResponse {
    if ($this->bookingApiSampleData) {
      $data = SampleDataHelper::getSampleData('resources');
      return new JsonResponse($data, 200);
    }

    $response = $this->bookingHelper->getAllResources($request);

    return new JsonResponse($response['data'], $response['statusCode']);
  }

  /**
   * Get resource by id.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $resourceEmail
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \JsonException
   */
  public function getResource(Request $request, string $resourceEmail): JsonResponse {
    if ($this->bookingApiSampleData) {
      $data = SampleDataHelper::getSampleData('resource');
      return new JsonResponse($data, 200);
    }

    $response = $this->bookingHelper->getResourceByEmail($request, $resourceEmail);

    return new JsonResponse($response['data'], $response['statusCode']);
  }

  /**
   * Get busy intervals.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \Exception
   */
  public function getBusyIntervals(Request $request): JsonResponse {
    if ($this->bookingApiSampleData) {
      $data = SampleDataHelper::getSampleData('busy-intervals');
      return new JsonResponse($data, 200);
    }

    $response = $this->bookingHelper->getBusyIntervals($request);

    return new JsonResponse($response['data'], $response['statusCode']);
  }

  /**
   * Get logged in user's booking.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \JsonException
   */
  public function getUserBookings(Request $request): JsonResponse {
    if ($this->bookingApiSampleData) {
      $data = SampleDataHelper::getSampleData('user-bookings');
      return new JsonResponse($data, 200);
    }

    $response = $this->bookingHelper->getUserBookings($request);

    return new JsonResponse($response['data'], $response['statusCode']);
  }

  /**
   * Get logged in user's pending booking.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \JsonException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getPendingBookings(Request $request): JsonResponse {
    $response = $this->bookingHelper->getPendingBookings($request);

    return new JsonResponse($response['data'], $response['statusCode']);
  }

  /**
   * Delete booking with given bookingId.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $bookingId
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \JsonException
   */
  public function deleteUserBooking(Request $request, string $bookingId): JsonResponse {
    if ($this->bookingApiSampleData) {
      return new JsonResponse([], 201);
    }

    $response = $this->bookingHelper->deleteUserBooking($request, $bookingId);

    return new JsonResponse($response['data'], $response['statusCode']);
  }

  /**
   * Patch booking with given bookingId.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $bookingId
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \JsonException
   */
  public function patchUserBooking(Request $request, string $bookingId): JsonResponse {
    if ($this->bookingApiSampleData) {
      return new JsonResponse([], 201);
    }

    $response = $this->bookingHelper->patchUserBooking($request, $bookingId);

    return new JsonResponse($response['data'], $response['statusCode']);
  }

  /**
   * Get booking details for a given booking id.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $bookingId
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \JsonException
   */
  public function getUserBookingDetails(Request $request, string $bookingId): JsonResponse {
    if ($this->bookingApiSampleData) {
      $data = SampleDataHelper::getSampleData('booking-details');
      return new JsonResponse($data, 200);
    }

    $response = $this->bookingHelper->getUserBookingDetails($request, $bookingId);

    return new JsonResponse($response['data'], $response['statusCode']);
  }

  /**
   * Get user information.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \JsonException
   */
  public function getUserInformation(Request $request): JsonResponse {
    $userArray = $this->userHelper->getUserValues($request);

    return new JsonResponse([
      'userType' => $userArray['userType'],
    ], 200);
  }

}
