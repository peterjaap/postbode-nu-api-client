<?php

namespace App\Clients;

use App\Models\Letter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class PostbodeNuApiClient.
 */
class PostbodeNuApiClient extends Client
{
    public const DEFAULT_ENVELOPE = 2;
    public const STATUS_SEND = true;
    public const STATUS_CONCEPT = false;
    public const COLOR_BLACK_WHITE = 'BW';
    public const COLOR_FULL = 'FC';
    public const PRINT_ONESIDED = 'simplex';
    public const PRINT_TWOSIDED = 'duplex';
    public const PRINTER_INKJET = 'inkjet';
    public const PRINTER_TONER = 'toner';

    /**
     * @var bool
     */
    protected $status = self::STATUS_SEND;
    /**
     * @var string
     */
    protected $printer = self::PRINT_TWOSIDED;
    /**
     * @var string
     */
    protected $sides = self::PRINT_TWOSIDED;
    /**
     * @var string
     */
    protected $color = self::COLOR_FULL;
    /**
     * @var int
     */
    protected $envelope = self::DEFAULT_ENVELOPE;
    /**
     * @var array
     */
    protected $metadata = [];
    /**
     * @var string
     */
    protected $country_code = 'NL';
    /**
     * @var Letter
     */
    protected $letter;
    /**
     * @var array
     */
    protected $queue;
    /**
     * @var int
     */
    protected $mailboxId;
    /**
     * @var string
     */
    protected $coverAddress;
    /**
     * @var string
     */
    protected $registered;

    /**
     * PostbodeNuApiClient constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->mailboxId = config('postbode.mailbox_id');
        $config['headers'] = [
            'User-Agent' => 'PostbodeNuApiClient / ' . config('app.name') . ' / PHP ' . phpversion(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Authorization' => config('postbode.api_key')
        ];
        $config['base_uri'] = config('postbode.endpoint');
        parent::__construct($config);
    }

    /**
     * @return mixed
     * @throws GuzzleException
     */
    public function getAvailableMailboxes()
    {
        $responseBody = $this->get('mailbox')->getBody();

        return json_decode($responseBody, true);
    }

    /**
     * @return mixed
     * @throws GuzzleException
     */
    public function sendLetter()
    {
        $responseBody = $this->post(sprintf('mailbox/%s/letters', $this->getMailboxId()), $this->buildLetterData())->getBody();

        return json_decode($responseBody, true);
    }

    /**
     * @return $this
     */
    public function addLetterToQueue()
    {
        $this->queue[] = $this->buildLetterData();
        return $this;
    }

    /**
     * @return array[]
     */
    private function buildLetterData()
    {
        $data = [
            'json' => [
                'documents' => [
                    [
                        'name' => $this->getLetter()->getPdfFilename(),
                        'content' => base64_encode(file_get_contents($this->getLetter()->getStoragePath())),
                    ],
                ],
                'envelope_id' => $this->getEnvelope(),
                'country' => $this->getCountryCode(),
                'registered' => false,
                'send' => $this->getStatus(),
                'color' => $this->getColor(),
                'printing' => $this->getSides(),
                'printer' => $this->getPrinter(),
                'metadata' => $this->getMetadata(),
            ],
        ];

        if ($this->getCoverAddress()) {
            $data['json']['cover_address'] = $this->getCoverAddress();
        }
    }

    /**
     * @return mixed
     * @throws GuzzleException
     */
    public function sendLetterQueue()
    {
        $responseBody = $this->post(sprintf('mailbox/%s/letterbatch', $this->getMailboxId()), $this->queue);

        return json_decode($responseBody, true);
    }

    /**
     * @param $id
     * @return mixed
     * @throws GuzzleException
     */
    public function cancelLetter($id)
    {
        $responseBody = $this->get(sprintf('mailbox/%s/letter/%s/cancel', $this->getMailboxId(), $id))->getBody();

        return json_decode($responseBody, true);
    }

    /**
     * @return mixed
     * @throws GuzzleException
     */
    public function getLettersFromMailbox()
    {
        $responseBody = $this->get(sprintf('mailbox/%s/letters', $this->getMailboxId()))->getBody();

        return json_decode($responseBody, true);
    }

    /**
     * @param $id
     * @return mixed
     * @throws GuzzleException
     */
    public function getLetterFromMailbox($id)
    {
        $responseBody = $this->get(sprintf('mailbox/%s/letter/%s', $this->getMailboxId(), $id))->getBody();

        return json_decode($responseBody, true);
    }

    /**
     * @param int $mailboxId
     */
    public function setMailboxId(int $mailboxId): PostbodeNuApiClient
    {
        $this->mailboxId = $mailboxId;
        return $this;
    }

    /**
     * @param mixed $letter
     * @return PostbodeNuApiClient
     */
    public function setLetter(Letter $letter): PostbodeNuApiClient
    {
        $this->letter = $letter;
        return $this;
    }

    /**
     * When status is STATUS_CONCEPT, letter will be placed as concept to be sent later manually
     *
     * @param $status
     * @return PostbodeNuApiClient
     */
    public function setStatus($status): PostbodeNuApiClient
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param $printer
     * @return PostbodeNuApiClient
     */
    public function setPrinter($printer): PostbodeNuApiClient
    {
        $this->printer = $printer;
        return $this;
    }

    /**
     * @param $sides
     * @return PostbodeNuApiClient
     */
    public function setSides($sides): PostbodeNuApiClient
    {
        $this->sides = $sides;
        return $this;
    }

    /**
     * @param $color
     * @return PostbodeNuApiClient
     */
    public function setColor($color): PostbodeNuApiClient
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @param $countryCode
     * @return PostbodeNuApiClient
     */
    public function setCountryCode($countryCode): PostbodeNuApiClient
    {
        $this->country_code = $countryCode;
        return $this;
    }

    /**
     * @param $metadata
     * @return PostbodeNuApiClient
     */
    public function setMetadata($metadata): PostbodeNuApiClient
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Standard 2 ( C5 window left) or custom envelop
     *
     * @param $envelope
     * @return PostbodeNuApiClient
     */
    public function setEnvelope($envelope): PostbodeNuApiClient
    {
        $this->envelope = $envelope;
        return $this;
    }

    /**
     * When provided an empty paper with the address will be generated in front of content
     *
     * @param string $coverAddress
     * @return PostbodeNuApiClient
     */
    public function setCoverAddress(string $coverAddress): PostbodeNuApiClient
    {
        $this->coverAddress = $coverAddress;
        return $this;
    }

    /**
     * @param bool $registered
     * @return PostbodeNuApiClient
     */
    public function setRegistered(bool $registered): PostbodeNuApiClient
    {
        $this->registered = $registered;
        return $this;
    }

    /**
     * @return int
     */
    public function getMailboxId(): int
    {
        return $this->mailboxId;
    }

    /**
     * @return mixed
     */
    public function getLetter(): Letter
    {
        return $this->letter;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getPrinter(): string
    {
        return $this->printer;
    }

    /**
     * @return string
     */
    public function getSides(): string
    {
        return $this->sides;
    }

    /**
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * @return int
     */
    public function getEnvelope(): int
    {
        return $this->envelope;
    }

    /**
     * Extra data that will be returned to the mailbox webhook url
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @return mixed
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

    /**
     * @return string
     */
    public function getCoverAddress(): string
    {
        return $this->coverAddress;
    }

    /**
     * @return bool
     */
    public function getRegistered(): bool
    {
        return $this->registered;
    }
}
