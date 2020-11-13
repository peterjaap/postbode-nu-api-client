# postbode-nu-api-client
Postbode.nu fluent API client for Laravel

## Usage

```php
// Send immediately
$this->apiClient->setLetter($letter)->sendLetter();

// Send through queue
collect($letters)->each(function ($letter) {
    $this->apiClient->addLetterToQueue($letter);
});
$this->apiClient->sendLetterQueue();
```

## Available methods
| Method  | Type  |  Comment |
|---|---|---|
| getAvailableMailboxes  | GET  | Gets the available mailboxes  |
| sendLetter | POST | Sends the current letter |
| addLetterToQueue | POST | Adds the current letter to the local queue |
| sendLetterQueue | POST | Process the local letter queue |
| cancelLetter | POST | Cancels a letter in the mailbox |
| getLettersFromMailbox | GET | Gets all letters from the mailbox |
| getLetterFromMailbox  | GET | Gets a specific letter from the mailbox |

## Available setters/getters
Fluent camelcased setters and getters are available for:
- `mailbox_id`
- `country_code`
- `status`
- `printer`
- `sides`
- `color`
- `envelope`
- `metadata`
- `letter`

## Config

Add `config/postbode.php`;

```php
<?php

return [
    'api_key' => env('POSTBODE_NU_API_KEY'),
    'mailbox_id' => env('POSTBODE_NU_MAILBOX_ID'),
    'endpoint' => env('POSTBODE_NU_ENDPOINT', 'https://app.postbode.nu/api/'),
];

```

And add your variables to `.env`;

```
POSTBODE_NU_API_KEY=
POSTBODE_NU_MAILBOX_ID=
```
