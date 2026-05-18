<?php

namespace Drupal\itkdev_booking\Helper;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class UserHelper {
  protected bool $bookingApiSampleUser;

  public function __construct() {
    $this->bookingApiSampleUser = Settings::get('itkdev_booking_api_sample_user', FALSE);
  }

  /**
   * @throws \JsonException
   */
  public function attachUserToHeaders(Request $request, array $headers): array {
    $userArray = $this->getUserValues($request);

    if ($userArray != NULL) {
      $headers['Authorization-UserId'] = $userArray['userId'];
      $headers['Authorization-UserPermission'] = $userArray['permission'];
    }

    return $headers;
  }

  /**
   * @throws \JsonException
   */
  public function attachPermissionQueryParameters(Request $request, array $query, bool $attachUserId = FALSE): array {
    $userArray = $this->getUserValues($request);

    if ($userArray != NULL) {
      $permission = $userArray['permission'];

      if ($permission == 'businessPartner') {
        $query['permissionBusinessPartner'] = TRUE;

        if (isset($userArray['whitelistKey'])) {
          $query['whitelistKey'] = $userArray['whitelistKey'];
        }
      }
      elseif ($permission == 'citizen') {
        $query['permissionCitizen'] = TRUE;
      }
    }

    return $query;
  }

  /**
   * @throws \JsonException
   */
  public function getUserValues(Request $request): ?array {
    if ($this->bookingApiSampleUser) {
      return SampleDataHelper::getSampleData('user-business');
    }

    $session = $request->getSession();
    $userToken = $session->get('os2forms_nemlogin_openid_connect.user_token');

    if (isset($userToken['cvr'])) {
      $permission = 'businessPartner';
      $whitelistKey = $userToken['cvr'];
      $userId = $this->generateUserId($userToken['cvr']);
      $userType = 'businessPartner';
    }
    elseif (isset($userToken['cpr']) && isset($userToken['pid'])) {
      $permission = 'citizen';
      $userType = 'citizen';
      $userId = $this->generateUserId($userToken['pid']);
    }
    else {
      return NULL;
    }

    return [
      'name' => $userToken['name'] ?? NULL,
      'givenName' => $userToken['given_name'] ?? NULL,
      'permission' => $permission,
      'userId' => $userId,
      'whitelistKey' => $whitelistKey ?? NULL,
      'userType' => $userType,
    ];
  }

  /**
   *
   */
  private function generateUserId(string $uniqueIdentifier): string {
    return Crypt::hashBase64($uniqueIdentifier);
  }

}
