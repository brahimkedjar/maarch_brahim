<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Password Model
 * @author dev@maarch.org
 */

namespace SrcCore\models;


use Exception;
use Throwable;

class PasswordModel
{
    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function getRules(array $aArgs = []): array
    {
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data']);

        $aRules = DatabaseModel::select([
            'select' => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'  => ['password_rules'],
            'where'  => $aArgs['where'] ?? [],
            'data'   => $aArgs['data'] ?? [],
        ]);

        return $aRules;
    }

    /**
     * @return array
     * @throws Exception
     */
    public static function getEnabledRules(): array
    {
        $aRules = DatabaseModel::select([
            'select' => ['label', 'value'],
            'table'  => ['password_rules'],
            'where'  => ['enabled = ?'],
            'data'   => [true],
        ]);

        $formattedRules = [];
        foreach ($aRules as $rule) {
            if (!str_contains($rule['label'], 'complexity')) {
                $formattedRules[$rule['label']] = $rule['value'];
            } else {
                $formattedRules[$rule['label']] = true;
            }
        }

        return $formattedRules;
    }

    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function getRuleById(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::arrayType($aArgs, ['select']);

        $rules = DatabaseModel::select([
            'select' => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'  => ['password_rules'],
            'where'  => ['id = ?'],
            'data'   => [$aArgs['id']],
        ]);

        if (empty($rules[0])) {
            return [];
        }

        return $rules[0];
    }

    /**
     * @param array $aArgs
     * @return true
     * @throws Exception
     */
    public static function updateRuleById(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id', 'value']);
        ValidatorModel::stringType($aArgs, ['enabled']);

        DatabaseModel::update([
            'table' => 'password_rules',
            'set'   => [
                '"value"' => $aArgs['value'],
                'enabled' => $aArgs['enabled'],
            ],
            'where' => ['id = ?'],
            'data'  => [$aArgs['id']]
        ]);

        return true;
    }

    /**
     * @param array $aArgs
     * @return bool
     * @throws Exception
     */
    public static function isPasswordHistoryValid(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['password', 'userSerialId']);
        ValidatorModel::stringType($aArgs, ['password']);
        ValidatorModel::intVal($aArgs, ['userSerialId']);

        $passwordRules = PasswordModel::getEnabledRules();

        if (!empty($passwordRules['historyLastUse'])) {
            $passwordHistory = DatabaseModel::select([
                'select'   => ['password'],
                'table'    => ['password_history'],
                'where'    => ['user_serial_id = ?'],
                'data'     => [$aArgs['userSerialId']],
                'order_by' => ['id DESC'],
                'limit'    => $passwordRules['historyLastUse']
            ]);

            foreach ($passwordHistory as $value) {
                if (password_verify($aArgs['password'], $value['password'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param array $aArgs
     * @return true
     * @throws Exception
     */
    public static function setHistoryPassword(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['password', 'userSerialId']);
        ValidatorModel::stringType($aArgs, ['password']);
        ValidatorModel::intVal($aArgs, ['userSerialId']);

        $passwordHistory = DatabaseModel::select([
            'select'   => ['id'],
            'table'    => ['password_history'],
            'where'    => ['user_serial_id = ?'],
            'data'     => [$aArgs['userSerialId']],
            'order_by' => ['id DESC']
        ]);

        if (count($passwordHistory) >= 10) {
            DatabaseModel::delete([
                'table' => 'password_history',
                'where' => ['id < ?', 'user_serial_id = ?'],
                'data'  => [$passwordHistory[8]['id'], $aArgs['userSerialId']]
            ]);
        }

        DatabaseModel::insert([
            'table'         => 'password_history',
            'columnsValues' => [
                'user_serial_id' => $aArgs['userSerialId'],
                'password'       => AuthenticationModel::getPasswordHash($aArgs['password'])
            ],
        ]);

        return true;
    }

    /**
     * @param array $aArgs
     * @return string
     * @throws Exception
     * @deprecated This function is deprecated and will be removed in future major versions.
     * Please use PasswordController::encrypt() instead.
     */
    public static function encrypt(array $aArgs): string
    {
        ValidatorModel::notEmpty($aArgs, ['password']);
        ValidatorModel::stringType($aArgs, ['password']);

        $enc_key = CoreConfigModel::getEncryptKey();

        $cipher_method = 'AES-128-CTR';
        $enc_iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher_method));
        $crypted_token = openssl_encrypt(
            $aArgs['password'],
            $cipher_method,
            $enc_key,
            0,
            $enc_iv
        ) . "::" . bin2hex($enc_iv);

        return $crypted_token;
    }

    /**
     * @param array $aArgs
     * @return string
     * @throws Exception
     * @deprecated This function is deprecated and will be removed in future major versions.
     * Please use PasswordController::decrypt() instead.
     */
    public static function decrypt(array $aArgs): string
    {
        ValidatorModel::notEmpty($aArgs, ['cryptedPassword']);
        ValidatorModel::stringType($aArgs, ['cryptedPassword']);

        $enc_key = CoreConfigModel::getEncryptKey();

        $cipher_method = 'AES-128-CTR';

        $token = null;
        try {
            $cryptedPasswordParts = explode("::", $aArgs['cryptedPassword']);
            if (count($cryptedPasswordParts) !== 2) {
                throw new Exception(
                    "Invalid format: cryptedPassword should contain two parts separated by '::'"
                );
            }
            list($crypted_token, $enc_iv) = $cryptedPasswordParts;

            $token = openssl_decrypt($crypted_token, $cipher_method, $enc_key, 0, hex2bin($enc_iv));
        } catch (Throwable $th) {
            throw new Exception($th->getMessage());
        }

        return $token;
    }
}
