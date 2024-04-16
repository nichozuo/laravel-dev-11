<?php

namespace LaravelDev\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Services\OpenApiServices;
use ReflectionException;

class DocController extends Controller
{
    /**
     * @return JsonResponse
     * @throws ReflectionException
     * @throws Err
     */
    public function getOpenApi(): JsonResponse
    {
//        sleep(3);
        return response()->json(OpenApiServices::ToArray());
    }
}
