# Setting up the module in os2forms context with separate booking api project

## 1. Install os2forms selvbetjening

Git clone [https://github.com/itk-dev/os2forms_selvbetjening](https://github.com/itk-dev/os2forms_selvbetjening)

Follow the steps in [https://github.com/itk-dev/os2forms_selvbetjening/blob/develop/README.md](https://github.com/itk-dev/os2forms_selvbetjening/blob/develop/README.md)

Ensure the module is enabled:

```shell
itkdev-docker-compose drush pm:enable itkdev_booking
```

## 2. Create an 'os2forms_rest_api' api key

See [OS2Forms REST API authentication](https://github.com/OS2Forms/os2forms_rest_api?tab=readme-ov-file#authentication),
and follow step 1 and 3.

## 3. Create an Affiliation in os2forms selvbetjening

@todo remove this step when an affiliation term is added as part
of the os2forms_selvbetjening installation.

```text
Structure->Taxonomy->Affiliation

+ Add Term
```

The name is not important in development.

## 4. Install booking service

Git clone: [https://github.com/itk-dev/book_aarhus](https://github.com/itk-dev/book_aarhus)

Follow the readme: [https://github.com/itk-dev/book_aarhus/blob/develop/README.md](https://github.com/itk-dev/book_aarhus/blob/develop/README.md)

Create an ApiKeyUser with the following command:

```shell
docker compose exec phpfpm bin/console app:auth:create-apikey
```

use the api key that was set up in step 2.

## 5. Set up settings.local.php

Set up the book_aarhus service fields.

```php
// Required

// Booking api endpoint, see booking api project to obtain this config
$settings['itkdev_booking_api_endpoint'] = 'http://bookaarhus-nginx-1.frontend:8080/';
$settings['itkdev_booking_api_key'] = '*** Get from book aarhus project ***';

// Endpoint provided by this drupal module (Domain = drupal website with webform)
$settings['itkdev_booking_api_endpoint_frontend'] = 'http://selvbetjening.local.itkdev.dk/';

// See 1Password for license key.
// Datafordeler Book Aarhus
$settings['itkdev_booking_df_map_username'] = '*** Get from 1password ***';
$settings['itkdev_booking_df_map_password'] = '*** Get from 1password ***';

// Fullcalendar
$settings['itkdev_booking_fullcalendar_license'] = '*** Get from 1password ***';
```

`$settings['itkdev_booking_api_endpoint']` is found by running:
`docker ps` in the booking service project.
Find the internal name of the "bookaarhus" nginx container.
Something like `bookaarhus-nginx-1` and append `.frontend:8080/`.

`$settings['itkdev_booking_api_key']` is the apikey created in step 4.

## 6. Set up a webform

Go to `http://selvbetjening.local.itkdev.dk/da/admin/structure/webform`
and "Add webform".
Select a name and the Affiliation that was set up in step 4.

In "build" press "+ Add element" and add "Booking" type.

Give [webform access](https://github.com/OS2Forms/os2forms_rest_api?tab=readme-ov-file#custom-access-control)
to the API user set up in step 2.

## 7. Set up "Api request handler"

In "Settings -> Emails/Handlers" press "+ Add handler".

In "API url" set the SERVICE_ENDPOINT from step 4 with the path
"v1/bookings-webform" appended.

Example: `http://bookaarhus-nginx-1.frontend:8080/v1/bookings-webform`

In "API authorization header" set the text
"Apikey SERVICE_APIKEY" as created  in step 5. SERVICE_APIKEY
is the same as `$settings['itkdev_booking_api_key']`.

Example: `Apikey 1234567890qwertyuioasdfghjklzxcvbnm`

### 8. Creating a booking through the webform

First create a submission from the webform.

After submitting the data the submission is added to the queue in os2forms.

To send the submission to book_aarhus the queue needs to run.

```shell
itkdev-docker-compose drush --uri=http://[SERVICE_ENDPOINT] advancedqueue:queue:process os2forms_api_request_handler -vvv
```

This will create a "WebformSubmitMessage" job in the book_aarhus service.

To create the booking in Exchange the job queue in book_aarhus needs to run.

From the book aarhus api project run:

```shell
itkdev-docker-compose exec phpfpm composer queues
```

This will handle the submission job. This job will retrieve the webform
submission data and create a "CreateBookingMessage" job will handle the
actual submission to Exchange.
