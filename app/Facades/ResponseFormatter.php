<?php

namespace App\Facades;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * JSON Response Formatter.
 */
class ResponseFormatter
{
    /**
     * API Response
     *
     * @var array
     */
    protected static $response = [
        'meta' => [
            'code' => 200,
            'status' => 'success',
            'messages' => [],
            'validations' => null,
        ],
        'data' => null,
    ];

    /**
     * Give success response.
     */
    public static function success(array|Model|Collection $data = null, array | string $messages = null)
    {
        if (!is_null($messages)) {
            if (!is_array($messages)) {
                self::$response['meta']['messages'] = [$messages];
            } else {
                self::$response['meta']['messages'] = $messages;
            }
        }

        self::$response['meta']['response_date'] = Carbon::now()->format('Y-m-d H:i:s');
        self::$response['data'] = $data;

        return response()->json(self::$response, self::$response['meta']['code']);
    }

    /**
     * Give error response.
     */
    public static function error($code = 400, $messages = null, bool $isValidation = false)
    {
        if (!is_null($messages)) {
            $key = $isValidation ? 'validations' : 'messages';
            if (!is_array($messages) && !is_object($messages)) {
                self::$response['meta'][$key] = [$messages];
            } else {
                self::$response['meta'][$key] = $messages;
            }
        }

        self::$response['meta']['status'] = 'error';
        self::$response['meta']['code'] = $code;
        self::$response['meta']['response_date'] = Carbon::now()->format('Y-m-d H:i:s');

        return response()->json(self::$response, self::$response['meta']['code']);
    }
}
