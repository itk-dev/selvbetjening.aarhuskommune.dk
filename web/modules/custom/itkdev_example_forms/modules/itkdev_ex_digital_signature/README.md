# itkdev_ex_digital_signature

Digital signature example forms.

Read more about Digital Signatures here:
[OS2Forms Digital Signature module](https://github.com/OS2Forms/os2forms/tree/develop/modules/os2forms_digital_signature#how-does-it-work)

## Install

```php
drush pm:install itkdev_ex_digital_signature
```

## Why

When an attachment element is configured for digital signature, the generated
PDF must contain no clickable links. That includes header, footer, colophon
and element values.

Note, that there's no actual digital signature handler in the example forms.
This is because whether an attachment is to be signed or not is
determined by the attachment-element.

## Which elements

Several elements may be rendered as links. As of writing this, that is:

* email
* tel
* url
* markup
* managed_file
* webform_document_file
* webform_audio_file
* webform_video_file
* webform_image_file
* webform_term_select
* webform_term_checkboxes

Note that this list only contains enabled elements.

## How it's done

1. Mark when a signature PDF is being generated, see `DigitalSignatureFlaggingPrintBuilder`.
2. Strip `<a>` from the rendered HTML, see `DigitalSignatureLinkStripperSubscriber`.
3. Render file element values as plain filenames, see `CustomViewBuilderWebformSubmission::overrideFormatsForPdf()`.
