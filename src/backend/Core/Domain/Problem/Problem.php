<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Problem class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\Core\Domain\Problem;

use Exception;
use JsonSerializable;

abstract class Problem extends Exception implements JsonSerializable
{
    private string $type;

    private string $title;

    private string $detail;

    private int $status;

    private string $errors;
    private ?string $lang = null;

    private array $context;

    public function __construct(string $detail, int $status, array $context = [])
    {
        parent::__construct($detail, $status);

        $className = basename(str_replace('\\', '/', static::class));

        $this->type = lcfirst($className);

        $this->title = 'An error occurred';
        $this->detail = $detail;
        $this->errors = $detail;

        $this->status = $status;
        $this->context = $context;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getErrors(): string
    {
        return $this->errors;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function jsonSerialize(bool $debug = true): array
    {
        $json = [
            'title'   => $this->title,
            'type'    => $this->type,
            'detail'  => $this->detail,
            'errors'  => $this->errors,
            'status'  => $this->status,
            'lang'    => $this->lang,
            'context' => $this->context
        ];

        if ($debug) {
            $json += [
                'file'    => $this->getFile(),
                'line'    => $this->getLine(),
                'trace'   => $this->getTrace()
            ];
        }

        return $json;
    }
}
