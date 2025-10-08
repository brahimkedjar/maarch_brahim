<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Logs Processor
 * @author dev@maarch.org
 * @ingroup core
 */

namespace SrcCore\processors;

// using Monolog version 2.6.0

class LogProcessor
{
    private array $lineData;
    private bool $isSql;
    private $extraData;

    /**
     * @param array $lineData
     * @param bool $isSql
     * @param array $extraData
     */
    public function __construct(array $lineData = [], bool $isSql = false, array $extraData = [])
    {
        $this->lineData = $lineData;
        $this->isSql = $isSql;
        $this->extraData = $extraData;
    }

    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record): array
    {
        $record['extra']['processId'] = getmypid();
        $record['extra']['extraData'] = $this->extraData;

        $record = $this->prepareRecord($record);

        if ($this->isSql) {
            $record = $this->prepareSqlRecord($record);
        }

        return $record;
    }

    /**
     * @param array $record
     * @return array
     */
    public function prepareRecord(array $record = []): array
    {
        $newData = [
            'WHERE'     => $this->lineData['tableName'] ?? ':noTableName',
            'ID'        => $this->lineData['recordId'] ?? ':noRecordId',
            'HOW'       => $this->lineData['eventType'] ?? ':noEventType',
            'USER'      => $GLOBALS['login'] ?? ':noUser',
            'WHAT'      => $this->lineData['eventId'] ?? ':noEventId',
            'ID_MODULE' => $this->lineData['moduleId'] ?? ':noModuleId',
            'REMOTE_IP' => (
                empty($_SERVER['REMOTE_ADDR']) || $_SERVER['REMOTE_ADDR'] == '::1') ?
                gethostbyname(gethostname()) : $_SERVER['REMOTE_ADDR']
        ];

        return array_merge($record, $newData);
    }

    /**
     * @param array $record
     * @return array
     */
    public function prepareSqlRecord(array $record = []): array
    {
        $sqlData = ':noSqlData';
        if (!empty($this->lineData['sqlData'])) {
            $sqlData = $this->lineData['sqlData'];

            if (is_array($this->lineData['sqlData'])) {
                $sqlData = json_encode($this->lineData['sqlData']);
            }
        }

        $record['QUERY'] = $this->lineData['sqlQuery'] ?? ':noSqlQuery';
        $record['DATA'] = $sqlData;
        $record['EXCEPTION'] = $this->lineData['sqlException'] ?? ':noSqlException';

        return $record;
    }
}
