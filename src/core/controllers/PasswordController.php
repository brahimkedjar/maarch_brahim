<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Password Controller
 *
 * @author dev@maarch.org
 */

namespace SrcCore\controllers;

use Group\controllers\PrivilegeController;
use History\controllers\HistoryController;
use Respect\Validation\Validator;
use Slim\Psr7\Request;
use SrcCore\http\Response;
use SrcCore\models\PasswordModel;
use SrcCore\models\ValidatorModel;
use Exception;
use SrcCore\models\CoreConfigModel;


class PasswordController
{
    public function getRules(Request $request, Response $response)
    {
        return $response->withJson(['rules' => PasswordModel::getRules()]);
    }

    public function updateRules(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_password_rules', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParsedBody();
        $check = Validator::arrayType()->notEmpty()->validate($data['rules']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        foreach ($data['rules'] as $rule) {
            $check = Validator::intVal()->validate($rule['value']);
            $check = $check && Validator::stringType()->validate($rule['label']);
            $check = $check && Validator::boolType()->validate($rule['enabled']);
            if (!$check) {
                continue;
            }

            $existingRule = PasswordModel::getRuleById(['id' => $rule['id'], 'select' => ['label']]);
            if (empty($existingRule) || $existingRule['label'] != $rule['label']) {
                continue;
            }

            $rule['enabled'] = empty($rule['enabled']) ? 'false' : 'true';
            PasswordModel::updateRuleById($rule);
        }

        HistoryController::add([
            'tableName' => 'password_rules',
            'recordId'  => 'rules',
            'eventType' => 'UP',
            'info'      => _PASSWORD_RULES_UPDATED,
            'moduleId'  => 'core',
            'eventId'   => 'passwordRulesModification',
        ]);

        return $response->withJson(['success' => 'success']);
    }

    public static function isPasswordValid(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['password']);
        ValidatorModel::stringType($aArgs, ['password']);

        $passwordRules = PasswordModel::getEnabledRules();

        if (!empty($passwordRules['minLength'])) {
            if (strlen($aArgs['password']) < $passwordRules['minLength']) {
                return false;
            }
        }
        if (!empty($passwordRules['complexityUpper'])) {
            if (!preg_match('/[A-Z]/', $aArgs['password'])) {
                return false;
            }
        }
        if (!empty($passwordRules['complexityNumber'])) {
            if (!preg_match('/[0-9]/', $aArgs['password'])) {
                return false;
            }
        }
        if (!empty($passwordRules['complexitySpecial'])) {
            if (!preg_match('/[^a-zA-Z0-9]/', $aArgs['password'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Encrypt data with the old vhost or new file encryption key
     *
     * @param   array   $args{dataToEncrypt: string}
     *
     * @throws  Exception   It can throws an exception
     */
    public static function encrypt(array $args): string
    {
        ValidatorModel::notEmpty($args, ['dataToEncrypt']);
        ValidatorModel::stringType($args, ['dataToEncrypt']);

        if (CoreConfigModel::useVhostEncryptKey()) {
            return PasswordModel::encrypt(['password' => $args['dataToEncrypt']]);
        } else {
            return PasswordController::newEncrypt(['dataToEncrypt' => $args['dataToEncrypt']]);
        }
    }

    /**
     * Decrypt encrypted data with the old vhost or new file encryption key
     *
     * @param   array   $args{encryptedData: string}
     *
     * @throws  Exception   It can throws an exception
     */
    public static function decrypt(array $args): string
    {
        ValidatorModel::notEmpty($args, ['encryptedData']);
        ValidatorModel::stringType($args, ['encryptedData']);

        if (CoreConfigModel::useVhostEncryptKey()) {
            return PasswordModel::decrypt(['cryptedPassword' => $args['encryptedData']]);
        } else {
            return PasswordController::newDecrypt(['encryptedData' => $args['encryptedData']]);
        }
    }

    /**
     * @deprecated This function logic will be moved to PasswordController::encrypt() in future major versions.
     * Please use PasswordController::encrypt() instead.
     *
     * @return string
     */
    public static function newEncrypt(array $args): string
    {
        ValidatorModel::notEmpty($args, ['dataToEncrypt']);
        ValidatorModel::stringType($args, ['dataToEncrypt']);

        $encryptedResult = null;
        $encryptKey      = CoreConfigModel::getEncryptKey();
        $cipherMethod    = 'AES-256-CTR';

        try {
            $encryptKeyHash  = openssl_digest($encryptKey, 'sha256');

            $initialisationVector = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipherMethod));
            $encrypted = openssl_encrypt(
                $args['dataToEncrypt'],
                $cipherMethod,
                $encryptKeyHash,
                OPENSSL_RAW_DATA,
                $initialisationVector
            );

            if ($encrypted === false) {
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => 'Encryption/Decryption',
                    'level'     => 'ERROR',
                    'eventType' => 'Decrypt',
                    'eventId'   => 'Encryption failed: ' . openssl_error_string()
                ]);
                throw new Exception('Encryption failed: ' . openssl_error_string());
            }

            $encryptedResult = $initialisationVector . $encrypted;
        } catch (Exception $e) {
            LogsController::add([
                'isTech'    => true,
                'moduleId'  => 'Encryption/Decryption',
                'level'     => 'ERROR',
                'eventType' => 'Decrypt',
                'eventId'   => 'Encryption Exception: ' . $e->getMessage()
            ]);
            throw new Exception('Encryption Exception: ' . $e->getMessage());
        }

        return base64_encode($encryptedResult);
    }

    /**
     * @deprecated This function logic will be moved to PasswordController::decrypt() in future major versions.
     * Please use PasswordController::decrypt() instead.
     *
     * @return string
     */
    public static function newDecrypt(array $args): string
    {
        ValidatorModel::notEmpty($args, ['encryptedData']);
        ValidatorModel::stringType($args, ['encryptedData']);

        $decryptedResult = null;
        $encryptKey      = CoreConfigModel::getEncryptKey();
        $cipherMethod    = 'AES-256-CTR';
        $encryptedData   = base64_decode($args['encryptedData']);

        try {
            $initialisationVectorLength = openssl_cipher_iv_length($cipherMethod);

            // encrypted data integrity check on size of data
            if (strlen($encryptedData) < $initialisationVectorLength) {
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => 'Encryption/Decryption',
                    'level'     => 'ERROR',
                    'eventType' => 'Decrypt',
                    'eventId'   => 'Decryption failed: data length '
                        . strlen($encryptedData)
                        . ' is less than iv length '
                        . $initialisationVectorLength
                ]);
                throw new Exception(
                    'Decryption failed: data length '
                    . strlen($encryptedData)
                    . ' is less than iv length '
                    . $initialisationVectorLength
                );
            }

            // Extract the initialisation vector and encrypted data
            $initialisationVector   = substr($encryptedData, 0, $initialisationVectorLength);
            $encryptedData          = substr($encryptedData, $initialisationVectorLength);
            $encryptKeyHash         = openssl_digest($encryptKey, 'sha256');

            $decryptedResult = openssl_decrypt(
                $encryptedData,
                $cipherMethod,
                $encryptKeyHash,
                OPENSSL_RAW_DATA,
                $initialisationVector
            );

            if ($decryptedResult === false) {
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => 'Encryption/Decryption',
                    'level'     => 'ERROR',
                    'eventType' => 'Decrypt',
                    'eventId'   => 'Decryption failed: ' . openssl_error_string()
                ]);
                throw new Exception('Decryption failed: ' . openssl_error_string());
            }
        } catch (Exception $e) {
            LogsController::add([
                'isTech'    => true,
                'moduleId'  => 'Encryption/Decryption',
                'level'     => 'ERROR',
                'eventType' => 'Decrypt',
                'eventId'   => 'Decryption Exception: ' . $e->getMessage()
            ]);
            throw new Exception('Decryption Exception: ' . $e->getMessage());
        }

        return $decryptedResult;
    }
}
